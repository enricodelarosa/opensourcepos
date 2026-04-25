<?php

namespace App\Controllers;

use App\Models\Copra_report;

class Copra_reports extends Secure_Controller
{
    private Copra_report $copraReport;

    public function __construct()
    {
        parent::__construct('copra_reports');

        $this->copraReport = model(Copra_report::class);
    }

    public function getIndex(): string
    {
        $period = (string) ($this->request->getGet('period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'daily');
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        $range = (string) ($this->request->getGet('range', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
        if (! array_key_exists($range, $this->quickRanges())) {
            $range = '';
        }

        $startDate = (string) ($this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-01'));
        $endDate   = (string) ($this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d'));

        if ($range !== '') {
            [$startDate, $endDate] = $this->quickRanges()[$range];
        }

        if (! $this->isValidDate($startDate)) {
            $startDate = date('Y-m-01');
        }

        if (! $this->isValidDate($endDate)) {
            $endDate = date('Y-m-d');
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $summary = $this->copraReport->getSummary($period, $startDate, $endDate);

        return view('copra_reports/manage', [
            'period'        => $period,
            'range'         => $range,
            'quick_ranges'  => array_keys($this->quickRanges()),
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'summary_rows'  => $summary['rows'],
            'totals'        => $summary['totals'],
            'purchase_rows' => $this->copraReport->getPurchaseRows($startDate, $endDate),
        ]);
    }

    private function isValidDate(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        $parsed = date_create_from_format('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function quickRanges(): array
    {
        $today = new \DateTimeImmutable('today');

        return [
            'yesterday'   => [
                $today->modify('-1 day')->format('Y-m-d'),
                $today->modify('-1 day')->format('Y-m-d'),
            ],
            'last_7_days' => [
                $today->modify('-6 days')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'this_month'  => [
                $today->modify('first day of this month')->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            'last_month'  => [
                $today->modify('first day of last month')->format('Y-m-d'),
                $today->modify('last day of last month')->format('Y-m-d'),
            ],
        ];
    }
}
