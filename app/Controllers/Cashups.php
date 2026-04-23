<?php

namespace App\Controllers;

use App\Models\Cash_movement;
use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Reports\Summary_payments;
use CodeIgniter\HTTP\ResponseInterface;
use Config\OSPOS;

class Cashups extends Secure_Controller
{
    private Cashup $cashup;
    private Cash_movement $cash_movement;
    private Expense $expense;
    private Loan_adjustment $loan_adjustment;
    private Receiving $receiving;
    private Summary_payments $summary_payments;
    private array $config;

    public function __construct()
    {
        parent::__construct('cashups');

        $this->cashup           = model(Cashup::class);
        $this->cash_movement    = model(Cash_movement::class);
        $this->expense          = model(Expense::class);
        $this->loan_adjustment  = model(Loan_adjustment::class);
        $this->receiving        = model(Receiving::class);
        $this->summary_payments = model(Summary_payments::class);
        $this->config           = config(OSPOS::class)->settings;
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_cashups_manage_table_headers();

        // filters that will be loaded in the multiselect dropdown
        $data['filters'] = ['is_deleted' => lang('Cashups.is_deleted')];

        // Restore filters from URL
        $data = array_merge($data, restoreTableFilters($this->request));

        return view('cashups/manage', $data);
    }

    /**
     * @return void
     */
    public function getSearch(): ResponseInterface
    {
        $search  = $this->request->getGet('search');
        $limit   = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset  = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort    = $this->sanitizeSortColumn(cashup_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'close_date');
        $order   = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $filters = [
            'start_date' => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),    // TODO: Is this the best way to filter dates
            'end_date'   => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'is_deleted' => false,
        ];

        // Check if any filter is set in the multiselect dropdown
        $request_filters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
        $filters         = array_merge($filters, $request_filters);
        $cash_ups        = $this->cashup->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows      = $this->cashup->get_found_rows($search, $filters);
        $data_rows       = [];

        foreach ($cash_ups->getResult() as $cash_up) {
            $data_rows[] = get_cash_up_data_row($cash_up);
        }

        return $this->response->setJSON(['total' => $total_rows, 'rows' => $data_rows]);
    }

    public function getView(int $cashup_id = NEW_ENTRY): string
    {
        $data = [];

        $data['employees'] = [];

        foreach ($this->employee->get_all()->getResult() as $employee) {
            foreach (get_object_vars($employee) as $property => $value) {
                $employee->{$property} = $value;
            }

            $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
        }

        $cash_ups_info = $this->cashup->get_info($cashup_id);

        foreach (get_object_vars($cash_ups_info) as $property => $value) {
            $cash_ups_info->{$property} = $value;
        }

        // Open cashup
        if ($cash_ups_info->cashup_id === NEW_ENTRY) {
            $cash_ups_info->open_date         = date('Y-m-d H:i:s');
            $cash_ups_info->close_date        = $cash_ups_info->open_date;
            $cash_ups_info->open_employee_id  = $this->employee->get_logged_in_employee_info()->person_id;
            $cash_ups_info->close_employee_id = $this->employee->get_logged_in_employee_info()->person_id;
            $cash_ups_info->expected_amount_cash = (float) $cash_ups_info->open_amount_cash + (float) $cash_ups_info->transfer_amount_cash;
            $cash_ups_info->closed_amount_total  = round((float) $cash_ups_info->closed_amount_cash - (float) $cash_ups_info->expected_amount_cash, 2);
        } elseif ($cash_ups_info->open_date !== null && $cash_ups_info->close_date !== null) {
            $is_pending_close = $this->isPendingCloseCashup($cash_ups_info);

            if ($is_pending_close) {
                $cash_ups_info->close_date = date('Y-m-d H:i:s');
            }

            $cash_breakdown         = $this->buildCashBreakdown($cash_ups_info->open_date, $cash_ups_info->close_date);
            $data['cash_breakdown'] = $cash_breakdown;
            $expected_amount_cash   = $this->calculateExpectedClosingCash(
                (float) $cash_ups_info->open_amount_cash,
                (float) $cash_ups_info->transfer_amount_cash,
                $cash_breakdown,
            );
            $cash_ups_info->expected_amount_cash = $expected_amount_cash;

            if ($is_pending_close) {
                $cash_ups_info->closed_amount_cash = $expected_amount_cash;

                $cash_ups_info->closed_amount_due   = (float) $cash_breakdown['sales_due'];
                $cash_ups_info->closed_amount_card  = (float) $cash_breakdown['sales_card'];
                $cash_ups_info->closed_amount_check = (float) $cash_breakdown['sales_check'];
            }

            $cash_ups_info->closed_amount_total = $this->_calculate_total(
                (float) $cash_ups_info->closed_amount_cash,
                $expected_amount_cash,
            );
        }

        $data['cash_ups_info'] = $cash_ups_info;

        return view('cashups/form', $data);
    }

    public function getRow(int $row_id): ResponseInterface
    {
        $cash_ups_info = $this->cashup->get_info($row_id);
        $data_row      = get_cash_up_data_row($cash_ups_info);

        return $this->response->setJSON($data_row);
    }

    public function postSave(int $cashup_id = NEW_ENTRY): ResponseInterface
    {
        $open_date           = $this->request->getPost('open_date');
        $open_date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $open_date);

        $close_date           = $this->request->getPost('close_date');
        $close_date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $close_date);

        $cash_up_data = [
            'open_date'            => $open_date_formatter->format('Y-m-d H:i:s'),
            'close_date'           => $close_date_formatter->format('Y-m-d H:i:s'),
            'open_amount_cash'     => parse_decimals($this->request->getPost('open_amount_cash')),
            'transfer_amount_cash' => parse_decimals($this->request->getPost('transfer_amount_cash')),
            'closed_amount_cash'   => parse_decimals($this->request->getPost('closed_amount_cash')),
            'closed_amount_due'    => parse_decimals($this->request->getPost('closed_amount_due')),
            'closed_amount_card'   => parse_decimals($this->request->getPost('closed_amount_card')),
            'closed_amount_check'  => parse_decimals($this->request->getPost('closed_amount_check')),
            'closed_amount_total'  => parse_decimals($this->request->getPost('closed_amount_total')),
            'note'                 => $this->request->getPost('note') !== null,
            'description'          => $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'open_employee_id'     => $this->request->getPost('open_employee_id', FILTER_SANITIZE_NUMBER_INT),
            'close_employee_id'    => $this->request->getPost('close_employee_id', FILTER_SANITIZE_NUMBER_INT),
            'deleted'              => $this->request->getPost('deleted') !== null,
        ];

        if ($this->cashup->save_value($cash_up_data, $cashup_id)) {
            // New cashup_id
            if ($cashup_id === NEW_ENTRY) {
                return $this->response->setJSON(['success' => true, 'message' => lang('Cashups.successful_adding'), 'id' => $cash_up_data['cashup_id']]);
            }   // Existing Cashup

            return $this->response->setJSON(['success' => true, 'message' => lang('Cashups.successful_updating'), 'id' => $cashup_id]);
        }   // Failure

        return $this->response->setJSON(['success' => false, 'message' => lang('Cashups.error_adding_updating'), 'id' => NEW_ENTRY]);
    }

    public function postDelete(): ResponseInterface
    {
        $cash_ups_to_delete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($this->cashup->delete_list($cash_ups_to_delete)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Cashups.successful_deleted') . ' ' . count($cash_ups_to_delete) . ' ' . lang('Cashups.one_or_multiple'), 'ids' => $cash_ups_to_delete]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Cashups.cannot_be_deleted'), 'ids' => $cash_ups_to_delete]);
    }

    /**
     * Calculate the total for cashups. Used in app\Views\cashups\form.php
     *
     * @noinspection PhpUnused
     */
    public function postAjax_cashup_total(): ResponseInterface
    {
        $open_amount_cash     = parse_decimals($this->request->getPost('open_amount_cash'));
        $transfer_amount_cash = parse_decimals($this->request->getPost('transfer_amount_cash'));
        $closed_amount_cash   = parse_decimals($this->request->getPost('closed_amount_cash'));
        $cash_movement        = parse_decimals($this->request->getPost('cash_movement'));
        $expected_amount_cash = round($open_amount_cash + $transfer_amount_cash + $cash_movement, 2);

        $total = $this->_calculate_total($closed_amount_cash, $expected_amount_cash);

        return $this->response->setJSON([
            'total'                => to_currency_no_money($total),
            'expected_amount_cash' => to_currency_no_money($expected_amount_cash),
        ]);
    }

    /**
     * Calculate total
     *
     * @param mixed $closed_amount_check
     */
    private function _calculate_total(float $closed_amount_cash, float $expected_amount_cash): float
    {
        return round($closed_amount_cash - $expected_amount_cash, 2);
    }

    private function isPendingCloseCashup(object $cashup): bool
    {
        return (float) ($cashup->closed_amount_cash) === 0.0
            && (float) ($cashup->closed_amount_due) === 0.0
            && (float) ($cashup->closed_amount_card) === 0.0
            && (float) ($cashup->closed_amount_check) === 0.0;
    }

    private function buildCashBreakdown(string $openDate, string $closeDate): array
    {
        $inputs = $this->buildCashBreakdownInputs($openDate, $closeDate);

        $reports_data = $this->summary_payments->getData($inputs);
        $breakdown    = [
            'sales_cash'       => 0.0,
            'sales_due'        => 0.0,
            'sales_card'       => 0.0,
            'sales_check'      => 0.0,
            'cash_movements'   => 0.0,
            'expenses_cash'    => 0.0,
            'loan_adjustments' => 0.0,
            'receivings_cash'  => 0.0,
        ];

        foreach ($reports_data as $row) {
            if ($row['trans_group'] !== lang('Reports.trans_payments')) {
                continue;
            }

            if ($row['trans_type'] === lang('Sales.cash')) {
                $breakdown['sales_cash'] += (float) $row['trans_amount'];
            } elseif ($row['trans_type'] === lang('Sales.due')) {
                $breakdown['sales_due'] += (float) $row['trans_amount'];
            } elseif (
                $row['trans_type'] === lang('Sales.debit')
                || $row['trans_type'] === lang('Sales.credit')
            ) {
                $breakdown['sales_card'] += (float) $row['trans_amount'];
            } elseif ($row['trans_type'] === lang('Sales.check')) {
                $breakdown['sales_check'] += (float) $row['trans_amount'];
            }
        }

        $expense_filters = [
            'only_cash'   => true,
            'only_due'    => false,
            'only_check'  => false,
            'only_credit' => false,
            'only_debit'  => false,
            'is_deleted'  => false,
        ];

        foreach ($this->expense->get_payments_summary('', array_merge($inputs, $expense_filters)) as $row) {
            $breakdown['expenses_cash'] += (float) $row['amount'];
        }

        $breakdown['cash_movements'] = $this->cash_movement->get_cash_total_for_period(
            $inputs['start_date'],
            $inputs['end_date'],
            ! empty($inputs['use_time_range']),
        );
        $breakdown['loan_adjustments'] = $this->loan_adjustment->get_cash_total_for_period(
            $inputs['start_date'],
            $inputs['end_date'],
            ! empty($inputs['use_time_range']),
        );
        $breakdown['receivings_cash'] = $this->receiving->get_cash_total_for_period(
            $inputs['start_date'],
            $inputs['end_date'],
            ! empty($inputs['use_time_range']),
        );

        return $breakdown;
    }

    private function buildCashBreakdownInputs(string $openDate, string $closeDate): array
    {
        return [
            'start_date'      => $openDate,
            'end_date'        => $closeDate,
            'sale_type'       => 'complete',
            'location_id'     => 'all',
            'use_time_range'  => true,
        ];
    }

    private function calculateExpectedClosingCash(float $openAmountCash, float $transferAmountCash, array $cashBreakdown): float
    {
        return round(
            $openAmountCash
            + $transferAmountCash
            + (float) ($cashBreakdown['sales_cash'] ?? 0)
            + (float) ($cashBreakdown['cash_movements'] ?? 0)
            - (float) ($cashBreakdown['expenses_cash'] ?? 0)
            - (float) ($cashBreakdown['loan_adjustments'] ?? 0)
            - (float) ($cashBreakdown['receivings_cash'] ?? 0),
            2,
        );
    }
}
