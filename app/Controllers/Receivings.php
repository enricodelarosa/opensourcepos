<?php

namespace App\Controllers;

use App\Libraries\Receiving_lib;
use App\Libraries\Token_lib;
use App\Libraries\Barcode_lib;
use App\Models\Customer_loan;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Item_kit;
use App\Models\Receiving;
use App\Models\Stock_location;
use App\Models\Supplier;
use CodeIgniter\HTTP\ResponseInterface;
use Config\OSPOS;
use Config\Services;
use ReflectionException;

class Receivings extends Secure_Controller
{
    private Receiving_lib $receiving_lib;
    private Token_lib $token_lib;
    private Barcode_lib $barcode_lib;
    private Inventory $inventory;
    private Item $item;
    private Item_kit $item_kit;
    private Receiving $receiving;
    private Stock_location $stock_location;
    private Supplier $supplier;
    private array $config;

    public function __construct()
    {
        parent::__construct('receivings');

        $this->receiving_lib = new Receiving_lib();
        $this->token_lib = new Token_lib();
        $this->barcode_lib = new Barcode_lib();

        $this->inventory = model(Inventory::class);
        $this->item_kit = model(Item_kit::class);
        $this->item = model(Item::class);
        $this->receiving = model(Receiving::class);
        $this->stock_location = model(Stock_location::class);
        $this->supplier = model(Supplier::class);
        $this->config = config(OSPOS::class)->settings;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->_reload();
    }

    /**
     * Returns search suggestions for an item. Used in app/Views/sales/register.php
     *
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function getItemSearch(): ResponseInterface
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->item->get_search_suggestions($search, ['search_custom' => false, 'is_deleted' => false], true);
        $suggestions = array_merge($suggestions, $this->item_kit->get_search_suggestions($search));

        return $this->response->setJSON($suggestions);
    }

    /**
     * Gets search suggestions for a stock item. Used in app/Views/receivings/receiving.php
     *
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function getStockItemSearch(): ResponseInterface
    {
        $search = $this->request->getGet('term');
        $suggestions = $this->item->get_stock_search_suggestions($search, ['search_custom' => false, 'is_deleted' => false], true);
        $suggestions = array_merge($suggestions, $this->item_kit->get_search_suggestions($search));

        return $this->response->setJSON($suggestions);
    }

    /**
     * Set supplier if it exists in the database. Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function postSelectSupplier(): string
    {
        $supplier_id = $this->request->getPost('supplier', FILTER_SANITIZE_NUMBER_INT);
        if ($this->supplier->exists($supplier_id)) {
            $this->receiving_lib->set_supplier($supplier_id);
        }

        return $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Change receiving mode for current receiving. Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function postChangeMode(): string
    {
        $stock_destination = $this->request->getPost('stock_destination', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $stock_source = $this->request->getPost('stock_source', FILTER_SANITIZE_NUMBER_INT);

        if ((!$stock_source || $stock_source == $this->receiving_lib->get_stock_source()) &&
            (!$stock_destination || $stock_destination == $this->receiving_lib->get_stock_destination())
        ) {
            $this->receiving_lib->clear_reference();
            $mode = $this->request->getPost('mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $this->receiving_lib->set_mode($mode);
        } elseif ($this->stock_location->is_allowed_location($stock_source, 'receivings')) {
            $this->receiving_lib->set_stock_source($stock_source);
            $this->receiving_lib->set_stock_destination($stock_destination);
        }

        return $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Sets receiving comment. Used in app/Views/receivings/receiving.php
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function postSetComment(): ResponseInterface
    {
        $this->receiving_lib->set_comment($this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Sets the print after sale flag for the receiving. Used in app/Views/receivings/receiving.php
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function postSetPrintAfterSale(): ResponseInterface
    {
        $this->receiving_lib->set_print_after_sale($this->request->getPost('recv_print_after_sale') != null);
        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Sets the reference number for the receiving.  Used in app/Views/receivings/receiving.php
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function postSetReference(): ResponseInterface
    {
        $this->receiving_lib->set_reference($this->request->getPost('recv_reference', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Add an item to the receiving. Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function postAdd(): string
    {
        $data = [];

        $mode = $this->receiving_lib->get_mode();
        $item_id_or_number_or_item_kit_or_receipt = $this->request->getPost('item', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $quantity = 1;
        $price = null;
        $this->token_lib->parse_barcode($quantity, $price, $item_id_or_number_or_item_kit_or_receipt);
        $quantity = ($mode == 'receive' || $mode == 'requisition') ? $quantity : -$quantity;
        $item_location = $this->receiving_lib->get_stock_source();
        $discount = $this->config['default_receivings_discount'];
        $discount_type = $this->config['default_receivings_discount_type'];

        if ($mode == 'return' && $this->receiving->is_valid_receipt($item_id_or_number_or_item_kit_or_receipt)) {
            $this->receiving_lib->return_entire_receiving($item_id_or_number_or_item_kit_or_receipt);
        } elseif ($this->item_kit->is_valid_item_kit($item_id_or_number_or_item_kit_or_receipt)) {
            $this->receiving_lib->add_item_kit($item_id_or_number_or_item_kit_or_receipt, $item_location, $discount, $discount_type);
        } elseif (!$this->receiving_lib->add_item($item_id_or_number_or_item_kit_or_receipt, $quantity, $item_location, $discount,  $discount_type)) {
            $data['error'] = lang('Receivings.unable_to_add_item');
        }

        return $this->_reload($data);    // TODO: Hungarian notation
    }

    /**
     * Edit line item in current receiving. Used in app/Views/receivings/receiving.php
     *
     * @param string|int|null $item_id
     * @return string
     * @noinspection PhpUnused
     */
    public function postEditItem($item_id): string
    {
        $data = [];

        $validation_rule = [
            'price'    => 'trim|required|decimal_locale',
            'quantity' => 'trim|required|decimal_locale',
            'discount' => 'trim|permit_empty|decimal_locale',
        ];

        $price = parse_decimals($this->request->getPost('price'));
        $quantity = parse_quantity($this->request->getPost('quantity'));
        $raw_receiving_quantity = parse_quantity($this->request->getPost('receiving_quantity'));

        $description = $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);    // TODO: Duplicated code
        $serialnumber = $this->request->getPost('serialnumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $discount_type = $this->request->getPost('discount_type', FILTER_SANITIZE_NUMBER_INT);
        $discount = $discount_type
            ? parse_quantity(filter_var($this->request->getPost('discount'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            : parse_decimals(filter_var($this->request->getPost('discount'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));

        $receiving_quantity = filter_var($raw_receiving_quantity, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($this->validate($validation_rule)) {
            $this->receiving_lib->edit_item($item_id, $description, $serialnumber, $quantity, $discount, $discount_type, $price, $receiving_quantity);
        } else {
            $data['error'] = lang('Receivings.error_editing_item');
        }

        return $this->_reload($data);    // TODO: Hungarian notation
    }

    /**
     * Edit a receiving. Used in app/Controllers/Receivings.php
     * @param $receiving_id
     * @return string
     * @noinspection PhpUnused
     */
    public function getEdit($receiving_id): string
    {
        $data = [];

        $data['suppliers'] = ['' => 'No Supplier'];
        foreach ($this->supplier->get_all()->getResult() as $supplier) {
            $data['suppliers'][$supplier->person_id] = $supplier->first_name . ' ' . $supplier->last_name;
        }

        $receiving_info = $this->receiving->get_info($receiving_id)->getRowArray();
        
        $current_employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $can_assign_employee = $this->employee->has_grant('employees', $current_employee_id);

        $data['employees'] = [];
        if ($can_assign_employee) {
            foreach ($this->employee->get_all()->getResult() as $employee) {
                $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
            }
        } else {
            $stored_employee_id = $receiving_info['employee_id'];
            $stored_employee = $this->employee->get_info($stored_employee_id);
            $data['employees'][$stored_employee_id] = $stored_employee->first_name . ' ' . $stored_employee->last_name;
        }

        $data['selected_supplier_name'] = !empty($receiving_info['supplier_id']) ? $receiving_info['first_name'] . ' ' . $receiving_info['last_name'] : '';
        $data['selected_supplier_id'] = $receiving_info['supplier_id'];
        $data['receiving_info'] = $receiving_info;
        $data['can_assign_employee'] = $can_assign_employee;

        return view('receivings/form', $data);
    }

    /**
     * Deletes an item from the current receiving. Used in app/Views/receivings/receiving.php
     *
     * @param $item_number
     * @return string
     * @noinspection PhpUnused
     */
    public function getDeleteItem($item_number): string
    {
        $this->receiving_lib->delete_item($item_number);

        return $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * @throws ReflectionException
     * @return ResponseInterface
     */
    public function postDelete(int $receiving_id = -1, bool $update_inventory = true): ResponseInterface
    {
        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $receiving_ids = $receiving_id == -1 ? $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT) : [$receiving_id];    // TODO: Replace -1 with constant

        if ($this->receiving->delete_list($receiving_ids, $employee_id, $update_inventory)) {    // TODO: Likely need to surround this block of code in a try-catch to catch the ReflectionException
            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Receivings.successfully_deleted') . ' ' . count($receiving_ids) . ' ' . lang('Receivings.one_or_multiple'),
                'ids'     => $receiving_ids
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => lang('Receivings.cannot_be_deleted')]);
        }
    }

    /**
     * Removes a supplier from a receiving. Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getRemoveSupplier(): string
    {
        $this->receiving_lib->clear_reference();
        $this->receiving_lib->remove_supplier();

        return $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Complete and finalize receiving.  Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function postComplete(): string
    {

        $data = [];

        $data['cart'] = $this->receiving_lib->get_cart();
        $data['total'] = $this->receiving_lib->get_total();
        $data['transaction_time'] = to_datetime(time());
        $data['mode'] = $this->receiving_lib->get_mode();
        $data['comment'] = $this->receiving_lib->get_comment();
        $data['reference'] = $this->receiving_lib->get_reference();
        $data['payment_type'] = $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $data['show_stock_locations'] = $this->stock_location->show_locations('receivings');
        $data['stock_location'] = $this->receiving_lib->get_stock_source();

        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $employee_info = $this->employee->get_info($employee_id);
        $data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;

        $supplier_id = $this->receiving_lib->get_supplier();
        if ($supplier_id != -1) {
            $supplier_info = $this->supplier->get_info($supplier_id);
            $data['supplier'] = $supplier_info->first_name . ' ' . $supplier_info->last_name;    // TODO: duplicated code
            $data['first_name'] = $supplier_info->first_name;
            $data['last_name'] = $supplier_info->last_name;
            $data['supplier_email'] = $supplier_info->email;
            $data['supplier_address'] = $supplier_info->address_1;
            if (!empty($supplier_info->zip) or !empty($supplier_info->city)) {
                $data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;
            } else {
                $data['supplier_location'] = '';
            }
        }

        // Handle loan deduction if provided
        $loan_deduction = 0;
        if ($this->request->getPost('loan_deduction') != null && $this->request->getPost('loan_deduction') != '') {
            $loan_deduction = parse_decimals($this->request->getPost('loan_deduction'));
        }
        $data['loan_deduction'] = $loan_deduction;

        // Handle partner loan deduction if provided
        $partner_loan_deduction = 0;
        if ($this->request->getPost('partner_loan_deduction') != null && $this->request->getPost('partner_loan_deduction') != '') {
            $partner_loan_deduction = parse_decimals($this->request->getPost('partner_loan_deduction'));
        }
        $data['partner_loan_deduction'] = $partner_loan_deduction;

        $customer_loan = model(Customer_loan::class);

        // Validate primary loan deduction
        if ($loan_deduction > 0 && $supplier_id != -1) {
            $linked_customer_id = $this->supplier->get_linked_customer_id($supplier_id);
            if ($linked_customer_id) {
                $current_balance = (float) $customer_loan->get_loan_balance($linked_customer_id);

                if ($loan_deduction > $current_balance) {
                    $data['error'] = lang('Receivings.loan_deduction_exceeds');
                    return $this->_reload($data);
                }
            }
        }

        // Validate partner loan deduction
        if ($partner_loan_deduction > 0 && $supplier_id != -1) {
            $partner_supplier_id = $this->supplier->get_partner_supplier_id($supplier_id);
            if ($partner_supplier_id) {
                $partner_customer_id = $this->supplier->get_linked_customer_id($partner_supplier_id);
                if ($partner_customer_id) {
                    $partner_balance = (float) $customer_loan->get_loan_balance($partner_customer_id);
                    if ($partner_loan_deduction > $partner_balance) {
                        $data['error'] = lang('Receivings.partner_loan_deduction_exceeds');
                        return $this->_reload($data);
                    }
                }
            }
        }

        // Validate combined deductions do not exceed receiving total
        if (($loan_deduction + $partner_loan_deduction) > $data['total']) {
            $data['error'] = lang('Receivings.combined_deduction_exceeds_total');
            return $this->_reload($data);
        }

        // Calculate amounts after loan deductions
        $cash_amount = $data['total'] - $loan_deduction - $partner_loan_deduction;

        $partner_amount_tendered = 0;
        if ($this->request->getPost('partner_amount_tendered') != null && $this->request->getPost('partner_amount_tendered') != '') {
            $partner_amount_tendered = parse_decimals($this->request->getPost('partner_amount_tendered'));
        }
        $data['partner_amount_tendered'] = $partner_amount_tendered;

        if (($loan_deduction + $partner_loan_deduction) > 0 && $cash_amount <= 0) {
            // Fully paid by loan deduction — no cash payment needed
            $data['payment_type'] = lang('Sales.loan_deduction');
            $data['amount_tendered'] = 0;
            $data['amount_change'] = to_currency(0);
        } elseif ($this->request->getPost('amount_tendered') != null && $this->request->getPost('amount_tendered') != '') {
            $data['amount_tendered'] = parse_decimals($this->request->getPost('amount_tendered'));
            $total_tendered = $data['amount_tendered'] + $partner_amount_tendered;

            // Validate split amounts sum to cash_amount when partner is involved
            if ($partner_amount_tendered > 0 && abs($total_tendered - $cash_amount) > 0.01) {
                $data['error'] = lang('Receivings.cash_amounts_mismatch');
                return $this->_reload($data);
            }

            $data['amount_change'] = to_currency($total_tendered - $cash_amount);
        }

        // SAVE receiving to database
        $receiving_id_num = $this->receiving->save_value($data['cart'], $supplier_id, $employee_id, $data['comment'], $data['reference'], $data['payment_type'], $data['stock_location']);
        $data['receiving_id'] = 'RECV ' . $receiving_id_num;

        if ($data['receiving_id'] == 'RECV -1') {
            $data['error_message'] = lang('Receivings.transaction_failed');
        } else {
            $data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['receiving_id']);

            // Record primary loan deduction if applicable
            if ($loan_deduction > 0 && $supplier_id != -1) {
                $linked_customer_id = $this->supplier->get_linked_customer_id($supplier_id);
                if ($linked_customer_id) {
                    $customer_loan->record_loan(
                        $linked_customer_id,
                        -$loan_deduction,    // Negative = paying off the loan
                        null,
                        $receiving_id_num,
                        'Loan deduction from receiving RECV ' . $receiving_id_num
                    );
                    $data['loan_balance_after'] = $customer_loan->get_loan_balance($linked_customer_id);
                }
            }

            // Record partner loan deduction if applicable
            $partner_supplier_id = $this->supplier->get_partner_supplier_id($supplier_id);
            if ($partner_loan_deduction > 0 && $partner_supplier_id) {
                $partner_customer_id = $this->supplier->get_linked_customer_id($partner_supplier_id);
                if ($partner_customer_id) {
                    $customer_loan->record_loan(
                        $partner_customer_id,
                        -$partner_loan_deduction,
                        null,
                        $receiving_id_num,
                        'Partner loan deduction from receiving RECV ' . $receiving_id_num
                    );
                    $data['partner_loan_balance_after'] = $customer_loan->get_loan_balance($partner_customer_id);
                }
            }

            if ($partner_supplier_id) {
                $partner_info = $this->supplier->get_info($partner_supplier_id);
                $data['partner_supplier_name'] = $partner_info->first_name . ' ' . $partner_info->last_name;
            }

            // Record per-supplier cash payments for cash summary reporting
            if ($cash_amount > 0 && $supplier_id != -1) {
                if ($partner_supplier_id && ($data['amount_tendered'] > 0 || $partner_amount_tendered > 0)) {
                    // Split cash: record separately for each party
                    if (($data['amount_tendered'] ?? 0) > 0) {
                        $this->receiving->record_payment($receiving_id_num, $supplier_id, (float) $data['amount_tendered']);
                    }
                    if ($partner_amount_tendered > 0) {
                        $this->receiving->record_payment($receiving_id_num, $partner_supplier_id, $partner_amount_tendered);
                    }
                } else {
                    // Single supplier — record full cash amount
                    $this->receiving->record_payment($receiving_id_num, $supplier_id, $cash_amount);
                }
            }
        }

        $data['print_after_sale'] = $this->receiving_lib->is_print_after_sale();

        $view = view("receivings/receipt", $data);

        $this->receiving_lib->clear_all();

        return $view;
    }

    /**
     * Complete a receiving requisition. Used in app/Views/receivings/receiving.php.
     *
     * @return string
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function postRequisitionComplete(): string
    {
        if ($this->receiving_lib->get_stock_source() != $this->receiving_lib->get_stock_destination()) {
            foreach ($this->receiving_lib->get_cart() as $item) {
                $this->receiving_lib->delete_item($item['line']);
                $this->receiving_lib->add_item($item['item_id'], $item['quantity'], $this->receiving_lib->get_stock_destination(), $item['discount_type']);
                $this->receiving_lib->add_item($item['item_id'], -$item['quantity'], $this->receiving_lib->get_stock_source(), $item['discount_type']);
            }

            return $this->postComplete();
        } else {
            $data['error'] = lang('Receivings.error_requisition');

            return $this->_reload($data);    // TODO: Hungarian notation
        }
    }

    /**
     * Gets the receipt for a receiving. Used in app/Views/receivings/form.php
     *
     * @param $receiving_id
     * @return string
     * @noinspection PhpUnused
     */
    public function getReceipt($receiving_id): string
    {
        $receiving_info = $this->receiving->get_info($receiving_id)->getRowArray();
        $this->receiving_lib->copy_entire_receiving($receiving_id);
        $data['cart'] = $this->receiving_lib->get_cart();
        $data['total'] = $this->receiving_lib->get_total();
        $data['mode'] = $this->receiving_lib->get_mode();
        $data['transaction_time'] = to_datetime(strtotime($receiving_info['receiving_time']));
        $data['show_stock_locations'] = $this->stock_location->show_locations('receivings');
        $data['payment_type'] = $receiving_info['payment_type'];
        $data['reference'] = $this->receiving_lib->get_reference();
        $data['receiving_id'] = 'RECV ' . $receiving_id;
        $data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['receiving_id']);
        $employee_info = $this->employee->get_info($receiving_info['employee_id']);
        $data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;

        $supplier_id = $this->receiving_lib->get_supplier();    // TODO: Duplicated code
        if ($supplier_id != -1) {
            $supplier_info = $this->supplier->get_info($supplier_id);
            $data['supplier'] = $supplier_info->first_name . ' ' . $supplier_info->last_name;
            $data['first_name'] = $supplier_info->first_name;
            $data['last_name'] = $supplier_info->last_name;
            $data['supplier_email'] = $supplier_info->email;
            $data['supplier_address'] = $supplier_info->address_1;
            if (!empty($supplier_info->zip) or !empty($supplier_info->city)) {
                $data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;
            } else {
                $data['supplier_location'] = '';
            }
        }

        $data['print_after_sale'] = false;

        $view = view("receivings/receipt", $data);

        $this->receiving_lib->clear_all();

        return $view;
    }

    /**
     * @param array $data
     * @return string
     */
    private function _reload(array $data = []): string    // TODO: Hungarian notation
    {
        $data['cart'] = $this->receiving_lib->get_cart();
        $data['modes'] = ['receive' => lang('Receivings.receiving'), 'return' => lang('Receivings.return')];
        $data['mode'] = $this->receiving_lib->get_mode();
        $data['stock_locations'] = $this->stock_location->get_allowed_locations('receivings');
        $data['show_stock_locations'] = count($data['stock_locations']) > 1;
        if ($data['show_stock_locations']) {
            $data['modes']['requisition'] = lang('Receivings.requisition');
            $data['stock_source'] = $this->receiving_lib->get_stock_source();
            $data['stock_destination'] = $this->receiving_lib->get_stock_destination();
        }

        $data['total'] = $this->receiving_lib->get_total();
        $data['items_module_allowed'] = $this->employee->has_grant('items', $this->employee->get_logged_in_employee_info()->person_id);
        $data['comment'] = $this->receiving_lib->get_comment();
        $data['reference'] = $this->receiving_lib->get_reference();
        $data['payment_options'] = $this->receiving->get_payment_options();

        $supplier_id = $this->receiving_lib->get_supplier();

        // Loan balance tracking for linked customers
        $data['loan_balance'] = '0.00';
        $data['has_linked_customer'] = false;
        $data['linked_customer_id'] = null;
        $data['has_partner_supplier'] = false;
        $data['partner_supplier_name'] = '';
        $data['partner_loan_balance'] = '0.00';
        $data['partner_customer_id'] = null;

        if ($supplier_id != -1) {    // TODO: Duplicated Code... replace -1 with a constant
            $supplier_info = $this->supplier->get_info($supplier_id);
            $data['supplier'] = $supplier_info->first_name . ' ' . $supplier_info->last_name;
            $data['first_name'] = $supplier_info->first_name;
            $data['last_name'] = $supplier_info->last_name;
            $data['supplier_email'] = $supplier_info->email;
            $data['supplier_address'] = $supplier_info->address_1;
            if (!empty($supplier_info->zip) or !empty($supplier_info->city)) {
                $data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;
            } else {
                $data['supplier_location'] = '';
            }

            // Check if supplier has a linked customer with outstanding loans
            $linked_customer_id = $this->supplier->get_linked_customer_id($supplier_id);
            if ($linked_customer_id) {
                $customer_loan = model(Customer_loan::class);
                $data['has_linked_customer'] = true;
                $data['linked_customer_id'] = $linked_customer_id;
                $data['loan_balance'] = $customer_loan->get_loan_balance($linked_customer_id);
            }

            // Check if supplier has a linked partner (always show cash split, loan section only if balance > 0)
            $partner_supplier_id = $this->supplier->get_partner_supplier_id($supplier_id);
            if ($partner_supplier_id) {
                $partner_info = $this->supplier->get_info($partner_supplier_id);
                $data['has_partner_supplier'] = true;
                $data['partner_supplier_name'] = $partner_info->first_name . ' ' . $partner_info->last_name;
                $partner_customer_id = $this->supplier->get_linked_customer_id($partner_supplier_id);
                if ($partner_customer_id) {
                    $customer_loan = $customer_loan ?? model(Customer_loan::class);
                    $partner_balance = (float) $customer_loan->get_loan_balance($partner_customer_id);
                    $data['partner_loan_balance'] = $partner_balance;
                    $data['partner_customer_id'] = $partner_customer_id;
                }
            }
        }

        $data['print_after_sale'] = $this->receiving_lib->is_print_after_sale();

        return view("receivings/receiving", $data);
    }

    /**
     * @return ResponseInterface
     * @throws ReflectionException
     */
    public function postSave(int $receiving_id = -1): ResponseInterface    // TODO: Replace -1 with a constant
    {
        $newdate = $this->request->getPost('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);    // TODO: newdate does not follow naming conventions

        $date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $newdate);
        $receiving_time = $date_formatter->format('Y-m-d H:i:s');

        $current_employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $submitted_employee_id = $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT);

        if (!$this->employee->has_grant('employees', $current_employee_id)) {
            $existing_receiving = $this->receiving->get_info($receiving_id)->getRowArray();
            $employee_id = $existing_receiving['employee_id'];
        } else {
            $employee_id = $submitted_employee_id;
        }

        $receiving_data = [
            'receiving_time' => $receiving_time,
            'supplier_id'    => $this->request->getPost('supplier_id') ? $this->request->getPost('supplier_id', FILTER_SANITIZE_NUMBER_INT) : null,
            'employee_id'    => $employee_id,
            'comment'        => $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'reference'      => $this->request->getPost('reference') != '' ? $this->request->getPost('reference', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null
        ];

        $this->inventory->update('RECV ' . $receiving_id, ['trans_date' => $receiving_time]);
        if ($this->receiving->update($receiving_id, $receiving_data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Receivings.successfully_updated'),
                'id'      => $receiving_id
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Receivings.unsuccessfully_updated'),
                'id'      => $receiving_id
            ]);
        }
    }

    /**
     * Cancel an in-process receiving. Used in app/Views/receivings/receiving.php
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function postCancelReceiving(): string
    {
        $this->receiving_lib->clear_all();

        return $this->_reload();    // TODO: Hungarian Notation
    }
}
