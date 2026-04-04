<?php

namespace App\Controllers;

use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Sale;
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

        $cash_ups = $this->cashup->search('', $filters, 0, 0, 'cashup_id', 'asc');
        $sessions = [];

        foreach ($cash_ups->getResult() as $cash_up) {
            $start = substr($cash_up->open_date,  0, 10);
            $end   = substr($cash_up->close_date, 0, 10);

            // Fetch individual rows for each transaction type
            $cn_rows = $this->_get_cn_rows($start, $end);
            $ca_rows = $this->_get_ca_rows($cash_up->open_date, $cash_up->close_date);
            $cp_rows = $this->_get_cp_rows($start, $end);
            $oe_rows = $this->_get_oe_rows($start, $end);

            // Merge into a unified row list: each entry has [particular, cn, ca, cp, oe]
            $rows = array_merge(
                array_map(fn($r) => ['particular' => $r['particular'], 'cn' => $r['amount'], 'ca' => null, 'cp' => null, 'oe' => null], $cn_rows),
                array_map(fn($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => $r['amount'],  'cp' => null, 'oe' => null], $ca_rows),
                array_map(fn($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => null, 'cp' => $r['amount'],  'oe' => null], $cp_rows),
                array_map(fn($r) => ['particular' => $r['particular'], 'cn' => null, 'ca' => null, 'cp' => null, 'oe' => $r['amount']],  $oe_rows)
            );

            $cn_total = array_sum(array_column($cn_rows, 'amount'));
            $ca_total = array_sum(array_column($ca_rows, 'amount'));
            $cp_total = array_sum(array_column($cp_rows, 'amount'));
            $oe_total = array_sum(array_column($oe_rows, 'amount'));

            $cash_beginning = floatval($cash_up->open_amount_cash);
            $cash_ending    = $cash_beginning + $cn_total - $ca_total - $cp_total - $oe_total;

            $sessions[] = [
                'label'          => $cash_up->description ?: to_datetime(strtotime($cash_up->open_date)),
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
        $filters = [
            'start_date' => substr($open_date, 0, 10),
            'end_date'   => substr($close_date, 0, 10),
            'is_deleted' => false,
        ];

        $result = $this->loan_adjustment->search('', $filters, 0, 0, 'adjustment_id', 'asc');
        $rows   = [];

        foreach ($result->getResult() as $adj) {
            $rows[] = [
                'particular' => trim($adj->supplier_first_name . ' ' . $adj->supplier_last_name),
                'amount'     => floatval($adj->loan_amount),
            ];
        }

        return $rows;
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
                'amount'     => floatval($exp->amount),
            ];
        }

        return $rows;
    }
}
