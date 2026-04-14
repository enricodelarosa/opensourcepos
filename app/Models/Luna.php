<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class Luna extends Model
{
    protected $table            = 'lunas';
    protected $primaryKey       = 'luna_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'area_name',
        'barangay',
        'landowner_id',
        'tenant_id',
        'deleted',
    ];

    /**
     * Returns all active lunas for a landowner with the assigned tenant name, if any.
     */
    public function get_lunas_for_landowner(int $landowner_id): array
    {
        $builder = $this->db->table('lunas');
        $builder->select([
            'lunas.luna_id',
            'lunas.area_name',
            'lunas.barangay',
            'tenant_suppliers.person_id AS tenant_id',
            'tenant_people.first_name AS tenant_first_name',
            'tenant_people.last_name AS tenant_last_name',
            'TRIM(CONCAT(COALESCE(tenant_people.first_name, ""), " ", COALESCE(tenant_people.last_name, ""))) AS tenant_name',
            'harvests.last_harvest_at',
        ]);
        $this->addHarvestJoin($builder);
        $builder->join('suppliers AS tenant_suppliers', 'tenant_suppliers.person_id = lunas.tenant_id AND tenant_suppliers.deleted = 0', 'left');
        $builder->join('people AS tenant_people', 'tenant_people.person_id = tenant_suppliers.person_id', 'left');
        $builder->where('lunas.landowner_id', $landowner_id);
        $builder->where('lunas.deleted', 0);
        $builder->orderBy('lunas.area_name', 'asc');
        $builder->orderBy('lunas.barangay', 'asc');

        return $this->appendHarvestMetadata($builder->get()->getResultArray());
    }

    /**
     * Returns all active lunas assigned to a tenant.
     */
    public function get_lunas_for_tenant(int $tenant_id): array
    {
        $builder = $this->db->table('lunas');
        $builder->select([
            'lunas.luna_id',
            'lunas.area_name',
            'lunas.barangay',
            'landowner_suppliers.person_id AS landowner_id',
            'landowner_people.first_name AS landowner_first_name',
            'landowner_people.last_name AS landowner_last_name',
            'TRIM(CONCAT(COALESCE(landowner_people.first_name, ""), " ", COALESCE(landowner_people.last_name, ""))) AS landowner_name',
            'harvests.last_harvest_at',
        ]);
        $this->addHarvestJoin($builder);
        $builder->join('suppliers AS landowner_suppliers', 'landowner_suppliers.person_id = lunas.landowner_id AND landowner_suppliers.deleted = 0');
        $builder->join('people AS landowner_people', 'landowner_people.person_id = landowner_suppliers.person_id');
        $builder->where('lunas.tenant_id', $tenant_id);
        $builder->where('lunas.deleted', 0);
        $builder->orderBy('lunas.area_name', 'asc');
        $builder->orderBy('lunas.barangay', 'asc');

        return $this->appendHarvestMetadata($builder->get()->getResultArray());
    }

    /**
     * Returns one active luna with the assigned tenant name, if any.
     */
    public function get_info(int $luna_id): ?object
    {
        $builder = $this->db->table('lunas');
        $builder->select([
            'lunas.luna_id',
            'lunas.area_name',
            'lunas.barangay',
            'lunas.landowner_id',
            'lunas.deleted',
            'tenant_suppliers.person_id AS tenant_id',
            'tenant_people.first_name AS tenant_first_name',
            'tenant_people.last_name AS tenant_last_name',
            'TRIM(CONCAT(COALESCE(tenant_people.first_name, ""), " ", COALESCE(tenant_people.last_name, ""))) AS tenant_name',
            'harvests.last_harvest_at',
        ]);
        $this->addHarvestJoin($builder);
        $builder->join('suppliers AS tenant_suppliers', 'tenant_suppliers.person_id = lunas.tenant_id AND tenant_suppliers.deleted = 0', 'left');
        $builder->join('people AS tenant_people', 'tenant_people.person_id = tenant_suppliers.person_id', 'left');
        $builder->where('lunas.luna_id', $luna_id);
        $builder->where('lunas.deleted', 0);

        $query = $builder->get();

        $luna = $query->getNumRows() === 1 ? $query->getRow() : null;

        return $this->appendHarvestMetadataToObject($luna);
    }

    /**
     * Gets the total number of luna rows matching a search.
     */
    public function get_found_rows(string $search): int
    {
        return (int) $this->search($search, 0, 0, 'luna_name', 'asc', true);
    }

    /**
     * Performs a searchable, sortable luna listing query for the manage table.
     */
    public function search(
        string $search,
        ?int $rows = 25,
        ?int $limit_from = 0,
        ?string $sort = 'luna_name',
        ?string $order = 'asc',
        ?bool $count_only = false,
    ) {
        $rows       ??= 25;
        $limit_from ??= 0;
        $sort       ??= 'luna_name';
        $order      = strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
        $count_only ??= false;

        $builder = $this->buildManageSearchBuilder($search);

        if ($count_only) {
            $builder->select('COUNT(DISTINCT ' . $this->db->prefixTable('lunas') . '.luna_id) AS count', false);

            return (int) ($builder->get()->getRow()->count ?? 0);
        }

        $builder->select($this->getManageSelectFields(), false);
        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }

    /**
     * Returns a single luna row with manage-table metadata.
     */
    public function get_manage_info(int $luna_id): ?object
    {
        $builder = $this->buildManageSearchBuilder('');
        $builder->select($this->getManageSelectFields(), false);
        $builder->where('lunas.luna_id', $luna_id);
        $builder->limit(1);

        $query = $builder->get();

        return $query->getNumRows() === 1 ? $query->getRow() : null;
    }

    /**
     * Returns receiving purchase history and totals for a single luna.
     *
     * @return array{rows: array<int, array<string, float|int|string>>, totals: array<string, float|int>}
     */
    public function get_purchase_summary(int $luna_id): array
    {
        $quantity_expression = $this->getReceivingQuantityExpression('receivings_items');
        $total_expression    = $this->getReceivingTotalExpression('receivings_items');

        $builder = $this->db->table('receivings AS receivings');
        $builder->select([
            'receivings.receiving_id',
            'receivings.receiving_time',
            $this->getDisplayNameExpression('suppliers', 'supplier_people') . ' AS supplier_name',
            'SUM(' . $quantity_expression . ') AS kilos',
            'SUM(' . $total_expression . ') AS total_amount',
            'CASE WHEN SUM(' . $quantity_expression . ') = 0 THEN 0 ELSE SUM(' . $total_expression . ') / SUM(' . $quantity_expression . ') END AS price_per_kilo',
        ]);
        $builder->join('receivings_items AS receivings_items', 'receivings_items.receiving_id = receivings.receiving_id');
        $builder->join('suppliers AS suppliers', 'suppliers.person_id = receivings.supplier_id', 'left');
        $builder->join('people AS supplier_people', 'supplier_people.person_id = suppliers.person_id', 'left');
        $builder->where('receivings.luna_id', $luna_id);
        $builder->groupBy('receivings.receiving_id');
        $builder->orderBy('receivings.receiving_time', 'desc');
        $builder->orderBy('receivings.receiving_id', 'desc');

        $rows = array_map(static fn (array $row): array => [
            'receiving_id'   => (int) $row['receiving_id'],
            'receiving_time' => (string) $row['receiving_time'],
            'supplier_name'  => trim((string) ($row['supplier_name'] ?? '')),
            'kilos'          => (float) ($row['kilos'] ?? 0),
            'price_per_kilo' => (float) ($row['price_per_kilo'] ?? 0),
            'total_amount'   => (float) ($row['total_amount'] ?? 0),
        ], $builder->get()->getResultArray());

        $total_kilos  = array_reduce($rows, static fn (float $carry, array $row): float => $carry + (float) $row['kilos'], 0.0);
        $total_amount = array_reduce($rows, static fn (float $carry, array $row): float => $carry + (float) $row['total_amount'], 0.0);
        $harvest_count = count($rows);

        return [
            'rows'   => $rows,
            'totals' => [
                'purchase_count' => $harvest_count,
                'total_kilos'    => $total_kilos,
                'average_kilos'  => $harvest_count > 0 ? $total_kilos / $harvest_count : 0,
                'price_per_kilo' => $total_kilos > 0 ? $total_amount / $total_kilos : 0,
                'total_amount'   => $total_amount,
            ],
        ];
    }

    /**
     * Inserts or updates a luna.
     */
    public function save_luna(array $data, int $luna_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('lunas');

        if ($luna_id === NEW_ENTRY || ! $this->exists($luna_id)) {
            return $builder->insert($data);
        }

        $builder->where('luna_id', $luna_id);

        return $builder->update($data);
    }

    /**
     * Soft deletes a luna.
     */
    public function delete_luna(int $luna_id): bool
    {
        $builder = $this->db->table('lunas');
        $builder->where('luna_id', $luna_id);

        return $builder->update(['deleted' => 1]);
    }

    /**
     * Determines if a luna exists and is active.
     */
    public function exists(int $luna_id): bool
    {
        $builder = $this->db->table('lunas');
        $builder->where('luna_id', $luna_id);
        $builder->where('deleted', 0);

        return $builder->countAllResults() === 1;
    }

    private function addHarvestJoin(BaseBuilder $builder): void
    {
        $builder->join(
            '(' . $this->getHarvestSubquery() . ') AS harvests',
            'harvests.luna_id = ' . $this->db->prefixTable('lunas') . '.luna_id',
            'left',
            false,
        );
    }

    private function getHarvestSubquery(): string
    {
        $builder = $this->db->table('receivings');
        $builder->select('luna_id, MAX(receiving_time) AS last_harvest_at');
        $builder->where('luna_id IS NOT NULL', null, false);
        $builder->groupBy('luna_id');

        return $builder->getCompiledSelect();
    }

    private function buildManageSearchBuilder(string $search): BaseBuilder
    {
        $builder = $this->db->table('lunas');
        $builder->where('lunas.deleted', 0);

        $this->applyManageJoins($builder);

        $search = trim($search);
        $lunas_table = $this->db->prefixTable('lunas');

        if ($search !== '') {
            $builder->groupStart();
            $builder->like('lunas.area_name', $search);
            $builder->orLike('lunas.barangay', $search);
            $builder->orLike('CONCAT(COALESCE(' . $lunas_table . '.area_name, ""), " ", COALESCE(' . $lunas_table . '.barangay, ""))', $search);
            $builder->orLike('CONCAT(COALESCE(landowner_people.first_name, ""), " ", COALESCE(landowner_people.last_name, ""))', $search);
            $builder->orLike('landowner_suppliers.company_name', $search);
            $builder->orLike('landowner_suppliers.agency_name', $search);
            $builder->orLike('CONCAT(COALESCE(tenant_people.first_name, ""), " ", COALESCE(tenant_people.last_name, ""))', $search);
            $builder->orLike('tenant_suppliers.company_name', $search);
            $builder->orLike('tenant_suppliers.agency_name', $search);
            $builder->groupEnd();
        }

        return $builder;
    }

    private function applyManageJoins(BaseBuilder $builder): void
    {
        $builder->join('suppliers AS landowner_suppliers', 'landowner_suppliers.person_id = lunas.landowner_id AND landowner_suppliers.deleted = 0', 'inner');
        $builder->join('people AS landowner_people', 'landowner_people.person_id = landowner_suppliers.person_id', 'left');
        $builder->join('customers AS landowner_customers', 'landowner_customers.person_id = landowner_suppliers.person_id AND landowner_customers.deleted = 0', 'left');
        $builder->join('suppliers AS tenant_suppliers', 'tenant_suppliers.person_id = lunas.tenant_id AND tenant_suppliers.deleted = 0', 'left');
        $builder->join('people AS tenant_people', 'tenant_people.person_id = tenant_suppliers.person_id', 'left');
        $builder->join('customers AS tenant_customers', 'tenant_customers.person_id = tenant_suppliers.person_id AND tenant_customers.deleted = 0', 'left');
        $this->addHarvestJoin($builder);

        $yield_subquery = $this->getYieldStatsSubquery();
        $loan_subquery = $this->getLoanBalanceSubquery();

        $builder->join(
            '(' . $yield_subquery . ') AS yield_stats',
            'yield_stats.luna_id = ' . $this->db->prefixTable('lunas') . '.luna_id',
            'left',
            false,
        );

        $builder->join(
            '(' . $loan_subquery . ') AS landowner_loans',
            'landowner_loans.customer_id = COALESCE(landowner_suppliers.customer_id, landowner_customers.person_id)'
            . ' AND landowner_loans.luna_id = ' . $this->db->prefixTable('lunas') . '.luna_id',
            'left',
            false,
        );
        $builder->join(
            '(' . $loan_subquery . ') AS tenant_loans',
            'tenant_loans.customer_id = COALESCE(tenant_suppliers.customer_id, tenant_customers.person_id)'
            . ' AND tenant_loans.luna_id = ' . $this->db->prefixTable('lunas') . '.luna_id',
            'left',
            false,
        );
    }

    /**
     * @return string
     */
    private function getManageSelectFields(): string
    {
        $lunas_table           = $this->db->prefixTable('lunas');
        $luna_label_expression = $this->getLunaLabelExpression($lunas_table);

        return implode(', ', [
            $lunas_table . '.luna_id',
            $lunas_table . '.area_name',
            $lunas_table . '.barangay',
            $lunas_table . '.landowner_id',
            $lunas_table . '.tenant_id',
            $luna_label_expression . ' AS luna_name',
            $this->getDisplayNameExpression('landowner_suppliers', 'landowner_people') . ' AS landowner_name',
            'COALESCE(landowner_loans.balance, 0) AS landowner_loan',
            $this->getDisplayNameExpression('tenant_suppliers', 'tenant_people') . ' AS tenant_name',
            'COALESCE(tenant_loans.balance, 0) AS tenant_loan',
            '(COALESCE(landowner_loans.balance, 0) + COALESCE(tenant_loans.balance, 0)) AS total_loan',
            'COALESCE(yield_stats.total_kilos, 0) AS total_kilo_yield',
            'COALESCE(yield_stats.average_kilos, 0) AS average_kilo_yield',
            'harvests.last_harvest_at AS last_harvest_at',
            'CASE WHEN harvests.last_harvest_at IS NULL THEN NULL ELSE DATE_ADD(harvests.last_harvest_at, INTERVAL 3 MONTH) END AS next_expected_harvest_at',
        ]);
    }

    private function getYieldStatsSubquery(): string
    {
        $quantity_expression = $this->getReceivingQuantityExpression('receivings_items');

        $builder = $this->db->table('receivings AS receivings');
        $builder->select([
            'receivings.luna_id',
            'SUM(' . $quantity_expression . ') AS total_kilos',
            'COUNT(DISTINCT receivings.receiving_id) AS harvest_count',
            'CASE WHEN COUNT(DISTINCT receivings.receiving_id) = 0 THEN 0 ELSE SUM(' . $quantity_expression . ') / COUNT(DISTINCT receivings.receiving_id) END AS average_kilos',
        ]);
        $builder->join('receivings_items AS receivings_items', 'receivings_items.receiving_id = receivings.receiving_id');
        $builder->where('receivings.luna_id IS NOT NULL', null, false);
        $builder->groupBy('receivings.luna_id');

        return $builder->getCompiledSelect();
    }

    private function getLoanBalanceSubquery(): string
    {
        $builder = $this->db->table('customer_loans');
        $builder->select([
            'customer_id',
            'luna_id',
            'SUM(loan_amount) AS balance',
        ]);
        $builder->groupBy(['customer_id', 'luna_id']);

        return $builder->getCompiledSelect();
    }

    private function getLunaLabelExpression(string $tableAlias): string
    {
        return 'TRIM(CONCAT(COALESCE(' . $tableAlias . '.area_name, ""), '
            . 'CASE WHEN ' . $tableAlias . '.barangay IS NULL OR ' . $tableAlias . '.barangay = "" '
            . 'THEN "" ELSE CONCAT(" (", ' . $tableAlias . '.barangay, ")") END))';
    }

    private function getDisplayNameExpression(string $supplierAlias, string $peopleAlias): string
    {
        return 'COALESCE('
            . 'NULLIF(TRIM(CONCAT(COALESCE(' . $peopleAlias . '.first_name, ""), " ", COALESCE(' . $peopleAlias . '.last_name, ""))), ""), '
            . 'NULLIF(' . $supplierAlias . '.company_name, ""), '
            . 'NULLIF(' . $supplierAlias . '.agency_name, "")'
            . ')';
    }

    private function getReceivingQuantityExpression(string $itemsAlias): string
    {
        return '(CASE WHEN ' . $itemsAlias . '.receiving_quantity = 0 '
            . 'THEN ' . $itemsAlias . '.quantity_purchased '
            . 'ELSE ' . $itemsAlias . '.quantity_purchased * ' . $itemsAlias . '.receiving_quantity END)';
    }

    private function getReceivingTotalExpression(string $itemsAlias): string
    {
        $quantity_expression = $this->getReceivingQuantityExpression($itemsAlias);

        return '(CASE WHEN ' . $itemsAlias . '.discount_type = ' . PERCENT
            . ' THEN ' . $itemsAlias . '.item_unit_price * ' . $quantity_expression
            . ' - ' . $itemsAlias . '.item_unit_price * ' . $quantity_expression
            . ' * ' . $itemsAlias . '.discount / 100'
            . ' ELSE ' . $itemsAlias . '.item_unit_price * ' . $quantity_expression
            . ' - ' . $itemsAlias . '.discount END)';
    }

    private function appendHarvestMetadata(array $lunas): array
    {
        return array_map(fn (array $luna): array => $this->appendHarvestMetadataToArray($luna), $lunas);
    }

    private function appendHarvestMetadataToArray(array $luna): array
    {
        $nextExpectedHarvestAt = $this->calculateNextExpectedHarvestAt($luna['last_harvest_at'] ?? null);

        $luna['last_harvest_date']          = $this->formatHarvestDate($luna['last_harvest_at'] ?? null);
        $luna['next_expected_harvest_date'] = $this->formatHarvestDate($nextExpectedHarvestAt);

        return $luna;
    }

    private function appendHarvestMetadataToObject(?object $luna): ?object
    {
        if ($luna === null) {
            return null;
        }

        $nextExpectedHarvestAt            = $this->calculateNextExpectedHarvestAt($luna->last_harvest_at ?? null);
        $luna->last_harvest_date          = $this->formatHarvestDate($luna->last_harvest_at ?? null);
        $luna->next_expected_harvest_date = $this->formatHarvestDate($nextExpectedHarvestAt);

        return $luna;
    }

    private function calculateNextExpectedHarvestAt(?string $lastHarvestAt): ?string
    {
        if (empty($lastHarvestAt)) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+3 months', strtotime($lastHarvestAt)));
    }

    private function formatHarvestDate(?string $harvestAt): ?string
    {
        if (empty($harvestAt)) {
            return null;
        }

        helper('locale');

        return to_date(strtotime($harvestAt));
    }
}
