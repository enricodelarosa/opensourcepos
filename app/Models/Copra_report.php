<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class Copra_report extends Model
{
    private const COPRA_ITEM_ID = 4;

    public function getSummary(string $period, string $startDate, string $endDate): array
    {
        $rows = $this->getGroupedRows($period, $startDate, $endDate);

        return [
            'rows'   => $rows,
            'totals' => $this->getTotals($startDate, $endDate),
        ];
    }

    public function getPurchaseRows(string $startDate, string $endDate): array
    {
        $builder = $this->baseCopraBuilder($startDate, $endDate);
        $builder->select([
            'receivings.receiving_id',
            'receivings.receiving_time',
            "TRIM(CONCAT(COALESCE(people.first_name, ''), ' ', COALESCE(people.last_name, ''))) AS supplier_name",
            'lunas.area_name',
            'lunas.barangay',
            $this->kilosExpression() . ' AS total_kilos',
            $this->amountExpression() . ' AS total_amount',
            $this->averagePriceExpression() . ' AS avg_price_per_kilo',
        ], false);
        $builder->groupBy([
            'receivings.receiving_id',
            'receivings.receiving_time',
            'people.first_name',
            'people.last_name',
            'lunas.area_name',
            'lunas.barangay',
        ]);
        $builder->orderBy('receivings.receiving_time', 'DESC');

        return $builder->get()->getResultArray();
    }

    private function getGroupedRows(string $period, string $startDate, string $endDate): array
    {
        $builder = $this->baseCopraBuilder($startDate, $endDate);
        $periodSelect = $this->periodSelect($period);

        $builder->select([
            $periodSelect['select'],
            $this->kilosExpression() . ' AS total_kilos',
            $this->amountExpression() . ' AS total_amount',
            $this->averagePriceExpression() . ' AS avg_price_per_kilo',
            'COUNT(DISTINCT receivings.receiving_id) AS purchase_count',
            'MIN(receivings.receiving_time) AS first_purchase_time',
            'MAX(receivings.receiving_time) AS last_purchase_time',
        ], false);
        $builder->groupBy($periodSelect['group_by'], false);
        $builder->orderBy('first_purchase_time', 'ASC');

        return $builder->get()->getResultArray();
    }

    private function getTotals(string $startDate, string $endDate): array
    {
        $builder = $this->baseCopraBuilder($startDate, $endDate);
        $builder->select([
            $this->kilosExpression() . ' AS total_kilos',
            $this->amountExpression() . ' AS total_amount',
            $this->averagePriceExpression() . ' AS avg_price_per_kilo',
            'COUNT(DISTINCT receivings.receiving_id) AS purchase_count',
        ], false);

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total_kilos'        => (float) ($row['total_kilos'] ?? 0),
            'total_amount'       => (float) ($row['total_amount'] ?? 0),
            'avg_price_per_kilo' => (float) ($row['avg_price_per_kilo'] ?? 0),
            'purchase_count'     => (int) ($row['purchase_count'] ?? 0),
        ];
    }

    private function baseCopraBuilder(string $startDate, string $endDate): BaseBuilder
    {
        $builder = $this->db->table('receivings AS receivings');
        $builder->join('receivings_items AS receivings_items', 'receivings_items.receiving_id = receivings.receiving_id');
        $builder->join('people AS people', 'people.person_id = receivings.supplier_id', 'LEFT');
        $builder->join('lunas AS lunas', 'lunas.luna_id = receivings.luna_id', 'LEFT');
        $builder->where('receivings_items.item_id', self::COPRA_ITEM_ID);
        $builder->where('receivings.receiving_time >=', $startDate . ' 00:00:00');
        $builder->where('receivings.receiving_time <=', $endDate . ' 23:59:59');

        return $builder;
    }

    private function periodSelect(string $period): array
    {
        return match ($period) {
            'weekly' => [
                'select'   => "YEARWEEK(receivings.receiving_time, 1) AS period_key, CONCAT(DATE_FORMAT(DATE_SUB(DATE(receivings.receiving_time), INTERVAL WEEKDAY(receivings.receiving_time) DAY), '%Y-%m-%d'), ' - ', DATE_FORMAT(DATE_ADD(DATE_SUB(DATE(receivings.receiving_time), INTERVAL WEEKDAY(receivings.receiving_time) DAY), INTERVAL 6 DAY), '%Y-%m-%d')) AS period_label",
                'group_by' => 'YEARWEEK(receivings.receiving_time, 1)',
            ],
            'monthly' => [
                'select'   => "DATE_FORMAT(receivings.receiving_time, '%Y-%m') AS period_key, DATE_FORMAT(receivings.receiving_time, '%Y-%m') AS period_label",
                'group_by' => "DATE_FORMAT(receivings.receiving_time, '%Y-%m')",
            ],
            default => [
                'select'   => 'DATE(receivings.receiving_time) AS period_key, DATE(receivings.receiving_time) AS period_label',
                'group_by' => 'DATE(receivings.receiving_time)',
            ],
        };
    }

    private function kilosExpression(): string
    {
        return 'SUM(receivings_items.quantity_purchased)';
    }

    private function amountExpression(): string
    {
        return 'SUM(receivings_items.quantity_purchased * receivings_items.item_unit_price)';
    }

    private function averagePriceExpression(): string
    {
        return 'SUM(receivings_items.quantity_purchased * receivings_items.item_unit_price) / NULLIF(SUM(receivings_items.quantity_purchased), 0)';
    }
}
