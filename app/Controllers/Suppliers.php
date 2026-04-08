<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Customer_loan;
use App\Models\Luna;
use App\Models\Supplier;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Suppliers extends Persons
{
    private Supplier $supplier;
    private Customer $customer;
    private Customer_loan $customer_loan;
    private Luna $luna;

    public function __construct()
    {
        parent::__construct('suppliers');

        $this->supplier      = model(Supplier::class);
        $this->customer      = model(Customer::class);
        $this->customer_loan = model(Customer_loan::class);
        $this->luna          = model(Luna::class);
    }

    private function isAutoLinkedCustomerCategory(int $category): bool
    {
        return in_array($category, [LAND_OWNER_SUPPLIER, TENANT_SUPPLIER], true);
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_suppliers_manage_table_headers();

        return view('people/manage', $data);
    }

    /**
     * Gets one row for a supplier manage table. This is called using AJAX to update one row.
     *
     * @param mixed $row_id
     */
    public function getRow($row_id): ResponseInterface
    {
        $data_row             = get_supplier_data_row($this->supplier->get_info($row_id));
        $data_row['category'] = $this->supplier->get_category_name($data_row['category']);

        return $this->response->setJSON($data_row);
    }

    /**
     * Returns Supplier table data rows. This will be called with AJAX.
     *
     * @return void
     */
    public function getSearch(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort   = $this->sanitizeSortColumn(supplier_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'people.person_id');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $suppliers  = $this->supplier->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->supplier->get_found_rows($search);

        $data_rows = [];

        foreach ($suppliers->getResult() as $supplier) {
            $row             = get_supplier_data_row($supplier);
            $row['category'] = $this->supplier->get_category_name($row['category']);
            $data_rows[]     = $row;
        }

        return $this->response->setJSON(['total' => $total_rows, 'rows' => $data_rows]);
    }

    /**
     * Gives search suggestions based on what is being searched for
     */
    public function getSuggest(): ResponseInterface
    {
        $search      = $this->request->getGet('term');
        $suggestions = $this->supplier->get_search_suggestions($search, true);

        return $this->response->setJSON($suggestions);
    }

    public function suggest_search(): ResponseInterface
    {
        $search      = $this->request->getPost('term');
        $suggestions = $this->supplier->get_search_suggestions($search, false);

        return $this->response->setJSON($suggestions);
    }

    /**
     * Loads the supplier edit form
     */
    public function getView(int $supplier_id = NEW_ENTRY): string
    {
        $info = $this->supplier->get_info($supplier_id);

        foreach (get_object_vars($info) as $property => $value) {
            $info->{$property} = $value;
        }
        $data['person_info'] = $info;
        $data['categories']  = $this->supplier->get_categories();

        $customers_list = ['' => lang('Suppliers.no_linked_customer')];

        foreach ($this->customer->get_all()->getResult() as $customer) {
            $customers_list[$customer->person_id] = $customer->first_name . ' ' . $customer->last_name . (! empty($customer->company_name) ? ' [' . $customer->company_name . ']' : '');
        }
        $data['customers_list']                = $customers_list;
        $data['show_linked_customer_controls'] = ! $this->isAutoLinkedCustomerCategory((int) $info->category);

        $tenants_list = ['' => lang('Suppliers.no_tenant_assigned')];
        $tenants      = $this->supplier->get_all(0, 0, TENANT_SUPPLIER)->getResult();

        usort($tenants, static function (object $left, object $right): int {
            $last_name_comparison = strcasecmp(trim((string) $left->last_name), trim((string) $right->last_name));
            if ($last_name_comparison !== 0) {
                return $last_name_comparison;
            }

            $first_name_comparison = strcasecmp(trim((string) $left->first_name), trim((string) $right->first_name));
            if ($first_name_comparison !== 0) {
                return $first_name_comparison;
            }

            return (int) $left->person_id <=> (int) $right->person_id;
        });

        foreach ($tenants as $tenant) {
            $tenant_label = trim($tenant->last_name . ', ' . $tenant->first_name, ', ');

            $tenants_list[$tenant->person_id] = $tenant_label
                . (! empty($tenant->company_name) ? ' [' . $tenant->company_name . ']' : '');
        }
        $luna_panel_mode = '';
        if ($supplier_id !== NEW_ENTRY) {
            if ((int) $info->category === LAND_OWNER_SUPPLIER) {
                $luna_panel_mode = 'landowner';
            } elseif ((int) $info->category === TENANT_SUPPLIER) {
                $luna_panel_mode = 'tenant';
            }
        }

        $data['tenants_list']    = $tenants_list;
        $data['luna_panel_mode'] = $luna_panel_mode;
        $data['show_luna_panel'] = $luna_panel_mode !== '';

        return view('suppliers/form', $data);
    }

    /**
     * Inserts/updates a supplier
     */
    public function postSave(int $supplier_id = NEW_ENTRY): ResponseInterface
    {
        $first_name = $this->request->getPost('first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $last_name  = $this->request->getPost('last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email      = strtolower($this->request->getPost('email', FILTER_SANITIZE_EMAIL));

        $first_name = $this->nameize($first_name);
        $last_name  = $this->nameize($last_name);
        $category   = (int) $this->request->getPost('category', FILTER_SANITIZE_NUMBER_INT);

        $person_data = [
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'gender'       => $this->request->getPost('gender'),
            'email'        => $email,
            'phone_number' => $this->request->getPost('phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'address_1'    => $this->request->getPost('address_1', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'address_2'    => $this->request->getPost('address_2', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'city'         => $this->request->getPost('city', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'state'        => $this->request->getPost('state', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'zip'          => $this->request->getPost('zip', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'country'      => $this->request->getPost('country', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'comments'     => $this->request->getPost('comments', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $current_supplier_info  = $supplier_id === NEW_ENTRY ? null : $this->supplier->get_info($supplier_id);
        $auto_linked_customer   = $this->isAutoLinkedCustomerCategory($category);
        $create_linked_customer = $auto_linked_customer || $this->request->getPost('create_linked_customer') === '1';
        $selected_customer_id   = $this->request->getPost('customer_id') === '' ? null : (int) $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT);
        $preserved_customer_id  = $current_supplier_info && ! empty($current_supplier_info->customer_id) ? (int) $current_supplier_info->customer_id : null;

        $company_name = trim((string) $this->request->getPost('company_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if (! $auto_linked_customer && $company_name === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Suppliers.company_name_required'),
                'id'      => NEW_ENTRY,
            ]);
        }

        $supplier_data = [
            'company_name'   => $company_name,
            'agency_name'    => $this->request->getPost('agency_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'category'       => $category,
            'account_number' => $this->request->getPost('account_number') === '' ? null : $this->request->getPost('account_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'tax_id'         => $this->request->getPost('tax_id', FILTER_SANITIZE_NUMBER_INT),
            'customer_id'    => $auto_linked_customer ? $preserved_customer_id : ($create_linked_customer ? null : $selected_customer_id),
        ];

        $supplier_label = $company_name !== '' ? $company_name : trim($first_name . ' ' . $last_name);

        if ($this->supplier->save_supplier($person_data, $supplier_data, $supplier_id)) {
            $saved_person_id       = ($supplier_id === NEW_ENTRY) ? $supplier_data['person_id'] : $supplier_id;
            $effective_customer_id = $supplier_data['customer_id'];

            if ($create_linked_customer) {
                if (! $this->customer->exists($saved_person_id)) {
                    $this->customer->create_for_person($saved_person_id, $supplier_data['company_name']);
                }

                if ($auto_linked_customer || $this->customer->exists($saved_person_id)) {
                    $this->supplier->link_customer($saved_person_id, $saved_person_id);
                    $effective_customer_id = $saved_person_id;
                }
            }

            if ($supplier_id === NEW_ENTRY) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => lang('Suppliers.successful_adding') . ' ' . $supplier_label,
                    'id'      => $saved_person_id,
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Suppliers.successful_updating') . ' ' . $supplier_label,
                'id'      => $supplier_id,
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => lang('Suppliers.error_adding_updating') . ' ' . $supplier_label,
            'id'      => NEW_ENTRY,
        ]);
    }

    /**
     * Returns all lunas relevant to a supplier.
     */
    public function getGetLunas(int $supplier_id): ResponseInterface
    {
        $supplier_info = $this->supplier->get_info($supplier_id);
        if (empty($supplier_info->person_id) || ! in_array((int) $supplier_info->category, [LAND_OWNER_SUPPLIER, TENANT_SUPPLIER], true)) {
            return $this->response->setJSON([]);
        }

        return $this->response->setJSON($this->supplier->get_lunas($supplier_id));
    }

    /**
     * Displays a read-only loan details modal for a supplier.
     */
    public function getLoanDetails(int $supplier_id): string
    {
        $supplier_info = $this->supplier->get_info($supplier_id);

        if (empty($supplier_info->person_id)) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (empty($supplier_info->customer_id)) {
            $linked_customer_id = $this->supplier->ensure_auto_linked_customer($supplier_id);
            if ($linked_customer_id !== null) {
                $supplier_info = $this->supplier->get_info($supplier_id);
            }
        }

        $customer_id = ! empty($supplier_info->customer_id) ? (int) $supplier_info->customer_id : null;

        return view('suppliers/loan_details', [
            'supplier_info' => $supplier_info,
            'customer_id'   => $customer_id,
            'loan_balance'  => $customer_id ? $this->customer_loan->get_loan_balance($customer_id) : '0.00',
            'breakdown'     => $customer_id ? $this->customer_loan->get_loan_balance_breakdown($customer_id) : [],
        ]);
    }

    /**
     * Returns paginated loan history rows for the supplier loan details modal.
     */
    public function getLoanHistory(int $supplier_id): ResponseInterface
    {
        $supplier_info = $this->supplier->get_info($supplier_id);

        if (empty($supplier_info->person_id)) {
            return $this->response->setJSON(['total' => 0, 'rows' => []]);
        }

        if (empty($supplier_info->customer_id)) {
            $linked_customer_id = $this->supplier->ensure_auto_linked_customer($supplier_id);
            if ($linked_customer_id !== null) {
                $supplier_info = $this->supplier->get_info($supplier_id);
            }
        }

        if (empty($supplier_info->customer_id)) {
            return $this->response->setJSON(['total' => 0, 'rows' => []]);
        }

        $limit  = max(0, (int) $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT));
        $offset = max(0, (int) $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT));

        $history_rows = $this->customer_loan->get_history((int) $supplier_info->customer_id, null, null, $limit, $offset, 'desc');
        $total_rows   = $this->customer_loan->get_history_total((int) $supplier_info->customer_id);

        $rows = array_map(fn (array $row): array => [
            'loan_id'          => (int) $row['loan_id'],
            'transaction_time' => to_datetime(strtotime($row['transaction_time'])),
            'transaction_type' => $this->formatLoanHistoryType($row),
            'reference'        => $this->formatLoanHistoryReference($row),
            'luna_label'       => $this->formatLunaLabel($row),
            'loan_amount'      => to_currency((float) $row['loan_amount']),
            'comment'          => (string) (! empty($row['adjustment_comment']) ? $row['adjustment_comment'] : ($row['comment'] ?? '')),
        ], $history_rows);

        return $this->response->setJSON(['total' => $total_rows, 'rows' => $rows]);
    }

    /**
     * Creates or updates a luna for a landowner.
     */
    public function postSaveLuna(int $supplier_id): ResponseInterface
    {
        $supplier_info = $this->supplier->get_info($supplier_id);
        if (empty($supplier_info->person_id) || (int) $supplier_info->category !== LAND_OWNER_SUPPLIER) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Suppliers.error_adding_updating')]);
        }

        $luna_id   = (int) $this->request->getPost('luna_id', FILTER_SANITIZE_NUMBER_INT);
        $area_name = trim((string) $this->request->getPost('area_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $barangay  = trim((string) $this->request->getPost('barangay', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $tenant_id = $this->request->getPost('tenant_id', FILTER_SANITIZE_NUMBER_INT);
        $tenant_id = $tenant_id === '' || $tenant_id === null ? null : (int) $tenant_id;

        if ($area_name === '' || $barangay === '') {
            return $this->response->setJSON(['success' => false, 'message' => lang('Suppliers.error_adding_updating')]);
        }

        if ($tenant_id !== null) {
            $tenant_info = $this->supplier->get_info($tenant_id);
            if (empty($tenant_info->person_id) || (int) $tenant_info->category !== TENANT_SUPPLIER || (int) $tenant_info->deleted === DELETED) {
                $tenant_id = null;
            }
        }

        if ($luna_id > 0) {
            $existing_luna = $this->luna->get_info($luna_id);
            if ($existing_luna === null || (int) $existing_luna->landowner_id !== $supplier_id) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Suppliers.error_adding_updating')]);
            }
        }

        $saved = $this->luna->save_luna([
            'area_name'    => $area_name,
            'barangay'     => $barangay,
            'landowner_id' => $supplier_id,
            'tenant_id'    => $tenant_id,
        ], $luna_id > 0 ? $luna_id : NEW_ENTRY);

        return $this->response->setJSON([
            'success' => $saved,
            'message' => $saved ? '' : lang('Suppliers.error_adding_updating'),
            'lunas'   => $this->luna->get_lunas_for_landowner($supplier_id),
        ]);
    }

    /**
     * Soft deletes a luna.
     */
    public function postDeleteLuna(int $luna_id): ResponseInterface
    {
        $luna = $this->luna->get_info($luna_id);
        if ($luna === null) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Suppliers.cannot_be_deleted')]);
        }

        $deleted = $this->luna->delete_luna($luna_id);

        return $this->response->setJSON([
            'success' => $deleted,
            'message' => $deleted ? '' : lang('Suppliers.cannot_be_deleted'),
            'lunas'   => $this->luna->get_lunas_for_landowner((int) $luna->landowner_id),
        ]);
    }

    /**
     * This deletes suppliers from the suppliers table
     */
    public function postDelete(): ResponseInterface
    {
        $suppliers_to_delete = $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT);

        if ($this->supplier->delete_list($suppliers_to_delete)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Suppliers.successful_deleted') . ' ' . count($suppliers_to_delete) . ' ' . lang('Suppliers.one_or_multiple'),
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Suppliers.cannot_be_deleted')]);
    }

    private function formatLoanHistoryType(array $row): string
    {
        if (! empty($row['adjustment_id'])) {
            return lang('Reports.loan_adjustment');
        }

        if (! empty($row['sale_id'])) {
            return lang('Reports.completed_sales');
        }

        if (! empty($row['receiving_id'])) {
            return lang('Module.receivings');
        }

        return lang('Reports.manual_entry');
    }

    private function formatLoanHistoryReference(array $row): string
    {
        if (! empty($row['adjustment_id'])) {
            return 'Adjustment #' . $row['adjustment_id'];
        }

        if (! empty($row['sale_id'])) {
            return 'Sale #' . $row['sale_id'];
        }

        if (! empty($row['receiving_id'])) {
            return 'Purchase #' . $row['receiving_id'];
        }

        return 'Loan #' . $row['loan_id'];
    }

    private function formatLunaLabel(array $row): string
    {
        if (empty($row['luna_id'])) {
            return lang('Reports.general_advance');
        }

        $label = trim((string) ($row['area_name'] ?? ''));

        if (! empty($row['barangay'])) {
            $label .= ' (' . trim((string) $row['barangay']) . ')';
        }

        return $label !== '' ? $label : lang('Reports.general_advance');
    }
}
