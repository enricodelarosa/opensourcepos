<?php

namespace App\Libraries;

use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Sale;

class CashSummaryService
{
    private Cashup $cashup;
    private Expense $expense;
    private Loan_adjustment $loanAdjustment;
    private Receiving $receiving;
    private Sale $sale;

    public function __construct()
    {
        $this->cashup         = model(Cashup::class);
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
        $filters = [
            'start_date' => $date,
            'end_date'   => $date,
            'is_deleted' => false,
        ];

        $cashups       = $this->cashup->search('', $filters, 0, 0, 'open_date', 'asc')->getResult();
        $sessions      = [];
        $splitByCashup = count($cashups) > 1;

        foreach ($cashups as $cashup) {
            $sessionWindow = $this->buildSessionWindow($cashup, $date, $splitByCashup);

            if ($sessionWindow['start'] > $sessionWindow['end']) {
                continue;
            }

            $start = substr($sessionWindow['start'], 0, 10);
            $end   = substr($sessionWindow['end'], 0, 10);

            $cnRows = $this->filterRowsByWindow($this->getCnRows($start, $end), $sessionWindow['start'], $sessionWindow['end']);
            $caRows = $this->filterRowsByWindow($this->getCaRows($cashup->open_date, $cashup->close_date), $sessionWindow['start'], $sessionWindow['end']);
            $cpRows = $this->filterRowsByWindow($this->getCpRows($start, $end), $sessionWindow['start'], $sessionWindow['end']);
            $oeRows = $this->filterRowsByWindow($this->getOeRows($start, $end), $sessionWindow['start'], $sessionWindow['end']);

            $rows = array_merge(
                array_map(static fn ($row) => ['particular' => $row['particular'], 'cn' => $row['amount'], 'ca' => null, 'cp' => null, 'oe' => null], $cnRows),
                array_map(static fn ($row) => ['particular' => $row['particular'], 'cn' => null, 'ca' => $row['amount'], 'cp' => null, 'oe' => null], $caRows),
                array_map(static fn ($row) => ['particular' => $row['particular'], 'cn' => null, 'ca' => null, 'cp' => $row['amount'], 'oe' => null], $cpRows),
                array_map(static fn ($row) => ['particular' => $row['particular'], 'cn' => null, 'ca' => null, 'cp' => null, 'oe' => $row['amount']], $oeRows),
            );

            $cnTotal = array_sum(array_column($cnRows, 'amount'));
            $caTotal = array_sum(array_column($caRows, 'amount'));
            $cpTotal = array_sum(array_column($cpRows, 'amount'));
            $oeTotal = array_sum(array_column($oeRows, 'amount'));

            $cashBeginning = (float) ($cashup->open_amount_cash);
            $cashEnding    = $cashBeginning + $cnTotal - $caTotal - $cpTotal - $oeTotal;

            $sessions[] = [
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
                'rows'                 => $rows,
                'cn_total'             => $cnTotal,
                'ca_total'             => $caTotal,
                'cp_total'             => $cpTotal,
                'oe_total'             => $oeTotal,
                'cash_ending'          => $cashEnding,
            ];
        }

        return $sessions;
    }

    private function getCnRows(string $start, string $end): array
    {
        return $this->sale->get_cash_sales_for_period($start, $end);
    }

    private function getCaRows(string $openDate, string $closeDate): array
    {
        return $this->loanAdjustment->get_cash_rows_for_period(substr($openDate, 0, 10), substr($closeDate, 0, 10));
    }

    private function getCpRows(string $start, string $end): array
    {
        return $this->receiving->get_cash_receivings_for_period($start, $end);
    }

    private function getOeRows(string $start, string $end): array
    {
        $filters = [
            'start_date'  => $start,
            'end_date'    => $end,
            'only_cash'   => true,
            'only_due'    => false,
            'only_check'  => false,
            'only_credit' => false,
            'only_debit'  => false,
            'is_deleted'  => false,
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

    private function filterRowsByWindow(array $rows, string $windowStart, string $windowEnd): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($windowStart, $windowEnd): bool {
            $transTime = $row['trans_time'] ?? null;

            if (empty($transTime)) {
                return false;
            }

            return $transTime >= $windowStart && $transTime <= $windowEnd;
        }));
    }

    private function buildSessionWindow(object $cashup, string $date, bool $splitByCashup): array
    {
        $dayStart = $date . ' 00:00:00';
        $dayEnd   = $date . ' 23:59:59';

        if (! $splitByCashup) {
            return [
                'start'   => $dayStart,
                'end'     => $dayEnd,
                'is_open' => $this->isPendingCloseCashup($cashup),
            ];
        }

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
