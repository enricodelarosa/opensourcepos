<?php

namespace App\Controllers;

use App\Models\Customer_loan;
use App\Models\Loan_adjustment;
use App\Models\Luna;
use App\Models\Supplier;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Config\OSPOS;

/**
 * Loan_adjustments controller
 *
 * Allows manually increasing or decreasing a supplier's loan balance with a
 * corresponding cash impact that feeds into the cashup calculation.
 */
class Loan_adjustments extends Secure_Controller
{
    private Loan_adjustment $loan_adjustment;
    private Customer_loan $customer_loan;
    private Luna $luna;
    private Supplier $supplier;
    private array $config;

    public function __construct()
    {
        parent::__construct('loan_adjustments');

        $this->loan_adjustment = model(Loan_adjustment::class);
        $this->customer_loan   = model(Customer_loan::class);
        $this->luna            = model(Luna::class);
        $this->supplier        = model(Supplier::class);
        $this->config          = config(OSPOS::class)->settings;
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_loan_adjustments_manage_table_headers();

        $data['filters'] = ['is_deleted' => lang('Loan_adjustments.is_deleted')];

        $data = array_merge($data, restoreTableFilters($this->request));

        return view('loan_adjustments/manage', $data);
    }

    public function getSearch(): ResponseInterface
    {
        $search  = $this->request->getGet('search');
        $limit   = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset  = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort    = $this->sanitizeSortColumn(loan_adjustment_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'adjustment_id');
        $order   = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $filters = [
            'start_date' => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'   => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'is_deleted' => false,
        ];

        $request_filters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
        $filters         = array_merge($filters, $request_filters);

        $adjustments = $this->loan_adjustment->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows  = $this->loan_adjustment->get_found_rows($search, $filters);
        $data_rows   = [];

        foreach ($adjustments->getResult() as $adjustment) {
            $data_rows[] = get_loan_adjustment_data_row($adjustment);
        }

        return $this->response->setJSON(['total' => $total_rows, 'rows' => $data_rows]);
    }

    public function getSupplierSuggest(): ResponseInterface
    {
        $search = (string) $this->request->getGet('term', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $this->response->setJSON($this->supplier->getLoanAdjustmentSuggestions($search));
    }

    public function getView(int $adjustment_id = NEW_ENTRY): string
    {
        if ($adjustment_id !== NEW_ENTRY) {
            throw PageNotFoundException::forPageNotFound();
        }

        $data = [];

        $adjustment_info = $this->loan_adjustment->get_info($adjustment_id);
        $linked_loan     = $this->resolveAdjustmentLoan($adjustment_info);

        $current_employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $can_assign_employee = $this->employee->has_grant('employees', $current_employee_id);

        $data['employees'] = [];
        if ($can_assign_employee) {
            foreach ($this->employee->get_all()->getResult() as $employee) {
                $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
            }
        } else {
            $stored_employee_id                     = $adjustment_id === NEW_ENTRY ? $current_employee_id : $adjustment_info->employee_id;
            $stored_employee                        = $this->employee->get_info($stored_employee_id);
            $data['employees'][$stored_employee_id] = $stored_employee->first_name . ' ' . $stored_employee->last_name;
        }
        $data['can_assign_employee'] = $can_assign_employee;

        if ($adjustment_id === NEW_ENTRY) {
            $adjustment_info->adjustment_time = $this->getDefaultDateTimeFromRequest();
            $adjustment_info->employee_id     = $current_employee_id;
        }

        $data['adjustment_info'] = $adjustment_info;

        // Pre-fill supplier name for the autocomplete field
        $data['selected_supplier_name'] = '';
        if (! empty($adjustment_info->supplier_id)) {
            $supplier_info                  = $this->supplier->get_info($adjustment_info->supplier_id);
            $data['selected_supplier_name'] = $this->supplier->getDisplayName($supplier_info, true);
        }

        $data['selected_luna_id'] = '';
        if ($linked_loan && ! empty($linked_loan->luna_id)) {
            $data['selected_luna_id'] = (string) $linked_loan->luna_id;
        }

        return view('loan_adjustments/form', $data);
    }

    private function getDefaultDateTimeFromRequest(): string
    {
        $date = $this->request->getGet('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date . ' ' . date('H:i:s');
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Returns the linked customer_id and current loan balance for a supplier.
     * Called by the form via AJAX after a supplier is selected from autocomplete.
     */
    public function getBalance(int $supplier_id): ResponseInterface
    {
        $supplier_info   = $this->supplier->get_info($supplier_id);
        $can_select_luna = ! empty($supplier_info->person_id)
            && in_array((int) ($supplier_info->category ?? 0), [LAND_OWNER_SUPPLIER, TENANT_SUPPLIER], true);
        $lunas = $can_select_luna ? $this->supplier->get_lunas($supplier_id) : [];
        $no_luna_message = match ((int) ($supplier_info->category ?? 0)) {
            LAND_OWNER_SUPPLIER => lang('Loan_adjustments.no_luna_added'),
            TENANT_SUPPLIER     => lang('Loan_adjustments.no_luna_assigned'),
            default             => lang('Suppliers.no_lunas'),
        };

        if (empty($supplier_info->customer_id)) {
            $linked_customer_id = $this->supplier->ensure_auto_linked_customer($supplier_id);
            if ($linked_customer_id !== null) {
                $supplier_info = $this->supplier->get_info($supplier_id);
            }
        }

        if (empty($supplier_info->customer_id)) {
            return $this->response->setJSON([
                'customer_id'     => null,
                'balance'         => null,
                'breakdown'       => [],
                'lunas'           => $lunas,
                'can_select_luna' => $can_select_luna,
                'no_luna_message' => $no_luna_message,
            ]);
        }

        $balance   = $this->customer_loan->get_loan_balance($supplier_info->customer_id);
        $breakdown = $this->customer_loan->get_loan_balance_breakdown($supplier_info->customer_id);

        return $this->response->setJSON([
            'customer_id'     => $supplier_info->customer_id,
            'balance'         => $balance,
            'breakdown'       => $breakdown,
            'lunas'           => $lunas,
            'can_select_luna' => $can_select_luna,
            'no_luna_message' => $no_luna_message,
        ]);
    }

    public function getRow(int $row_id): ResponseInterface
    {
        $adjustment_info = $this->loan_adjustment->get_info($row_id);
        $data_row        = get_loan_adjustment_data_row($adjustment_info);

        return $this->response->setJSON($data_row);
    }

    public function postSave(int $adjustment_id = NEW_ENTRY): ResponseInterface
    {
        if ($adjustment_id !== NEW_ENTRY) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Loan_adjustments.editing_disabled'),
                'id'      => NEW_ENTRY,
            ]);
        }

        $existing_adjustment   = $adjustment_id === NEW_ENTRY ? null : $this->loan_adjustment->get_info($adjustment_id);
        $existing_loan         = $this->resolveAdjustmentLoan($existing_adjustment);
        $current_employee_id   = $this->employee->get_logged_in_employee_info()->person_id;
        $submitted_employee_id = (int) $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT);

        $employee_id = $this->employee->has_grant('employees', $current_employee_id)
            ? $submitted_employee_id
            : ($adjustment_id === NEW_ENTRY ? $current_employee_id : $this->loan_adjustment->get_info($adjustment_id)->employee_id);

        $supplier_id      = (int) $this->request->getPost('supplier_id', FILTER_SANITIZE_NUMBER_INT);
        $direction        = $this->request->getPost('direction', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // 'increase' or 'decrease'
        $raw_amount       = parse_decimals((string) ($this->request->getPost('amount') ?? ''));
        $amount           = is_numeric($raw_amount) ? (float) $raw_amount : null;
        $comment          = $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $selected_luna_id = $this->request->getPost('luna_id', FILTER_SANITIZE_NUMBER_INT);
        $selected_luna_id = $selected_luna_id === '' || $selected_luna_id === null ? null : (int) $selected_luna_id;

        $newdate         = $this->request->getPost('adjustment_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $date_formatter  = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $newdate);
        $adjustment_time = $date_formatter ? $date_formatter->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

        // Validate supplier has a linked customer
        $supplier_info = $this->supplier->get_info($supplier_id);
        if (empty($supplier_info->customer_id)) {
            $linked_customer_id = $this->supplier->ensure_auto_linked_customer($supplier_id);
            if ($linked_customer_id !== null) {
                $supplier_info = $this->supplier->get_info($supplier_id);
            }
        }

        if (empty($supplier_info->customer_id)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.error_no_linked_customer'), 'id' => NEW_ENTRY]);
        }

        if ($amount === null || $amount <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.amount_positive'), 'id' => NEW_ENTRY]);
        }

        if ($selected_luna_id === null) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.luna_required'), 'id' => NEW_ENTRY]);
        }

        $selected_luna = $this->luna->get_info($selected_luna_id);
        if ($selected_luna === null) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.error_invalid_luna'), 'id' => NEW_ENTRY]);
        }

        $luna_belongs_to_supplier = ((int) $supplier_info->category === LAND_OWNER_SUPPLIER && (int) $selected_luna->landowner_id === $supplier_id)
            || ((int) $supplier_info->category === TENANT_SUPPLIER && (int) ($selected_luna->tenant_id ?? 0) === $supplier_id);

        if (! $luna_belongs_to_supplier) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.error_invalid_luna'), 'id' => NEW_ENTRY]);
        }

        $customer_id = $supplier_info->customer_id;

        // Positive = increase loan (cash out), Negative = decrease loan (cash in)
        $loan_amount = $direction === 'decrease' ? -$amount : $amount;

        $adjustment_data = [
            'adjustment_time' => $adjustment_time,
            'supplier_id'     => $supplier_id,
            'customer_id'     => $customer_id,
            'loan_amount'     => $loan_amount,
            'comment'         => $comment,
            'employee_id'     => $employee_id,
            'deleted'         => 0,
        ];

        $db = Database::connect();
        $db->transStart();

        // Record the loan balance change in customer_loans
        $full_comment = $this->buildLoanComment($loan_amount, $comment);

        if ($existing_loan !== null) {
            $loan_saved = $this->customer_loan->update_loan_entry((int) $existing_loan->loan_id, [
                'customer_id'      => $customer_id,
                'sale_id'          => null,
                'receiving_id'     => null,
                'luna_id'          => $selected_luna_id,
                'loan_amount'      => $loan_amount,
                'transaction_time' => $adjustment_time,
                'comment'          => $full_comment,
            ]);

            if ($loan_saved) {
                $adjustment_data['loan_id'] = (int) $existing_loan->loan_id;
            }
        } else {
            $loan_saved = $this->customer_loan->record_loan($customer_id, $loan_amount, null, null, $full_comment, $selected_luna_id, $adjustment_time);

            if ($loan_saved) {
                $adjustment_data['loan_id'] = $this->customer_loan->get_insert_id();
            }
        }

        $saved = $this->loan_adjustment->save_value($adjustment_data, $adjustment_id);

        $db->transComplete();

        if ($db->transStatus() && $saved) {
            if ($adjustment_id === NEW_ENTRY) {
                return $this->response->setJSON(['success' => true, 'message' => lang('Loan_adjustments.successful_adding'), 'id' => $adjustment_data['adjustment_id']]);
            }

            return $this->response->setJSON(['success' => true, 'message' => lang('Loan_adjustments.successful_updating'), 'id' => $adjustment_id]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.error_adding_updating'), 'id' => NEW_ENTRY]);
    }

    private function resolveAdjustmentLoan(?object $adjustment_info): ?object
    {
        if ($adjustment_info === null || empty($adjustment_info->adjustment_id)) {
            return null;
        }

        if (! empty($adjustment_info->loan_id)) {
            $loan_info = $this->customer_loan->get_info((int) $adjustment_info->loan_id);
            if ($loan_info !== null) {
                return $loan_info;
            }
        }

        if (empty($adjustment_info->customer_id) || empty($adjustment_info->adjustment_time)) {
            return null;
        }

        $loan_info = $this->customer_loan->find_matching_adjustment_loan(
            (int) $adjustment_info->customer_id,
            (float) $adjustment_info->loan_amount,
            (string) $adjustment_info->adjustment_time,
            $this->buildLoanComment((float) $adjustment_info->loan_amount, (string) ($adjustment_info->comment ?? '')),
        );

        if ($loan_info !== null) {
            $this->loan_adjustment->save_value(['loan_id' => (int) $loan_info->loan_id], (int) $adjustment_info->adjustment_id);
        }

        return $loan_info;
    }

    private function buildLoanComment(float $loan_amount, string $comment): string
    {
        $direction_label = $loan_amount < 0
            ? lang('Loan_adjustments.comment_decrease')
            : lang('Loan_adjustments.comment_increase');

        return $direction_label . ($comment !== '' ? ': ' . $comment : '');
    }

    public function postDelete(): ResponseInterface
    {
        $ids_to_delete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (is_array($ids_to_delete)) {
            foreach ($ids_to_delete as $adjustment_id) {
                $this->resolveAdjustmentLoan($this->loan_adjustment->get_info((int) $adjustment_id));
            }
        }

        $loan_ids = $this->loan_adjustment->delete_list($ids_to_delete);

        // Hard-delete the linked customer_loans rows to reverse the balance
        if (! empty($loan_ids)) {
            $this->db->table('customer_loans')->whereIn('loan_id', $loan_ids)->delete();
        }

        if (! empty($loan_ids) || empty($ids_to_delete)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Loan_adjustments.successful_deleted') . ' ' . count($ids_to_delete) . ' ' . lang('Loan_adjustments.one_or_multiple'), 'ids' => $ids_to_delete]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Loan_adjustments.cannot_be_deleted'), 'ids' => $ids_to_delete]);
    }
}
