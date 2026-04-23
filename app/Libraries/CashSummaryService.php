<?php

namespace App\Libraries;

use App\Models\Cash_movement;
use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Sale;

class CashSummaryService
{
    private Cashup $cashup;
    private Cash_movement $cashMovement;
    private Expense $expense;
    private Loan_adjustment $loanAdjustment;
    private Receiving $receiving;
    private Sale $sale;

    public function __construct()
    {
        $this->cashup         = model(Cashup::class);
        $this->cashMovement   = model(Cash_movement::class);
        $this->expense        = model(Expense::class);
        $this->loanAdjustment = model(Loan_adjustment::class);
        $this->receiving      = model(Receiving::class);
        $this->sale           = model(Sale::class);
    }

    /**
     * Builds the session data for a given date.
     * Each session represents one cashup.
     */
    public function buildSessions(string $date): array
    {
        $dayStart = $date . ' 00:00:00';
        $dayEnd   = $date . ' 23:59:59';
        $filters  = [
            'start_date' => $date,
            'end_date'   => $date,
            'is_deleted' => false,
        ];

        $cashups        = $this->cashup->search('', $filters, 0, 0, 'open_date', 'asc')->getResult();
        $cashupSessions = [];
        $allRows        = [
            'cn' => $this->getCnRows($date, $date, $dayStart, $dayEnd),
            'ca' => $this->getCaRows($dayStart, $dayEnd),
            'cp' => $this->getCpRows($date, $date),
            'oe' => $this->getOeRows($dayStart, $dayEnd, true),
        ];

        foreach ($cashups as $cashup) {
            $sessionWindow = $this->buildSessionWindow($cashup, $dayStart, $dayEnd);

            if ($sessionWindow['start'] > $sessionWindow['end']) {
                continue;
            }

            $sessionRows = $this->filterTransactionRowsByWindow($allRows, $sessionWindow['start'], $sessionWindow['end']);

            $cashBeginning = (float) ($cashup->open_amount_cash);

            $cashupSessions[] = [
                'cashup_id'     => (int) $cashup->cashup_id,
                'label'         => $cashup->description ?: to_datetime(strtotime($cashup->open_date)),
                'open_display'  => to_datetime(strtotime($sessionWindow['start'])),
                'close_display' => $sessionWindow['is_open']
                    ? 'Ongoing (' . to_datetime(strtotime($sessionWindow['end'])) . ')'
                    : to_datetime(strtotime($sessionWindow['end'])),
                'window_start'         => $sessionWindow['start'],
                'window_end'           => $sessionWindow['end'],
                'is_open'              => $sessionWindow['is_open'],
                'cash_beginning'       => $cashBeginning,
                'transfer_amount_cash' => (float) ($cashup->transfer_amount_cash ?? 0),
                ...$this->buildSessionTotals($sessionRows, $cashBeginning),
            ];
        }

        return $this->interleaveOutsideCashupSessions($allRows, $cashupSessions, $dayStart, $dayEnd);
    }

    private function getCnRows(string $start, string $end, string $dayStart, string $dayEnd): array
    {
        $rows = array_merge(
            $this->sale->get_cash_sales_for_period($start, $end),
            $this->cashMovement->get_cash_rows_for_period($dayStart, $dayEnd, true),
        );

        usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['trans_time'] ?? ''), (string) ($right['trans_time'] ?? '')));

        return $rows;
    }

    private function getCaRows(string $startDate, string $endDate): array
    {
        return $this->loanAdjustment->get_cash_rows_for_period($startDate, $endDate, true);
    }

    private function getCpRows(string $start, string $end): array
    {
        return $this->receiving->get_cash_receivings_for_period($start, $end);
    }

    private function getOeRows(string $start, string $end, bool $useTimeRange = false): array
    {
        $filters = [
            'start_date'     => $start,
            'end_date'       => $end,
            'use_time_range' => $useTimeRange,
            'only_cash'      => true,
            'only_due'       => false,
            'only_check'     => false,
            'only_credit'    => false,
            'only_debit'     => false,
            'is_deleted'     => false,
        ];

        $result = $this->expense->search('', $filters, 0, 0, 'expense_id', 'asc');
        $rows   = [];

        foreach ($result->getResult() as $expense) {
            $rows[] = [
                'particular' => $expense->description ?: $expense->category_name,
                'amount'     => (float) ($expense->amount),
                'trans_time' => $expense->date,
            ];
        }

        return $rows;
    }

    private function interleaveOutsideCashupSessions(array $rowsByType, array $cashupSessions, string $dayStart, string $dayEnd): array
    {
        if (empty($cashupSessions)) {
            $outsideRows = $this->filterTransactionRowsByWindow($rowsByType, $dayStart, $dayEnd);

            return $this->hasTransactionRows($outsideRows)
                ? [$this->buildOutsideCashupSession($outsideRows, $dayStart, $dayEnd)]
                : [];
        }

        $sessions = [];
        $cursor   = $dayStart;

        foreach ($cashupSessions as $cashupSession) {
            if ($cursor < $cashupSession['window_start']) {
                $outsideRows = $this->filterTransactionRowsByWindow($rowsByType, $cursor, $cashupSession['window_start'], true, false);

                if ($this->hasTransactionRows($outsideRows)) {
                    $sessions[] = $this->buildOutsideCashupSession($outsideRows, $cursor, $cashupSession['window_start']);
                }
            }

            $sessions[] = $cashupSession;

            if ($cashupSession['window_end'] > $cursor) {
                $cursor = $cashupSession['window_end'];
            }
        }

        if ($cursor < $dayEnd) {
            $outsideRows = $this->filterTransactionRowsByWindow($rowsByType, $cursor, $dayEnd, false, true);

            if ($this->hasTransactionRows($outsideRows)) {
                $sessions[] = $this->buildOutsideCashupSession($outsideRows, $cursor, $dayEnd);
            }
        }

        return $sessions;
    }

    private function buildOutsideCashupSession(array $rowsByType, string $windowStart, string $windowEnd): array
    {
        return [
            'cashup_id'            => null,
            'label'                => lang('Cash_summary.outside_cashup'),
            'open_display'         => to_datetime(strtotime($windowStart)),
            'close_display'        => to_datetime(strtotime($windowEnd)),
            'window_start'         => $windowStart,
            'window_end'           => $windowEnd,
            'is_open'              => false,
            'cash_beginning'       => 0.0,
            'transfer_amount_cash' => 0.0,
            ...$this->buildSessionTotals($rowsByType, 0.0),
        ];
    }

    private function filterTransactionRowsByWindow(
        array $rowsByType,
        string $windowStart,
        string $windowEnd,
        bool $includeStart = true,
        bool $includeEnd = true,
    ): array {
        return [
            'cn' => $this->filterRowsByWindow($rowsByType['cn'], $windowStart, $windowEnd, $includeStart, $includeEnd),
            'ca' => $this->filterRowsByWindow($rowsByType['ca'], $windowStart, $windowEnd, $includeStart, $includeEnd),
            'cp' => $this->filterRowsByWindow($rowsByType['cp'], $windowStart, $windowEnd, $includeStart, $includeEnd),
            'oe' => $this->filterRowsByWindow($rowsByType['oe'], $windowStart, $windowEnd, $includeStart, $includeEnd),
        ];
    }

    private function buildSessionTotals(array $rowsByType, float $cashBeginning): array
    {
        $cnTotal = array_sum(array_column($rowsByType['cn'], 'amount'));
        $caTotal = array_sum(array_column($rowsByType['ca'], 'amount'));
        $cpTotal = array_sum(array_column($rowsByType['cp'], 'amount'));
        $oeTotal = array_sum(array_column($rowsByType['oe'], 'amount'));
        $rows    = array_merge(
            array_map(static fn ($row) => ['particular' => $row['particular'], 'trans_time' => $row['trans_time'], 'cn' => $row['amount'], 'ca' => null, 'cp' => null, 'oe' => null], $rowsByType['cn']),
            array_map(static fn ($row) => ['particular' => $row['particular'], 'trans_time' => $row['trans_time'], 'cn' => null, 'ca' => $row['amount'], 'cp' => null, 'oe' => null], $rowsByType['ca']),
            array_map(static fn ($row) => ['particular' => $row['particular'], 'trans_time' => $row['trans_time'], 'cn' => null, 'ca' => null, 'cp' => $row['amount'], 'oe' => null], $rowsByType['cp']),
            array_map(static fn ($row) => ['particular' => $row['particular'], 'trans_time' => $row['trans_time'], 'cn' => null, 'ca' => null, 'cp' => null, 'oe' => $row['amount']], $rowsByType['oe']),
        );
        usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['trans_time'] ?? ''), (string) ($right['trans_time'] ?? '')));

        return [
            'rows'        => $rows,
            'cn_total'    => $cnTotal,
            'ca_total'    => $caTotal,
            'cp_total'    => $cpTotal,
            'oe_total'    => $oeTotal,
            'cash_ending' => $cashBeginning + $cnTotal - $caTotal - $cpTotal - $oeTotal,
        ];
    }

    private function hasTransactionRows(array $rowsByType): bool
    {
        return ! empty($rowsByType['cn'])
            || ! empty($rowsByType['ca'])
            || ! empty($rowsByType['cp'])
            || ! empty($rowsByType['oe']);
    }

    private function filterRowsByWindow(array $rows, string $windowStart, string $windowEnd, bool $includeStart = true, bool $includeEnd = true): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($windowStart, $windowEnd, $includeStart, $includeEnd): bool {
            $transTime = $row['trans_time'] ?? null;

            if (empty($transTime)) {
                return false;
            }

            $afterStart = $includeStart ? $transTime >= $windowStart : $transTime > $windowStart;
            $beforeEnd  = $includeEnd ? $transTime <= $windowEnd : $transTime < $windowEnd;

            return $afterStart && $beforeEnd;
        }));
    }

    private function buildSessionWindow(object $cashup, string $dayStart, string $dayEnd): array
    {
        $isOpen = $this->isPendingCloseCashup($cashup);
        $start  = max($cashup->open_date, $dayStart);
        $end    = $isOpen ? $dayEnd : min($cashup->close_date, $dayEnd);

        if ($end < $start) {
            $end = $start;
        }

        return [
            'start'   => $start,
            'end'     => $end,
            'is_open' => $isOpen,
        ];
    }

    private function isPendingCloseCashup(object $cashup): bool
    {
        return (float) ($cashup->closed_amount_cash ?? 0) === 0.0
            && (float) ($cashup->closed_amount_due ?? 0) === 0.0
            && (float) ($cashup->closed_amount_card ?? 0) === 0.0
            && (float) ($cashup->closed_amount_check ?? 0) === 0.0;
    }
}
