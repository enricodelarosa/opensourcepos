<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\OSPOS;
use stdClass;

class Cash_movement extends Model
{
    protected $table            = 'cash_movements';
    protected $primaryKey       = 'movement_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'movement_time',
        'amount',
        'description',
        'employee_id',
        'deleted',
    ];

    public function get_info(int $movement_id): object
    {
        if (! $this->db->tableExists('cash_movements')) {
            return $this->emptyObject();
        }

        $builder = $this->db->table('cash_movements AS cash_movements');
        $builder->select('cash_movements.*, people.first_name AS employee_first_name, people.last_name AS employee_last_name');
        $builder->join('people AS people', 'people.person_id = cash_movements.employee_id', 'LEFT');
        $builder->where('movement_id', $movement_id);

        $query = $builder->get();
        if ($query->getNumRows() === 1) {
            return $query->getRow();
        }

        return $this->emptyObject();
    }

    public function save_value(array &$data, int $movement_id = NEW_ENTRY): bool
    {
        if (! $this->db->tableExists('cash_movements')) {
            return false;
        }

        if ($movement_id === NEW_ENTRY) {
            if ($this->db->table('cash_movements')->insert($data)) {
                $data['movement_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder = $this->db->table('cash_movements');
        $builder->where('movement_id', $movement_id);

        return $builder->update($data);
    }

    public function get_cash_total_for_period(string $start_date, string $end_date, bool $use_time_range = false): float
    {
        if (! $this->db->tableExists('cash_movements')) {
            return 0.0;
        }

        $config  = config(OSPOS::class)->settings;
        $builder = $this->db->table('cash_movements');
        $builder->selectSum('amount', 'total');
        $builder->where('deleted', 0);

        if (! $use_time_range && empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(movement_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date));
        } else {
            $builder->where('movement_time BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date)));
        }

        return (float) ($builder->get()->getRow()->total ?? 0);
    }

    public function get_cash_rows_for_period(string $start_date, string $end_date, bool $use_time_range = false): array
    {
        if (! $this->db->tableExists('cash_movements')) {
            return [];
        }

        $config  = config(OSPOS::class)->settings;
        $builder = $this->db->table('cash_movements AS cash_movements');
        $builder->select([
            'cash_movements.description',
            'cash_movements.amount',
            'cash_movements.movement_time AS trans_time',
        ]);
        $builder->where('cash_movements.deleted', 0);

        if (! $use_time_range && empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(cash_movements.movement_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($start_date) . ' AND ' . $this->db->escape($end_date));
        } else {
            $builder->where('cash_movements.movement_time BETWEEN ' . $this->db->escape(rawurldecode($start_date)) . ' AND ' . $this->db->escape(rawurldecode($end_date)));
        }

        $builder->orderBy('cash_movements.movement_time', 'ASC');

        return array_map(static fn (array $row): array => [
            'particular' => trim((string) ($row['description'] ?? '')) !== '' ? (string) $row['description'] : lang('Cash_movements.default_description'),
            'amount'     => (float) $row['amount'],
            'trans_time' => $row['trans_time'],
        ], $builder->get()->getResultArray());
    }

    private function emptyObject(): object
    {
        $obj                = new stdClass();
        $obj->movement_id   = NEW_ENTRY;
        $obj->movement_time = date('Y-m-d H:i:s');
        $obj->amount        = '0.00';
        $obj->description   = '';
        $obj->employee_id   = null;
        $obj->deleted       = 0;

        return $obj;
    }
}
