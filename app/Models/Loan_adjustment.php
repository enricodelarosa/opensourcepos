<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\OSPOS;
use stdClass;

/**
 * Loan_adjustment model
 *
 * Tracks manual adjustments to a supplier's loan balance that also affect cash.
 * Positive loan_amount = loan increased (business gave cash out to supplier).
 * Negative loan_amount = loan decreased (business received cash in from supplier).
 *
 * Each saved adjustment creates a corresponding ospos_customer_loans row so the
 * existing get_loan_balance() calculation stays accurate. The loan_id column stores
 * that reference so it can be cleaned up on delete.
 */
class Loan_adjustment extends Model
{
    protected $table            = 'loan_adjustments';
    protected $primaryKey       = 'adjustment_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'adjustment_time',
        'supplier_id',
        'customer_id',
        'loan_amount',
        'comment',
        'employee_id',
        'loan_id',
        'deleted',
    ];

    /**
     * Returns a single adjustment joined with supplier name and employee name.
     */
    public function get_info(int $adjustment_id): object
    {
        $builder = $this->db->table('loan_adjustments AS loan_adjustments');
        $builder->select('loan_adjustments.*, suppliers.category AS supplier_category, customer_loans.luna_id, lunas.area_name, lunas.barangay, TRIM(CONCAT(COALESCE(landowner_people.first_name, ""), " ", COALESCE(landowner_people.last_name, ""))) AS landowner_name, sup_people.first_name AS supplier_first_name, sup_people.last_name AS supplier_last_name, emp_people.first_name AS employee_first_name, emp_people.last_name AS employee_last_name');
        $builder->join('suppliers AS suppliers', 'suppliers.person_id = loan_adjustments.supplier_id', 'LEFT');
        $builder->join('people AS sup_people', 'sup_people.person_id = loan_adjustments.supplier_id', 'LEFT');
        $builder->join('people AS emp_people', 'emp_people.person_id = loan_adjustments.employee_id', 'LEFT');
        $builder->join('customer_loans AS customer_loans', 'customer_loans.loan_id = loan_adjustments.loan_id', 'LEFT');
        $builder->join('lunas AS lunas', 'lunas.luna_id = customer_loans.luna_id', 'LEFT');
        $builder->join('people AS landowner_people', 'landowner_people.person_id = lunas.landowner_id', 'LEFT');
        $builder->where('adjustment_id', $adjustment_id);

        $query = $builder->get();
        if ($query->getNumRows() === 1) {
            return $query->getRow();
        }

        return $this->_empty_object();
    }

    /**
     * Searches adjustments for the bootstrap table with optional date and deleted filters.
     */
    public function search(string $search, array $filters, ?int $rows = 0, ?int $limit_from = 0, ?string $sort = 'adjustment_id', ?string $order = 'desc', ?bool $count_only = false)
    {
        if ($rows === null) {
            $rows = 0;
        }
        if ($limit_from === null) {
            $limit_from = 0;
        }
        if ($sort === null) {
            $sort = 'adjustment_id';
        }
        if ($order === null) {
            $order = 'desc';
        }
        if ($count_only === null) {
            $count_only = false;
        }

        $config = config(OSPOS::class)->settings;

        $builder = $this->db->table('loan_adjustments AS loan_adjustments');
        $builder->join('suppliers AS suppliers', 'suppliers.person_id = loan_adjustments.supplier_id', 'LEFT');
        $builder->join('people AS sup_people', 'sup_people.person_id = loan_adjustments.supplier_id', 'LEFT');
        $builder->join('people AS emp_people', 'emp_people.person_id = loan_adjustments.employee_id', 'LEFT');
        $builder->join('customer_loans AS customer_loans', 'customer_loans.loan_id = loan_adjustments.loan_id', 'LEFT');
        $builder->join('lunas AS lunas', 'lunas.luna_id = customer_loans.luna_id', 'LEFT');
        $builder->join('people AS landowner_people', 'landowner_people.person_id = lunas.landowner_id', 'LEFT');

        if ($count_only) {
            $builder->select('COUNT(loan_adjustments.adjustment_id) AS count');
        } else {
            $builder->select('
                loan_adjustments.adjustment_id,
                loan_adjustments.adjustment_time,
                loan_adjustments.supplier_id,
                loan_adjustments.customer_id,
                loan_adjustments.loan_amount,
                loan_adjustments.comment,
                loan_adjustments.employee_id,
                loan_adjustments.deleted,
                suppliers.category AS supplier_category,
                customer_loans.luna_id,
                lunas.area_name,
                lunas.barangay,
                TRIM(CONCAT(COALESCE(landowner_people.first_name, ""), COALESCE(CONCAT(" ", landowner_people.last_name), ""))) AS landowner_name,
                sup_people.first_name AS supplier_first_name,
                sup_people.last_name AS supplier_last_name,
                emp_people.first_name AS employee_first_name,
                emp_people.last_name AS employee_last_name
            ');
        }

        // Search across supplier name and comment
        $builder->groupStart();
        $builder->like('sup_people.first_name', $search);
        $builder->orLike('sup_people.last_name', $search);
        $builder->orLike('CONCAT(sup_people.first_name, " ", sup_people.last_name)', $search);
        $builder->orLike('lunas.area_name', $search);
        $builder->orLike('lunas.barangay', $search);
        $builder->orLike('landowner_people.first_name', $search);
        $builder->orLike('landowner_people.last_name', $search);
        $builder->orLike('CONCAT(landowner_people.first_name, " ", landowner_people.last_name)', $search);
        $builder->orLike('loan_adjustments.comment', $search);
        $builder->groupEnd();

        $builder->where('loan_adjustments.deleted', $filters['is_deleted'] ? 1 : 0);

        if (empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(loan_adjustments.adjustment_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
        } else {
            $builder->where('loan_adjustments.adjustment_time BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date'])));
        }

        if ($count_only) {
            return $builder->get()->getRow()->count;
        }

        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }

    /**
     * Returns the total row count for the given search/filters (used by bootstrap table).
     */
    public function get_found_rows(string $search, array $filters): int
    {
        return $this->search($search, $filters, 0, 0, 'adjustment_id', 'desc', true);
    }

    /**
     * Inserts or updates an adjustment row.
     * Does NOT create the customer_loans entry — that is handled by the controller.
     */
    public function save_value(array &$data, int $adjustment_id = NEW_ENTRY): bool
    {
        if ($adjustment_id === NEW_ENTRY) {
            if ($this->db->table('loan_adjustments')->insert($data)) {
                $data['adjustment_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder = $this->db->table('loan_adjustments');
        $builder->where('adjustment_id', $adjustment_id);

        return $builder->update($data);
    }

    /**
     * Soft-deletes a list of adjustments and returns an array of their loan_ids
     * so the controller can clean up the corresponding customer_loans rows.
     */
    public function delete_list(array $adjustment_ids): array
    {
        // Collect loan_ids before deleting so the controller can reverse them
        $builder = $this->db->table('loan_adjustments');
        $builder->whereIn('adjustment_id', $adjustment_ids);
        $rows = $builder->get()->getResult();

        $loan_ids = array_filter(array_column($rows, 'loan_id'));

        $this->db->transStart();
        $builder = $this->db->table('loan_adjustments');
        $builder->whereIn('adjustment_id', $adjustment_ids);
        $success = $builder->update(['deleted' => 1]);
        $this->db->transComplete();

        return $success ? $loan_ids : [];
    }

    /**
     * Returns the SUM of loan_amount for non-deleted adjustments within a date range.
     * Used by the Cashups controller to adjust closed_amount_cash.
     *   Positive sum = net cash went OUT  → subtract from cashup cash
     *   Negative sum = net cash came IN   → subtract a negative = add to cashup cash
     */
    public function get_cash_total_for_period(string $start_date, string $end_date): float
    {
        $config  = config(OSPOS::class)->settings;
        $builder = $this->db->table('loan_adjustments');
        $builder->selectSum('loan_amount', 'total');
        $builder->where('deleted', 0);

        if (empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(adjustment_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date));
        } else {
            $builder->where('adjustment_time BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date)));
        }

        $result = $builder->get()->getRow();

        return (float) ($result->total ?? 0);
    }

    /**
     * Returns individual cash-adjustment rows with optional luna labels for cash summary.
     */
    public function get_cash_rows_for_period(string $start_date, string $end_date): array
    {
        $config  = config(OSPOS::class)->settings;
        $builder = $this->db->table('loan_adjustments AS loan_adjustments');
        $builder->select([
            'sup_people.first_name AS supplier_first_name',
            'sup_people.last_name AS supplier_last_name',
            'lunas.area_name',
            'lunas.barangay',
            'loan_adjustments.loan_amount AS amount',
            'loan_adjustments.adjustment_time AS trans_time',
        ]);
        $builder->join('people AS sup_people', 'sup_people.person_id = loan_adjustments.supplier_id', 'LEFT');
        $builder->join('customer_loans AS customer_loans', 'customer_loans.loan_id = loan_adjustments.loan_id', 'LEFT');
        $builder->join('lunas AS lunas', 'lunas.luna_id = customer_loans.luna_id', 'LEFT');
        $builder->where('loan_adjustments.deleted', 0);

        if (empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(loan_adjustments.adjustment_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date));
        } else {
            $builder->where('loan_adjustments.adjustment_time BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date)));
        }

        $builder->orderBy('loan_adjustments.adjustment_time', 'ASC');

        return array_map(static function (array $row): array {
            $particular = trim(($row['supplier_first_name'] ?? '') . ' ' . ($row['supplier_last_name'] ?? ''));

            if (! empty($row['area_name'])) {
                $particular .= ' - ' . $row['area_name'];

                if (! empty($row['barangay'])) {
                    $particular .= ' (' . $row['barangay'] . ')';
                }
            }

            return [
                'particular' => $particular,
                'amount'     => (float) $row['amount'],
                'trans_time' => $row['trans_time'],
            ];
        }, $builder->get()->getResultArray());
    }

    private function _empty_object(): object
    {
        $obj                  = new stdClass();
        $obj->adjustment_id   = NEW_ENTRY;
        $obj->adjustment_time = date('Y-m-d H:i:s');
        $obj->supplier_id     = null;
        $obj->customer_id     = null;
        $obj->loan_amount     = '0.00';
        $obj->comment         = '';
        $obj->employee_id     = null;
        $obj->loan_id         = null;
        $obj->deleted         = 0;

        return $obj;
    }
}
