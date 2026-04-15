<?php

namespace App\Models;

use CodeIgniter\Model;

class Receiving_loan_snapshot extends Model
{
    protected $table            = 'receiving_loan_snapshots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'receiving_id',
        'supplier_id',
        'customer_id',
        'luna_id',
        'loan_balance_before',
        'loan_deduction_amount',
        'loan_balance_after',
    ];

    public function is_available(): bool
    {
        return $this->db->tableExists($this->table);
    }

    public function record_snapshot(
        int $receiving_id,
        int $supplier_id,
        int $customer_id,
        ?int $luna_id,
        float $loan_balance_before,
        float $loan_deduction_amount,
        float $loan_balance_after,
    ): bool {
        if (! $this->is_available()) {
            return false;
        }

        $data = [
            'receiving_id'          => $receiving_id,
            'supplier_id'           => $supplier_id,
            'customer_id'           => $customer_id,
            'luna_id'               => $luna_id,
            'loan_balance_before'   => $loan_balance_before,
            'loan_deduction_amount' => $loan_deduction_amount,
            'loan_balance_after'    => $loan_balance_after,
        ];

        $builder  = $this->db->table($this->table);
        $existing = $builder->select('id')
            ->where('receiving_id', $receiving_id)
            ->where('supplier_id', $supplier_id)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return $this->db->table($this->table)
                ->where('id', $existing['id'])
                ->update($data);
        }

        return $this->db->table($this->table)->insert($data);
    }

    public function get_report_context(array $receiving_ids): array
    {
        $receiving_ids = array_values(array_unique(array_filter(array_map('intval', $receiving_ids))));
        if ($receiving_ids === []) {
            return [];
        }

        $receiving_meta = $this->get_receiving_metadata($receiving_ids);
        $expense_rows   = $this->get_expense_rows($receiving_ids);
        $context        = [];

        foreach ($receiving_meta as $receiving_id => $meta) {
            $context[$receiving_id] = [
                'luna_label'              => $meta['luna_label'],
                'landowner_name'          => $meta['landowner_name'],
                'tenant_name'             => $meta['tenant_name'],
                'landowner_share_percent' => $meta['landowner_share_percent'],
                'tenant_share_percent'    => $meta['tenant_share_percent'],
                'expenses'                => $expense_rows[$receiving_id] ?? [],
                'rows'                    => [],
            ];
        }

        foreach ($this->get_payment_rows($receiving_ids) as $payment_row) {
            $receiving_id = (int) $payment_row['receiving_id'];
            $supplier_id  = (int) $payment_row['supplier_id'];

            if (! isset($context[$receiving_id])) {
                continue;
            }

            $context[$receiving_id]['rows'][$supplier_id] ??= $this->build_context_row($receiving_meta[$receiving_id] ?? [], $supplier_id);
            $context[$receiving_id]['rows'][$supplier_id]['supplier_name'] = $this->resolve_supplier_name(
                $receiving_meta[$receiving_id] ?? [],
                $supplier_id,
                (string) $payment_row['supplier_name'],
            );
            $context[$receiving_id]['rows'][$supplier_id]['cash_amount'] = (float) $payment_row['cash_amount'];
        }

        $snapshot_rows = $this->get_snapshot_rows($receiving_ids);
        $snapshot_keys = [];

        foreach ($snapshot_rows as $snapshot_row) {
            $receiving_id = (int) $snapshot_row['receiving_id'];
            $supplier_id  = (int) $snapshot_row['supplier_id'];

            if (! isset($context[$receiving_id])) {
                continue;
            }

            $snapshot_keys[$receiving_id . ':' . $supplier_id] = true;

            $context[$receiving_id]['rows'][$supplier_id] ??= $this->build_context_row($receiving_meta[$receiving_id] ?? [], $supplier_id);
            $context[$receiving_id]['rows'][$supplier_id]['supplier_name'] = $this->resolve_supplier_name(
                $receiving_meta[$receiving_id] ?? [],
                $supplier_id,
                (string) $snapshot_row['supplier_name'],
            );
            $context[$receiving_id]['rows'][$supplier_id]['loan_balance_before']   = (float) $snapshot_row['loan_balance_before'];
            $context[$receiving_id]['rows'][$supplier_id]['loan_deduction_amount'] = (float) $snapshot_row['loan_deduction_amount'];
            $context[$receiving_id]['rows'][$supplier_id]['loan_balance_after']    = (float) $snapshot_row['loan_balance_after'];
        }

        foreach ($this->get_fallback_loan_rows($receiving_ids, $snapshot_keys) as $fallback_row) {
            $receiving_id = (int) $fallback_row['receiving_id'];
            $supplier_id  = (int) $fallback_row['supplier_id'];

            if (! isset($context[$receiving_id])) {
                continue;
            }

            $context[$receiving_id]['rows'][$supplier_id] ??= $this->build_context_row($receiving_meta[$receiving_id] ?? [], $supplier_id);
            $context[$receiving_id]['rows'][$supplier_id]['supplier_name'] = $this->resolve_supplier_name(
                $receiving_meta[$receiving_id] ?? [],
                $supplier_id,
                (string) $fallback_row['supplier_name'],
            );
            $context[$receiving_id]['rows'][$supplier_id]['loan_balance_before']   = (float) $fallback_row['loan_balance_before'];
            $context[$receiving_id]['rows'][$supplier_id]['loan_deduction_amount'] = (float) $fallback_row['loan_deduction_amount'];
            $context[$receiving_id]['rows'][$supplier_id]['loan_balance_after']    = (float) $fallback_row['loan_balance_after'];
        }

        foreach ($context as $receiving_id => $receiving_context) {
            if ($receiving_context['rows'] === []) {
                continue;
            }

            $rows = array_values($receiving_context['rows']);
            usort($rows, fn (array $left, array $right): int => $this->get_party_sort_weight($receiving_meta[$receiving_id] ?? [], (int) $left['supplier_id']) <=> $this->get_party_sort_weight($receiving_meta[$receiving_id] ?? [], (int) $right['supplier_id'])
                    ?: strcmp($left['supplier_name'], $right['supplier_name']));

            $context[$receiving_id]['rows'] = $rows;
        }

        return $context;
    }

    private function get_receiving_metadata(array $receiving_ids): array
    {
        $builder = $this->db->table('receivings AS receivings');
        $select  = [
            'receivings.receiving_id',
            'receivings.supplier_id',
            'receivings.luna_id',
            'lunas.tenant_id',
            'lunas.area_name',
            'lunas.barangay',
            "CONCAT(COALESCE(primary_people.first_name, ''), ' ', COALESCE(primary_people.last_name, '')) AS landowner_name",
            "CONCAT(COALESCE(tenant_people.first_name, ''), ' ', COALESCE(tenant_people.last_name, '')) AS tenant_name",
        ];

        if ($this->hasCopraSplitContext()) {
            $select[] = 'receivings.landowner_share_percent';
            $select[] = 'receivings.tenant_share_percent';
        }

        $builder->select($select);
        $builder->join('lunas', 'lunas.luna_id = receivings.luna_id', 'left');
        $builder->join('people AS primary_people', 'primary_people.person_id = receivings.supplier_id', 'left');
        $builder->join('people AS tenant_people', 'tenant_people.person_id = lunas.tenant_id', 'left');
        $builder->whereIn('receivings.receiving_id', $receiving_ids);

        $metadata = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $metadata[(int) $row['receiving_id']] = [
                'supplier_id'             => (int) $row['supplier_id'],
                'tenant_id'               => empty($row['tenant_id']) ? null : (int) $row['tenant_id'],
                'luna_id'                 => empty($row['luna_id']) ? null : (int) $row['luna_id'],
                'landowner_name'          => trim((string) $row['landowner_name']),
                'tenant_name'             => trim((string) $row['tenant_name']),
                'landowner_share_percent' => isset($row['landowner_share_percent']) && $row['landowner_share_percent'] !== ''
                    ? (float) $row['landowner_share_percent']
                    : null,
                'tenant_share_percent' => isset($row['tenant_share_percent']) && $row['tenant_share_percent'] !== ''
                    ? (float) $row['tenant_share_percent']
                    : null,
                'luna_label' => $this->build_luna_label((string) ($row['area_name'] ?? ''), (string) ($row['barangay'] ?? '')),
            ];
        }

        return $metadata;
    }

    private function get_expense_rows(array $receiving_ids): array
    {
        if (! $this->db->tableExists('receiving_expenses')) {
            return [];
        }

        $builder = $this->db->table('receiving_expenses');
        $builder->select([
            'receiving_id',
            'description',
            'amount',
            'add_back_to',
        ]);
        $builder->whereIn('receiving_id', $receiving_ids);
        $builder->orderBy('receiving_id', 'asc');
        $builder->orderBy('sort_order', 'asc');
        $builder->orderBy('id', 'asc');

        $rows = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $rows[(int) $row['receiving_id']][] = [
                'description' => trim((string) ($row['description'] ?? '')),
                'amount'      => round(max(0, (float) ($row['amount'] ?? 0)), 2),
                'add_back_to' => ($row['add_back_to'] ?? Receiving_expense::ADD_BACK_TO_TENANT) === Receiving_expense::ADD_BACK_TO_SUPPLIER
                    ? Receiving_expense::ADD_BACK_TO_LANDOWNER
                    : Receiving_expense::ADD_BACK_TO_TENANT,
            ];
        }

        return $rows;
    }

    private function get_payment_rows(array $receiving_ids): array
    {
        if (! $this->db->tableExists('receiving_payments')) {
            return [];
        }

        $builder = $this->db->table('receiving_payments AS receiving_payments');
        $builder->select([
            'receiving_payments.receiving_id',
            'receiving_payments.supplier_id',
            'receiving_payments.cash_amount',
            "CONCAT(COALESCE(payment_people.first_name, ''), ' ', COALESCE(payment_people.last_name, '')) AS supplier_name",
        ]);
        $builder->join('people AS payment_people', 'payment_people.person_id = receiving_payments.supplier_id', 'left');
        $builder->whereIn('receiving_payments.receiving_id', $receiving_ids);

        return $builder->get()->getResultArray();
    }

    private function get_snapshot_rows(array $receiving_ids): array
    {
        if (! $this->is_available()) {
            return [];
        }

        $builder = $this->db->table($this->table . ' AS receiving_loan_snapshots');
        $builder->select([
            'receiving_loan_snapshots.receiving_id',
            'receiving_loan_snapshots.supplier_id',
            'receiving_loan_snapshots.loan_balance_before',
            'receiving_loan_snapshots.loan_deduction_amount',
            'receiving_loan_snapshots.loan_balance_after',
            "CONCAT(COALESCE(snapshot_people.first_name, ''), ' ', COALESCE(snapshot_people.last_name, '')) AS supplier_name",
        ]);
        $builder->join('people AS snapshot_people', 'snapshot_people.person_id = receiving_loan_snapshots.supplier_id', 'left');
        $builder->whereIn('receiving_loan_snapshots.receiving_id', $receiving_ids);

        return $builder->get()->getResultArray();
    }

    private function get_fallback_loan_rows(array $receiving_ids, array $snapshot_keys): array
    {
        $builder = $this->db->table('customer_loans AS customer_loans');
        $builder->select([
            'customer_loans.loan_id',
            'customer_loans.receiving_id',
            'customer_loans.customer_id',
            'customer_loans.luna_id',
            'customer_loans.loan_amount',
            'customer_loans.transaction_time',
            'suppliers.person_id AS supplier_id',
            "CONCAT(COALESCE(supplier_people.first_name, ''), ' ', COALESCE(supplier_people.last_name, '')) AS supplier_name",
        ]);
        $builder->join('suppliers', 'suppliers.customer_id = customer_loans.customer_id AND suppliers.deleted = 0', 'left');
        $builder->join('people AS supplier_people', 'supplier_people.person_id = suppliers.person_id', 'left');
        $builder->whereIn('customer_loans.receiving_id', $receiving_ids);

        $fallback_rows = [];

        foreach ($builder->get()->getResultArray() as $row) {
            if (empty($row['supplier_id'])) {
                continue;
            }

            $snapshot_key = (int) $row['receiving_id'] . ':' . (int) $row['supplier_id'];
            if (isset($snapshot_keys[$snapshot_key])) {
                continue;
            }

            $loan_amount = (float) $row['loan_amount'];
            $before      = $this->get_balance_before_loan_entry(
                (int) $row['customer_id'],
                empty($row['luna_id']) ? null : (int) $row['luna_id'],
                (string) $row['transaction_time'],
                (int) $row['loan_id'],
            );

            $fallback_rows[] = [
                'receiving_id'          => (int) $row['receiving_id'],
                'supplier_id'           => (int) $row['supplier_id'],
                'supplier_name'         => trim((string) $row['supplier_name']),
                'loan_balance_before'   => $before,
                'loan_deduction_amount' => max(0.0, -$loan_amount),
                'loan_balance_after'    => $before + $loan_amount,
            ];
        }

        return $fallback_rows;
    }

    private function get_balance_before_loan_entry(int $customer_id, ?int $luna_id, string $transaction_time, int $loan_id): float
    {
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'balance');
        $builder->where('customer_id', $customer_id);

        if ($luna_id === null) {
            $builder->where('luna_id IS NULL', null, false);
        } else {
            $builder->where('luna_id', $luna_id);
        }

        $builder->groupStart()
            ->where('transaction_time <', $transaction_time)
            ->orGroupStart()
            ->where('transaction_time', $transaction_time)
            ->where('loan_id <', $loan_id)
            ->groupEnd()
            ->groupEnd();

        $result = $builder->get()->getRowArray();

        return (float) ($result['balance'] ?? 0);
    }

    private function build_context_row(array $receiving_meta, int $supplier_id): array
    {
        return [
            'supplier_id'           => $supplier_id,
            'party_label'           => $this->get_party_label($receiving_meta, $supplier_id),
            'supplier_name'         => $this->resolve_supplier_name($receiving_meta, $supplier_id),
            'cash_amount'           => 0.0,
            'loan_balance_before'   => null,
            'loan_deduction_amount' => null,
            'loan_balance_after'    => null,
        ];
    }

    private function get_party_label(array $receiving_meta, int $supplier_id): string
    {
        if (! empty($receiving_meta['luna_id']) && $supplier_id === (int) ($receiving_meta['supplier_id'] ?? 0)) {
            return lang('Reports.landowner');
        }

        if (! empty($receiving_meta['tenant_id']) && $supplier_id === (int) $receiving_meta['tenant_id']) {
            return lang('Reports.tenant');
        }

        return lang('Reports.supplier');
    }

    private function get_party_sort_weight(array $receiving_meta, int $supplier_id): int
    {
        if ($supplier_id === (int) ($receiving_meta['supplier_id'] ?? 0)) {
            return 0;
        }

        if (! empty($receiving_meta['tenant_id']) && $supplier_id === (int) $receiving_meta['tenant_id']) {
            return 1;
        }

        return 2;
    }

    private function resolve_supplier_name(array $receiving_meta, int $supplier_id, string $fallback_name = ''): string
    {
        if ($supplier_id === (int) ($receiving_meta['supplier_id'] ?? 0) && ! empty($receiving_meta['landowner_name'])) {
            return $receiving_meta['landowner_name'];
        }

        if (! empty($receiving_meta['tenant_id']) && $supplier_id === (int) $receiving_meta['tenant_id'] && ! empty($receiving_meta['tenant_name'])) {
            return $receiving_meta['tenant_name'];
        }

        return trim($fallback_name);
    }

    private function build_luna_label(string $area_name, string $barangay): string
    {
        $label = trim($area_name);

        if ($label !== '' && trim($barangay) !== '') {
            $label .= ' (' . trim($barangay) . ')';
        }

        return $label;
    }

    private function hasCopraSplitContext(): bool
    {
        return $this->db->fieldExists('landowner_share_percent', 'receivings')
            && $this->db->fieldExists('tenant_share_percent', 'receivings');
    }
}
