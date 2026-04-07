<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

/**
 * Customer_loan class
 *
 * Manages loan/credit tracking for customers who are also suppliers (e.g. copra farmers).
 * Positive loan_amount = new debt/loan (from a sale on store account)
 * Negative loan_amount = payment/deduction (from a receiving/copra purchase)
 */
class Customer_loan extends Model
{
    protected $table            = 'customer_loans';
    protected $primaryKey       = 'loan_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'customer_id',
        'sale_id',
        'receiving_id',
        'luna_id',
        'loan_amount',
        'transaction_time',
        'comment',
    ];

    /**
     * Gets the outstanding loan balance for a customer.
     * Positive balance means the customer owes money.
     */
    public function get_loan_balance(int $customer_id): string
    {
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'balance');
        $builder->where('customer_id', $customer_id);

        $result = $builder->get()->getRow();

        return $result->balance ?? '0.00';
    }

    /**
     * Gets the outstanding loan balance for a customer for a specific luna.
     */
    public function get_loan_balance_for_luna(int $customer_id, int $luna_id): string
    {
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'balance');
        $builder->where('customer_id', $customer_id);
        $builder->where('luna_id', $luna_id);

        $result = $builder->get()->getRow();

        return $result->balance ?? '0.00';
    }

    /**
     * Gets the outstanding loan balance broken down by luna, plus general advances.
     */
    public function get_loan_balance_breakdown(int $customer_id): array
    {
        $supplier = model(Supplier::class)->get_info_by_customer_id($customer_id);
        $builder  = $this->db->table('customer_loans AS customer_loans');
        $builder->select([
            'customer_loans.luna_id',
            'lunas.area_name',
            'lunas.barangay',
            'SUM(customer_loans.loan_amount) AS balance',
        ]);
        $builder->join('lunas', 'lunas.luna_id = customer_loans.luna_id', 'left');
        $builder->where('customer_loans.customer_id', $customer_id);
        $builder->where('customer_loans.luna_id IS NOT NULL', null, false);
        $builder->groupBy('customer_loans.luna_id, lunas.area_name, lunas.barangay');
        $builder->orderBy('lunas.area_name', 'asc');
        $builder->orderBy('lunas.barangay', 'asc');

        $breakdown_by_luna = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $breakdown_by_luna[(int) $row['luna_id']] = [
                'luna_id'        => (int) $row['luna_id'],
                'area_name'      => $row['area_name'],
                'barangay'       => $row['barangay'],
                'landowner_name' => null,
                'balance'        => $row['balance'] ?? '0.00',
            ];
        }

        if ($supplier !== null) {
            $luna_model     = model(Luna::class);
            $supplier_lunas = match ((int) ($supplier->category ?? 0)) {
                LAND_OWNER_SUPPLIER => $luna_model->get_lunas_for_landowner((int) $supplier->person_id),
                TENANT_SUPPLIER     => $luna_model->get_lunas_for_tenant((int) $supplier->person_id),
                default             => [],
            };

            foreach ($supplier_lunas as $luna_row) {
                $luna_id = (int) ($luna_row['luna_id'] ?? 0);

                if ($luna_id <= 0 || isset($breakdown_by_luna[$luna_id])) {
                    if (
                        $luna_id > 0
                        && isset($breakdown_by_luna[$luna_id])
                        && (int) ($supplier->category ?? 0) === TENANT_SUPPLIER
                    ) {
                        $breakdown_by_luna[$luna_id]['landowner_name'] = $luna_row['landowner_name'] ?? null;
                    }

                    continue;
                }

                $breakdown_by_luna[$luna_id] = [
                    'luna_id'        => $luna_id,
                    'area_name'      => $luna_row['area_name'] ?? null,
                    'barangay'       => $luna_row['barangay'] ?? null,
                    'landowner_name' => (int) ($supplier->category ?? 0) === TENANT_SUPPLIER ? ($luna_row['landowner_name'] ?? null) : null,
                    'balance'        => '0.00',
                ];
            }
        }

        $breakdown = array_values($breakdown_by_luna);

        usort($breakdown, static fn (array $left, array $right): int => [$left['area_name'] ?? '', $left['barangay'] ?? ''] <=> [$right['area_name'] ?? '', $right['barangay'] ?? '']);

        $general_builder = $this->db->table('customer_loans');
        $general_builder->selectSum('loan_amount', 'balance');
        $general_builder->where('customer_id', $customer_id);
        $general_builder->where('luna_id IS NULL', null, false);

        $general_row = $general_builder->get()->getRowArray();
        if (! empty($general_row) && (float) ($general_row['balance'] ?? 0) !== 0.0) {
            $breakdown[] = [
                'luna_id'        => null,
                'area_name'      => null,
                'barangay'       => null,
                'landowner_name' => null,
                'balance'        => $general_row['balance'],
            ];
        }

        return $breakdown;
    }

    /**
     * Records a new loan entry (positive amount = new debt, negative = payment).
     */
    public function record_loan(
        int $customer_id,
        float $amount,
        ?int $sale_id = null,
        ?int $receiving_id = null,
        string $comment = '',
        ?int $luna_id = null,
        ?string $transaction_time = null,
    ): bool {
        $data = [
            'customer_id'      => $customer_id,
            'sale_id'          => $sale_id,
            'receiving_id'     => $receiving_id,
            'luna_id'          => $luna_id,
            'loan_amount'      => $amount,
            'transaction_time' => $transaction_time ?? date('Y-m-d H:i:s'),
            'comment'          => $comment,
        ];

        $builder = $this->db->table('customer_loans');

        return $builder->insert($data);
    }

    /**
     * Returns the last inserted loan_id for this connection.
     */
    public function get_insert_id(): int
    {
        return (int) $this->db->insertID();
    }

    /**
     * Updates an existing loan entry in place.
     */
    public function update_loan_entry(int $loan_id, array $data): bool
    {
        $builder = $this->db->table('customer_loans');
        $builder->where('loan_id', $loan_id);

        return $builder->update($data);
    }

    /**
     * Gets all loan transactions for a customer
     */
    public function get_loans_for_customer(int $customer_id, int $limit = 0, int $offset = 0): ResultInterface
    {
        $builder = $this->db->table('customer_loans');
        $builder->where('customer_id', $customer_id);
        $builder->orderBy('transaction_time', 'desc');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get();
    }

    /**
     * Gets loan info by ID
     */
    public function get_info(int $loan_id): ?object
    {
        $builder = $this->db->table('customer_loans');
        $builder->where('loan_id', $loan_id);

        $query = $builder->get();

        return $query->getNumRows() === 1 ? $query->getRow() : null;
    }

    /**
     * Finds the manual customer_loan row that belongs to a loan adjustment.
     */
    public function find_matching_adjustment_loan(int $customer_id, float $loan_amount, string $transaction_time, string $comment): ?object
    {
        $builder = $this->db->table('customer_loans');
        $builder->where('customer_id', $customer_id);
        $builder->where('sale_id IS NULL', null, false);
        $builder->where('receiving_id IS NULL', null, false);
        $builder->where('loan_amount', $loan_amount);
        $builder->where('transaction_time', $transaction_time);
        $builder->where('comment', $comment);
        $builder->orderBy('loan_id', 'desc');
        $builder->limit(1);

        $query = $builder->get();

        return $query->getNumRows() === 1 ? $query->getRow() : null;
    }

    /**
     * Returns paginated loan history with luna and adjustment metadata.
     */
    public function get_history(
        int $customer_id,
        ?string $start_time = null,
        ?string $end_time = null,
        int $limit = 0,
        int $offset = 0,
        string $direction = 'desc',
    ): array {
        $builder = $this->db->table('customer_loans AS customer_loans');
        $builder->select([
            'customer_loans.loan_id',
            'customer_loans.transaction_time',
            'customer_loans.sale_id',
            'customer_loans.receiving_id',
            'customer_loans.luna_id',
            'customer_loans.loan_amount',
            'customer_loans.comment',
            'loan_adjustments.adjustment_id',
            'loan_adjustments.comment AS adjustment_comment',
            'lunas.area_name',
            'lunas.barangay',
        ]);
        $builder->join('loan_adjustments', 'loan_adjustments.loan_id = customer_loans.loan_id AND loan_adjustments.deleted = 0', 'left');
        $builder->join('lunas', 'lunas.luna_id = customer_loans.luna_id', 'left');
        $builder->where('customer_loans.customer_id', $customer_id);

        if ($start_time !== null && $start_time !== '') {
            $builder->where('customer_loans.transaction_time >=', $start_time);
        }

        if ($end_time !== null && $end_time !== '') {
            $builder->where('customer_loans.transaction_time <=', $end_time);
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $builder->orderBy('customer_loans.transaction_time', $direction);
        $builder->orderBy('customer_loans.loan_id', $direction);

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Returns the total number of history rows for a customer.
     */
    public function get_history_total(int $customer_id, ?string $start_time = null, ?string $end_time = null): int
    {
        $builder = $this->db->table('customer_loans');
        $builder->selectCount('loan_id', 'total');
        $builder->where('customer_id', $customer_id);

        if ($start_time !== null && $start_time !== '') {
            $builder->where('transaction_time >=', $start_time);
        }

        if ($end_time !== null && $end_time !== '') {
            $builder->where('transaction_time <=', $end_time);
        }

        $row = $builder->get()->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Returns the loan balance before a given point in time.
     */
    public function get_balance_before_datetime(int $customer_id, string $transaction_time): float
    {
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'balance');
        $builder->where('customer_id', $customer_id);
        $builder->where('transaction_time <', $transaction_time);

        $row = $builder->get()->getRowArray();

        return (float) ($row['balance'] ?? 0);
    }

    /**
     * Returns customer ids that have at least one loan history row.
     */
    public function get_customer_ids_with_history(): array
    {
        $builder = $this->db->table('customer_loans');
        $builder->distinct();
        $builder->select('customer_id');
        $builder->orderBy('customer_id', 'asc');

        return array_map(static fn (array $row): int => (int) $row['customer_id'], $builder->get()->getResultArray());
    }
}
