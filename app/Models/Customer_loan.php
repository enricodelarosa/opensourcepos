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
    protected $table = 'customer_loans';
    protected $primaryKey = 'loan_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'customer_id',
        'sale_id',
        'receiving_id',
        'loan_amount',
        'transaction_time',
        'comment'
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
     * Records a new loan entry (positive amount = new debt, negative = payment)
     */
    public function record_loan(int $customer_id, float $amount, ?int $sale_id = null, ?int $receiving_id = null, string $comment = ''): bool
    {
        $data = [
            'customer_id'      => $customer_id,
            'sale_id'          => $sale_id,
            'receiving_id'     => $receiving_id,
            'loan_amount'      => $amount,
            'transaction_time' => date('Y-m-d H:i:s'),
            'comment'          => $comment
        ];

        $builder = $this->db->table('customer_loans');
        return $builder->insert($data);
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

        return $query->getNumRows() == 1 ? $query->getRow() : null;
    }
}
