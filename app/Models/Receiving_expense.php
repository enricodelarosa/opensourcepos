<?php

namespace App\Models;

use CodeIgniter\Model;

class Receiving_expense extends Model
{
    public const ADD_BACK_TO_TENANT = 'tenant';
    public const ADD_BACK_TO_LANDOWNER = 'landowner';
    public const ADD_BACK_TO_SUPPLIER = self::ADD_BACK_TO_LANDOWNER;

    protected $table            = 'receiving_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'receiving_id',
        'description',
        'amount',
        'add_back_to',
        'sort_order',
    ];

    public function get_by_receiving(int $receiving_id): array
    {
        return array_map(fn (array $row): array => $this->normalizeExpenseRow($row), $this->db->table($this->table)
            ->where('receiving_id', $receiving_id)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->getResultArray());
    }

    public function save_for_receiving(int $receiving_id, array $expenses): bool
    {
        $builder = $this->db->table($this->table);

        $this->db->transStart();

        $builder->where('receiving_id', $receiving_id)->delete();

        foreach ($this->normalize_expenses($expenses) as $index => $expense) {
            $builder->insert([
                'receiving_id' => $receiving_id,
                'description'  => $expense['description'],
                'amount'       => $expense['amount'],
                'add_back_to'  => $expense['add_back_to'],
                'sort_order'   => $index,
            ]);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function normalize_expenses(array $expenses): array
    {
        $normalized = [];

        foreach ($expenses as $expense) {
            $description = trim((string) ($expense['description'] ?? ''));
            $amount      = round(max(0, (float) ($expense['amount'] ?? 0)), 2);

            if ($description === '' || $amount <= 0) {
                continue;
            }

            $normalized[] = [
                'description' => $description,
                'amount'      => $amount,
                'add_back_to' => $this->normalizeAddBackTo($expense['add_back_to'] ?? null),
            ];
        }

        return $normalized;
    }

    private function normalizeExpenseRow(array $row): array
    {
        return [
            'description' => trim((string) ($row['description'] ?? '')),
            'amount'      => round(max(0, (float) ($row['amount'] ?? 0)), 2),
            'add_back_to' => $this->normalizeAddBackTo($row['add_back_to'] ?? null),
        ];
    }

    private function normalizeAddBackTo(mixed $value): string
    {
        return in_array($value, [self::ADD_BACK_TO_LANDOWNER, 'supplier'], true)
            ? self::ADD_BACK_TO_LANDOWNER
            : self::ADD_BACK_TO_TENANT;
    }
}
