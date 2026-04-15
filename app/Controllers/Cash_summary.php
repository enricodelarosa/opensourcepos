<?php

namespace App\Controllers;

use App\Libraries\CashOnHandService;
use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Sale;
use CodeIgniter\HTTP\ResponseInterface;
use Config\OSPOS;

class Cash_summary extends Secure_Controller
{
    private Cashup $cashup;
    private Expense $expense;
    private Loan_adjustment $loan_adjustment;
    private Receiving $receiving;
    private Sale $sale;
    private array $config;

    public function __construct()
    {
        parent::__construct('cash_summary');

        $this->cashup          = model(Cashup::class);
        $this->expense         = model(Expense::class);
        $this->loan_adjustment = model(Loan_adjustment::class);
        $this->receiving       = model(Receiving::class);
        $this->sale            = model(Sale::class);
        $this->config          = config(OSPOS::class)->settings;
    }

    public function getIndex(): string
    {
        $date = $this->request->getGet('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d');

        $data['date']     = $date;
        $data['sessions'] = $this->_build_sessions($date);

        return view('cash_summary/manage', $data);
    }

    public function getCurrent(): ResponseInterface
    {
        $cashOnHandService = new CashOnHandService();

        return $this->response->setJSON($cashOnHandService->getCurrentCashData());
    }

    /**
     * Builds the session data for a given date.
     * Each session = one cashup. Returns array of session arrays ready for the view.
     */
    private function _build_sessions(string $date): array
    {
        $filters = [
            'start_date' => $date,
            'end_date'   => $date,
            'is_deleted' => false,
        ];

        $cash_ups        = $this->cashup->search('', $filters, 0, 0, 'open_date', 'asc')->getResult();
        $sessions        = [];
        $split_by_cashup = count($cash_ups) > 1;

        foreach ($cash_ups as $cash_up) {
            $session_window = $this->buildSessionWindow($cash_up, $date, $split_by_cashup);

            if ($session_window['start'] > $session_window['end']) {
                continue;
            }

            $start = substr($session_window['start'], 0, 10);
            $end   = substr($session_window['end'], 0, 10);

            // Fetch individual rows for each transaction type
            $cn_rows = $this->filterRowsByWindow($this->_get_cn_rows($start, $end), $session_window['start'], $session_window['end']);
            $ca_rows = $this->filterRowsByWindow($this->_get_ca_rows($cash_up->open_date, $cash_up->close_date), $session_window['start'], $session_window['end']);
            $cp_rows = $this->filterRowsByWindow($this->_get_cp_rows($start, $end), $session_window['start'], $session_window['end']);
            $oe_rows = $this->filterRowsByWindow($this->_get_oe_rows($start, $end), $session_window['start'], $session_window['end']);

            // Merge into a unified row list: each entry has [particular, cn, ca, cp, oe]
            $rows = array_merge(
                array_map(static fn ($r) => ['particular' => $r['particular'], 'cn' => $r['amount'], 'ca' => null, 'cp' => null, 'oe' => null], $cn_rows),
                array_map(static fn ($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => $r['amount'], 'cp' => null, 'oe' => null], $ca_rows),
                array_map(static fn ($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => null, 'cp' => $r['amount'], 'oe' => null], $cp_rows),
                array_map(static fn ($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => null, 'cp' => null, 'oe' => $r['amount']], $oe_rows),
            );

            $cn_total = array_sum(array_column($cn_rows, 'amount'));
            $ca_total = array_sum(array_column($ca_rows, 'amount'));
            $cp_total = array_sum(array_column($cp_rows, 'amount'));
            $oe_total = array_sum(array_column($oe_rows, 'amount'));

            $cash_beginning = (float) ($cash_up->open_amount_cash);
            $cash_ending    = $cash_beginning + $cn_total - $ca_total - $cp_total - $oe_total;

            $sessions[] = [
                'label'         => $cash_up->description ?: to_datetime(strtotime($cash_up->open_date)),
                'open_display'  => to_datetime(strtotime($session_window['start'])),
                'close_display' => $session_window['is_open']
                    ? 'Ongoing (' . to_datetime(strtotime($session_window['end'])) . ')'
                    : to_datetime(strtotime($session_window['end'])),
                'cash_beginning' => $cash_beginning,
                'rows'           => $rows,
                'cn_total'       => $cn_total,
                'ca_total'       => $ca_total,
                'cp_total'       => $cp_total,
                'oe_total'       => $oe_total,
                'cash_ending'    => $cash_ending,
            ];
        }

        return $sessions;
    }

    private function _get_cn_rows(string $start, string $end): array
    {
        return $this->sale->get_cash_sales_for_period($start, $end);
    }

    private function _get_ca_rows(string $open_date, string $close_date): array
    {
        return $this->loan_adjustment->get_cash_rows_for_period(substr($open_date, 0, 10), substr($close_date, 0, 10));
    }

    private function _get_cp_rows(string $start, string $end): array
    {
        return $this->receiving->get_cash_receivings_for_period($start, $end);
    }

    private function _get_oe_rows(string $start, string $end): array
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

        foreach ($result->getResult() as $exp) {
            $rows[] = [
                'particular' => $exp->description ?: $exp->category_name,
                'amount'     => (float) ($exp->amount),
                'trans_time' => $exp->date,
            ];
        }

        return $rows;
    }

    private function filterRowsByWindow(array $rows, string $windowStart, string $windowEnd): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($windowStart, $windowEnd): bool {
            $trans_time = $row['trans_time'] ?? null;

            if (empty($trans_time)) {
                return false;
            }

            return $trans_time >= $windowStart && $trans_time <= $windowEnd;
        }));
    }

    private function buildSessionWindow(object $cashup, string $date, bool $splitByCashup): array
    {
        $day_start = $date . ' 00:00:00';
        $day_end   = $date . ' 23:59:59';

        if (! $splitByCashup) {
            return [
                'start'   => $day_start,
                'end'     => $day_end,
                'is_open' => $this->isPendingCloseCashup($cashup),
            ];
        }

        $is_open = $this->isPendingCloseCashup($cashup);
        $start   = max($cashup->open_date, $day_start);
        $end     = $is_open ? $day_end : min($cashup->close_date, $day_end);

        if ($end < $start) {
            $end = $start;
        }

        return [
            'start'   => $start,
            'end'     => $end,
            'is_open' => $is_open,
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
