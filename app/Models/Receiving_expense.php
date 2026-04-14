<?php

namespace App\Models;

use CodeIgniter\Model;

class Receiving_expense extends Model
{
    protected $table            = 'receiving_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'receiving_id',
        'description',
        'amount',
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
            ];
        }

        return $normalized;
    }

    private function normalizeExpenseRow(array $row): array
    {
        return [
            'description' => trim((string) ($row['description'] ?? '')),
            'amount'      => round(max(0, (float) ($row['amount'] ?? 0)), 2),
        ];
    }
}
