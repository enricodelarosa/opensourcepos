<?php

namespace App\Controllers;

use App\Models\Customer_loan;
use App\Models\Luna;
use App\Models\Supplier;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Lunas extends Secure_Controller
{
    private Luna $luna;
    private Supplier $supplier;
    private Customer_loan $customer_loan;

    public function __construct()
    {
        parent::__construct('lunas');

        $this->luna          = model(Luna::class);
        $this->supplier      = model(Supplier::class);
        $this->customer_loan = model(Customer_loan::class);
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_lunas_manage_table_headers();

        return view('lunas/manage', $data);
    }

    /**
     * Gets one row for the luna manage table.
     */
    public function getRow(int $row_id): ResponseInterface
    {
        $luna_info = $this->luna->get_manage_info($row_id);

        if ($luna_info === null) {
            return $this->response->setJSON([]);
        }

        return $this->response->setJSON(get_luna_data_row($luna_info));
    }

    /**
     * Returns luna table data rows.
     */
    public function getSearch(): ResponseInterface
    {
        $search = trim((string) $this->request->getGet('search', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort   = $this->sanitizeSortColumn(luna_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'luna_name');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $lunas      = $this->luna->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->luna->get_found_rows($search);
        $data_rows  = [];

        foreach ($lunas->getResult() as $luna_row) {
            $data_rows[] = get_luna_data_row($luna_row);
        }

        return $this->response->setJSON(['total' => $total_rows, 'rows' => $data_rows]);
    }

    /**
     * Displays a summary of supplier purchases related to a luna.
     */
    public function getSummary(int $luna_id): string
    {
        $luna_info = $this->luna->get_manage_info($luna_id);

        if ($luna_info === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $purchase_summary = $this->luna->get_purchase_summary($luna_id);

        return view('lunas/summary', [
            'luna_info'         => $luna_info,
            'purchase_rows'     => $purchase_summary['rows'],
            'purchase_totals'   => $purchase_summary['totals'],
        ]);
    }

    /**
     * Displays a read-only loan details modal for a luna party.
     */
    public function getLoanDetails(int $luna_id, int $supplier_id): string
    {
        $context = $this->getLoanContext($luna_id, $supplier_id);

        return view('lunas/loan_details', [
            'luna_info'      => $context['luna_info'],
            'supplier_info'  => $context['supplier_info'],
            'customer_id'    => $context['customer_id'],
            'loan_party'     => $this->supplier->getDisplayName($context['supplier_info'], true),
            'loan_balance'   => $context['customer_id'] !== null
                ? $this->customer_loan->get_loan_balance_for_luna($context['customer_id'], $luna_id)
                : '0.00',
        ]);
    }

    /**
     * Returns paginated loan history rows for a luna party.
     */
    public function getLoanHistory(int $luna_id, int $supplier_id): ResponseInterface
    {
        $context = $this->getLoanContext($luna_id, $supplier_id);

        if ($context['customer_id'] === null) {
            return $this->response->setJSON(['total' => 0, 'rows' => []]);
        }

        $limit  = max(0, (int) $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT));
        $offset = max(0, (int) $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT));

        $history_rows = $this->customer_loan->get_history($context['customer_id'], null, null, $limit, $offset, 'desc', $luna_id);
        $total_rows   = $this->customer_loan->get_history_total($context['customer_id'], null, null, $luna_id);

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
     * @return array{luna_info: object, supplier_info: object, customer_id: ?int}
     */
    private function getLoanContext(int $luna_id, int $supplier_id): array
    {
        $luna_info = $this->luna->get_manage_info($luna_id);

        if ($luna_info === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $valid_supplier_ids = array_filter([
            (int) ($luna_info->landowner_id ?? 0),
            (int) ($luna_info->tenant_id ?? 0),
        ]);

        if (! in_array($supplier_id, $valid_supplier_ids, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $supplier_info = $this->supplier->get_info($supplier_id);

        if (empty($supplier_info->person_id) || (int) ($supplier_info->deleted ?? 0) === DELETED) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (empty($supplier_info->customer_id)) {
            $linked_customer_id = $this->supplier->ensure_auto_linked_customer($supplier_id);

            if ($linked_customer_id !== null) {
                $supplier_info = $this->supplier->get_info($supplier_id);
            }
        }

        return [
            'luna_info'     => $luna_info,
            'supplier_info' => $supplier_info,
            'customer_id'   => ! empty($supplier_info->customer_id) ? (int) $supplier_info->customer_id : null,
        ];
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
