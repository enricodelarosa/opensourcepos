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
