<?php

namespace App\Models;

use CodeIgniter\Model;

class Deletion_log extends Model
{
    protected $table            = 'deletion_logs';
    protected $primaryKey       = 'log_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'entity_type',
        'entity_id',
        'entity_label',
        'deleted_by',
        'deleted_at',
        'deleted_data',
    ];

    public function record(string $entity_type, int $entity_id, string $entity_label, array $deleted_data, ?int $deleted_by): bool
    {
        $encoded_data = json_encode($deleted_data);

        if ($encoded_data === false) {
            return false;
        }

        return $this->insert([
            'entity_type'  => $entity_type,
            'entity_id'    => $entity_id,
            'entity_label' => $entity_label,
            'deleted_by'   => $deleted_by,
            'deleted_at'   => date('Y-m-d H:i:s'),
            'deleted_data' => $encoded_data,
        ], false) !== false;
    }
}
