<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;

/**
 * Supplier class
 */
class Supplier extends Person
{
    protected $table            = 'suppliers';
    protected $primaryKey       = 'person_id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'company_name',
        'account_number',
        'tax_id',
        'deleted',
        'agency_name',
        'category',
        'customer_id',
    ];

    /**
     * Determines if a given person_id is a customer
     */
    public function exists(int $person_id): bool
    {
        $builder = $this->db->table('suppliers');
        $builder->join('people', 'people.person_id = suppliers.person_id');
        $builder->where('suppliers.person_id', $person_id);

        return $builder->get()->getNumRows() === 1;    // TODO: ===
    }

    /**
     * Gets total of rows
     */
    public function get_total_rows(): int
    {
        $builder = $this->db->table('suppliers');
        $builder->where('deleted', 0);

        return $builder->countAllResults();
    }

    /**
     * Returns all the suppliers
     */
    public function get_all(int $limit = 0, int $offset = 0, ?int $category = GOODS_SUPPLIER): ResultInterface
    {
        $builder = $this->db->table('suppliers');
        $builder->join('people', 'suppliers.person_id = people.person_id');
        if ($category !== null) {
            $builder->where('category', $category);
        }
        $builder->where('deleted', 0);
        $builder->orderBy('company_name', 'asc');

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get();
    }

    /**
     * Gets information about a particular supplier
     */
    public function get_info(?int $person_id): object
    {
        $builder = $this->db->table('suppliers');
        $builder->join('people', 'people.person_id = suppliers.person_id');
        $builder->where('suppliers.person_id', $person_id);
        $query = $builder->get();

        if ($query->getNumRows() === 1) {    // TODO: ===
            return $query->getRow();
        }
        // Get empty base parent object, as $supplier_id is NOT a supplier
        $person_obj = parent::get_info(NEW_ENTRY);

        // Get all the fields from supplier table
        // Append those fields to base parent object, we have a complete empty object
        foreach ($this->db->getFieldNames('suppliers') as $field) {
            $person_obj->{$field} = '';
        }

        return $person_obj;
    }

    /**
     * Gets information about multiple suppliers
     */
    public function get_multiple_info(array $person_ids): ResultInterface
    {
        $builder = $this->db->table('suppliers');
        $builder->join('people', 'people.person_id = suppliers.person_id');
        $builder->whereIn('suppliers.person_id', $person_ids);
        $builder->orderBy('last_name', 'asc');

        return $builder->get();
    }

    /**
     * Inserts or updates a suppliers
     */
    public function save_supplier(array &$person_data, array &$supplier_data, int $supplier_id = NEW_ENTRY): bool
    {
        $success = false;

        // Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();

        if (parent::save_value($person_data, $supplier_id)) {
            $builder = $this->db->table('suppliers');
            if ($supplier_id === NEW_ENTRY || ! $this->exists($supplier_id)) {
                $supplier_data['person_id'] = $person_data['person_id'];
                $success                    = $builder->insert($supplier_data);
            } else {
                $builder->where('person_id', $supplier_id);
                $success = $builder->update($supplier_data);
            }
        }

        $this->db->transComplete();

        $success &= $this->db->transStatus();

        return $success;
    }

    /**
     * Deletes one supplier
     *
     * @param mixed|null $supplier_id
     */
    public function delete($supplier_id = null, bool $purge = false): bool
    {
        $builder = $this->db->table('suppliers');
        $builder->where('person_id', $supplier_id);

        return $builder->update(['deleted' => 1]);
    }

    /**
     * Deletes a list of suppliers
     */
    public function delete_list(array $person_ids): bool
    {
        $builder = $this->db->table('suppliers');
        $builder->whereIn('person_id', $person_ids);

        return $builder->update(['deleted' => 1]);
    }

    /**
     * Get search suggestions to find suppliers
     */
    public function get_search_suggestions(string $search, int $limit = 25, bool $unique = false): array    // TODO: Parent is looking for the 2nd parameter to be an int
    {
        $suggestions = [];

        $builder = $this->db->table('suppliers');
        $builder->join('people', 'suppliers.person_id = people.person_id');
        $builder->where('deleted', 0);
        $builder->like('company_name', $search);
        $builder->orderBy('company_name', 'asc');

        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = ['value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name];
        }

        $builder = $this->db->table('suppliers');
        $builder->join('people', 'suppliers.person_id = people.person_id');
        $builder->where('deleted', 0);
        $builder->distinct();
        $builder->like('agency_name', $search);
        $builder->where('agency_name IS NOT NULL');
        $builder->orderBy('agency_name', 'asc');

        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = ['value' => $row->person_id, 'label' => $row->agency_name];
        }

        $builder = $this->db->table('suppliers');
        $builder->join('people', 'suppliers.person_id = people.person_id');
        $builder->groupStart();
        $builder->like('first_name', $search);
        $builder->orLike('last_name', $search);
        $builder->orLike('CONCAT(first_name, " ", last_name)', $search);
        $builder->groupEnd();
        $builder->where('deleted', 0);
        $builder->orderBy('last_name', 'asc');

        foreach ($builder->get()->getResult() as $row) {
            $suggestions[] = ['value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name];
        }

        if (! $unique) {
            $builder = $this->db->table('suppliers');
            $builder->join('people', 'suppliers.person_id = people.person_id');
            $builder->where('deleted', 0);
            $builder->like('email', $search);
            $builder->orderBy('email', 'asc');

            foreach ($builder->get()->getResult() as $row) {
                $suggestions[] = ['value' => $row->person_id, 'label' => $row->email];
            }

            $builder = $this->db->table('suppliers');
            $builder->join('people', 'suppliers.person_id = people.person_id');
            $builder->where('deleted', 0);
            $builder->like('phone_number', $search);
            $builder->orderBy('phone_number', 'asc');

            foreach ($builder->get()->getResult() as $row) {
                $suggestions[] = ['value' => $row->person_id, 'label' => $row->phone_number];
            }

            $builder = $this->db->table('suppliers');
            $builder->join('people', 'suppliers.person_id = people.person_id');
            $builder->where('deleted', 0);
            $builder->like('account_number', $search);
            $builder->orderBy('account_number', 'asc');

            foreach ($builder->get()->getResult() as $row) {
                $suggestions[] = ['value' => $row->person_id, 'label' => $row->account_number];
            }
        }

        // Only return $limit suggestions
        if (count($suggestions) > $limit) {    // TODO: this can be replaced with return count($suggestions) > $limit ? array_slice($suggestions, 0, $limit) : $suggestions
            $suggestions = array_slice($suggestions, 0, $limit);
        }

        return $suggestions;
    }

    /**
     * Returns a supplier name formatted for UI display.
     */
    public function getDisplayName(object $supplier, bool $includeRole = false): string
    {
        $name = trim((string) ($supplier->first_name ?? '') . ' ' . (string) ($supplier->last_name ?? ''));

        if ($name === '') {
            $name = trim((string) ($supplier->company_name ?? ''));
        }

        if ($name === '') {
            $name = trim((string) ($supplier->agency_name ?? ''));
        }

        if (! $includeRole || ! in_array((int) ($supplier->category ?? 0), [LAND_OWNER_SUPPLIER, TENANT_SUPPLIER], true)) {
            return $name;
        }

        $roleLabel = $this->get_category_name((int) $supplier->category);

        return $name === '' ? $roleLabel : $name . ' - ' . $roleLabel;
    }

    /**
     * Returns role-aware supplier autocomplete suggestions for loan adjustments.
     */
    public function getLoanAdjustmentSuggestions(string $search, int $limit = 25): array
    {
        $suggestions = [];
        $seen        = [];
        $fetchLimit  = max($limit * 4, 50);

        foreach ($this->get_search_suggestions($search, $fetchLimit, true) as $suggestion) {
            $supplier_id = (int) ($suggestion['value'] ?? 0);

            if ($supplier_id <= 0 || isset($seen[$supplier_id])) {
                continue;
            }

            $supplier_info = $this->get_info($supplier_id);

            if (empty($supplier_info->person_id) || (int) ($supplier_info->deleted ?? 0) === DELETED) {
                continue;
            }

            $suggestions[] = [
                'value' => $supplier_id,
                'label' => $this->getDisplayName($supplier_info, true),
            ];
            $seen[$supplier_id] = true;

            if (count($suggestions) >= $limit) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Gets rows
     */
    public function get_found_rows(string $search): int
    {
        return $this->search($search, 0, 0, 'last_name', 'asc', true);
    }

    /**
     * Perform a search on suppliers
     */
    public function search(string $search, ?int $rows = 25, ?int $limit_from = 0, ?string $sort = 'last_name', ?string $order = 'asc', ?bool $count_only = false)
    {
        // Set default values on null
        $rows ??= 25;
        $limit_from ??= 0;
        $sort ??= 'last_name';
        $order ??= 'asc';
        $count_only ??= false;

        $builder = $this->db->table('suppliers AS suppliers');

        // get_found_rows case
        if ($count_only) {
            $builder->select('COUNT(suppliers.person_id) as count');
        }

        $builder->join('people', 'suppliers.person_id = people.person_id');
        $builder->groupStart();
        $builder->like('first_name', $search);
        $builder->orLike('last_name', $search);
        $builder->orLike('company_name', $search);
        $builder->orLike('agency_name', $search);
        $builder->orLike('email', $search);
        $builder->orLike('phone_number', $search);
        $builder->orLike('account_number', $search);
        $builder->orLike('CONCAT(first_name, " ", last_name)', $search);    // TODO: According to PHPStorm, this line down to the return is repeated in Customer.php and Employee.php... perhaps refactoring a method in a library could be helpful?
        $builder->groupEnd();
        $builder->where('deleted', 0);

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
     * Return supplier categories
     */
    public function get_categories(): array
    {
        return [
            GOODS_SUPPLIER      => lang('Suppliers.goods'),
            COST_SUPPLIER       => lang('Suppliers.cost'),
            LAND_OWNER_SUPPLIER => lang('Suppliers.land_owner'),
            TENANT_SUPPLIER     => lang('Suppliers.tenant'),
        ];
    }

    /**
     * Return a category name given its id.
     *
     * @param int $supplier_type Constant representing the type of supplier.
     *
     * @return string Language string for the given supplier type.
     */
    public function get_category_name(int $supplier_type): string
    {
        switch ($supplier_type) {
            case COST_SUPPLIER:
                return lang('Suppliers.cost');

            case LAND_OWNER_SUPPLIER:
                return lang('Suppliers.land_owner');

            case TENANT_SUPPLIER:
                return lang('Suppliers.tenant');

            case GOODS_SUPPLIER:
            default:
                return lang('Suppliers.goods');
        }
    }

    /**
     * Links a customer to a supplier by setting the customer_id field.
     */
    public function link_customer(int $supplier_id, int $customer_id): bool
    {
        $builder = $this->db->table('suppliers');
        $builder->where('person_id', $supplier_id);

        return $builder->update(['customer_id' => $customer_id]);
    }

    /**
     * Gets the linked customer_id for a supplier, if any.
     */
    public function get_linked_customer_id(int $supplier_id): ?int
    {
        $builder = $this->db->table('suppliers');
        $builder->select('customer_id');
        $builder->where('person_id', $supplier_id);

        $row = $builder->get()->getRow();

        return ($row && ! empty($row->customer_id)) ? (int) $row->customer_id : null;
    }

    /**
     * Gets the supplier linked to a customer account, if any.
     */
    public function get_info_by_customer_id(int $customer_id): ?object
    {
        $builder = $this->db->table('suppliers');
        $builder->select('person_id');
        $builder->where('customer_id', $customer_id);
        $builder->where('deleted', 0);

        $row = $builder->get()->getRow();

        return $row ? $this->get_info((int) $row->person_id) : null;
    }

    /**
     * Ensures landowner/tenant suppliers always have a linked customer account.
     * Returns the linked customer_id when available.
     */
    public function ensure_auto_linked_customer(int $supplier_id): ?int
    {
        $supplier_info = $this->get_info($supplier_id);

        if (empty($supplier_info->person_id) || (int) ($supplier_info->deleted ?? 0) === DELETED) {
            return null;
        }

        if (! empty($supplier_info->customer_id)) {
            return (int) $supplier_info->customer_id;
        }

        $category = (int) ($supplier_info->category ?? 0);
        if (! in_array($category, [LAND_OWNER_SUPPLIER, TENANT_SUPPLIER], true)) {
            return null;
        }

        $customer = model(Customer::class);
        if (! $customer->exists($supplier_id)) {
            $created = $customer->create_for_person($supplier_id, (string) ($supplier_info->company_name ?? ''));
            if (! $created) {
                return null;
            }
        }

        if (! $this->link_customer($supplier_id, $supplier_id)) {
            return null;
        }

        return $supplier_id;
    }

    /**
     * Returns all lunas accessible to the given supplier.
     */
    public function get_lunas(int $supplier_id): array
    {
        $supplier_info = $this->get_info($supplier_id);

        if (empty($supplier_info->person_id) || (int) ($supplier_info->deleted ?? 0) === DELETED) {
            return [];
        }

        $luna = model(Luna::class);

        return match ((int) ($supplier_info->category ?? 0)) {
            LAND_OWNER_SUPPLIER => $luna->get_lunas_for_landowner($supplier_id),
            TENANT_SUPPLIER     => $luna->get_lunas_for_tenant($supplier_id),
            default             => [],
        };
    }
}
