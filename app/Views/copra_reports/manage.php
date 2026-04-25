<?php
/**
 * @var string $period
 * @var string $range
 * @var array  $quick_ranges
 * @var string $start_date
 * @var string $end_date
 * @var array  $summary_rows
 * @var array  $totals
 * @var array  $purchase_rows
 */

$trim_decimals = static function (string $number): string {
    $decimal_position = strrpos($number, '.');

    if ($decimal_position === false) {
        return $number;
    }

    $decimal_part = substr($number, $decimal_position + 1);

    if ($decimal_part === '' || ! ctype_digit($decimal_part)) {
        return $number;
    }

    return rtrim(rtrim($number, '0'), '.');
};

$format_kilos = static fn ($value): string => $trim_decimals(to_quantity_decimals((string) $value));
$format_price = static fn ($value): string => $trim_decimals(to_currency_no_money((string) $value));
$quick_range_url = static fn (string $quick_range): string => site_url('copra_reports') . '?' . http_build_query([
    'period' => $period,
    'range'  => $quick_range,
]);
$format_luna = static function (array $row): string {
    $area = trim((string) ($row['area_name'] ?? ''));

    if ($area === '') {
        return lang('Copra_reports.no_luna');
    }

    if (! empty($row['barangay'])) {
        $area .= ' (' . trim((string) $row['barangay']) . ')';
    }

    return $area;
};
?>

<?= view('partial/header') ?>

<style>
.copra-report-toolbar { margin-bottom: 16px; }
.copra-report-range-buttons { display: inline-flex; flex-wrap: wrap; gap: 6px; margin: 0 0 8px; vertical-align: middle; }
.copra-report-filter-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.copra-report-filter-row .form-group { margin-bottom: 0; }
.copra-report-summary { display: grid; grid-template-columns: repeat(4, minmax(140px, 1fr)); gap: 10px; margin-bottom: 16px; }
.copra-report-metric { border: 1px solid #ddd; padding: 10px 12px; background: #fafafa; }
.copra-report-metric-label { color: #666; font-size: 0.85em; text-transform: uppercase; }
.copra-report-metric-value { font-size: 1.35em; font-weight: bold; }
.copra-report-section-title { font-size: 1.15em; font-weight: bold; margin: 18px 0 8px; }
.copra-report-table th, .copra-report-table td { vertical-align: middle !important; }
.copra-report-table .amount, .copra-report-table .number { text-align: right; }
@media (max-width: 767px) {
    .copra-report-summary { grid-template-columns: 1fr 1fr; }
}
@media print {
    .copra-report-toolbar { display: none; }
    .copra-report-summary { grid-template-columns: repeat(4, 1fr); }
}
</style>

<div id="page_title"><?= esc(lang('Copra_reports.title')) ?></div>
<div id="page_subtitle"><?= esc(lang('Copra_reports.subtitle')) ?></div>

<div class="copra-report-toolbar print_hide">
    <div class="copra-report-range-buttons">
        <?php foreach ($quick_ranges as $quick_range): ?>
            <a
                href="<?= esc($quick_range_url($quick_range)) ?>"
                class="btn btn-sm <?= $range === $quick_range ? 'btn-info' : 'btn-default' ?>"
            >
                <?= esc(lang('Copra_reports.range_' . $quick_range)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?= form_open('copra_reports', ['method' => 'get', 'class' => 'form-inline copra-report-filter-row', 'id' => 'copra_report_filters']) ?>
        <?= form_input([
            'type'  => 'hidden',
            'name'  => 'range',
            'id'    => 'range',
            'value' => esc($range),
        ]) ?>
        <div class="form-group">
            <label for="period"><?= esc(lang('Copra_reports.period')) ?>:&nbsp;</label>
            <?= form_dropdown(
                'period',
                [
                    'daily'   => lang('Copra_reports.daily'),
                    'weekly'  => lang('Copra_reports.weekly'),
                    'monthly' => lang('Copra_reports.monthly'),
                ],
                $period,
                ['id' => 'period', 'class' => 'form-control input-sm copra-report-auto-submit'],
            ) ?>
        </div>
        <div class="form-group">
            <label for="start_date">&nbsp;<?= esc(lang('Copra_reports.start_date')) ?>:&nbsp;</label>
            <?= form_input([
                'name'  => 'start_date',
                'id'    => 'start_date',
                'type'  => 'date',
                'value' => esc($start_date),
                'class' => 'form-control input-sm copra-report-auto-submit copra-report-date-filter',
            ]) ?>
        </div>
        <div class="form-group">
            <label for="end_date">&nbsp;<?= esc(lang('Copra_reports.end_date')) ?>:&nbsp;</label>
            <?= form_input([
                'name'  => 'end_date',
                'id'    => 'end_date',
                'type'  => 'date',
                'value' => esc($end_date),
                'class' => 'form-control input-sm copra-report-auto-submit copra-report-date-filter',
            ]) ?>
        </div>
        <noscript>
            <button class="btn btn-info btn-sm" type="submit"><?= esc(lang('Copra_reports.apply')) ?></button>
        </noscript>
    <?= form_close() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filters = document.getElementById('copra_report_filters');

    if (!filters) {
        return;
    }

    filters.querySelectorAll('.copra-report-auto-submit').forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.classList.contains('copra-report-date-filter')) {
                const rangeInput = document.getElementById('range');

                if (rangeInput) {
                    rangeInput.value = '';
                }
            }

            filters.submit();
        });
    });
});
</script>

<div class="copra-report-summary">
    <div class="copra-report-metric">
        <div class="copra-report-metric-label"><?= esc(lang('Copra_reports.total_kilos')) ?></div>
        <div class="copra-report-metric-value"><?= esc($format_kilos($totals['total_kilos'] ?? 0)) ?></div>
    </div>
    <div class="copra-report-metric">
        <div class="copra-report-metric-label"><?= esc(lang('Copra_reports.average_price')) ?></div>
        <div class="copra-report-metric-value"><?= esc($format_price($totals['avg_price_per_kilo'] ?? 0)) ?>/kg</div>
    </div>
    <div class="copra-report-metric">
        <div class="copra-report-metric-label"><?= esc(lang('Copra_reports.total_amount')) ?></div>
        <div class="copra-report-metric-value"><?= to_currency((string) ($totals['total_amount'] ?? 0)) ?></div>
    </div>
    <div class="copra-report-metric">
        <div class="copra-report-metric-label"><?= esc(lang('Copra_reports.purchase_count')) ?></div>
        <div class="copra-report-metric-value"><?= (int) ($totals['purchase_count'] ?? 0) ?></div>
    </div>
</div>

<div class="copra-report-section-title"><?= esc(lang('Copra_reports.summary_by_period')) ?></div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-condensed copra-report-table">
        <thead>
            <tr>
                <th><?= esc(lang('Copra_reports.period_label')) ?></th>
                <th class="number"><?= esc(lang('Copra_reports.kilos')) ?></th>
                <th class="number"><?= esc(lang('Copra_reports.price_per_kilo')) ?></th>
                <th class="amount"><?= esc(lang('Copra_reports.total_amount')) ?></th>
                <th class="number"><?= esc(lang('Copra_reports.purchase_count')) ?></th>
                <th><?= esc(lang('Copra_reports.first_purchase')) ?></th>
                <th><?= esc(lang('Copra_reports.last_purchase')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($summary_rows)): ?>
                <tr>
                    <td colspan="7" class="text-center"><?= esc(lang('Copra_reports.no_results')) ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($summary_rows as $row): ?>
                    <tr>
                        <td><?= esc((string) $row['period_label']) ?></td>
                        <td class="number"><?= esc($format_kilos($row['total_kilos'])) ?></td>
                        <td class="number"><?= esc($format_price($row['avg_price_per_kilo'])) ?>/kg</td>
                        <td class="amount"><?= to_currency((string) $row['total_amount']) ?></td>
                        <td class="number"><?= (int) $row['purchase_count'] ?></td>
                        <td><?= esc(to_datetime(strtotime((string) $row['first_purchase_time']))) ?></td>
                        <td><?= esc(to_datetime(strtotime((string) $row['last_purchase_time']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="copra-report-section-title"><?= esc(lang('Copra_reports.purchase_details')) ?></div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-condensed copra-report-table">
        <thead>
            <tr>
                <th><?= esc(lang('Common.date')) ?></th>
                <th><?= esc(lang('Copra_reports.purchase')) ?></th>
                <th><?= esc(lang('Copra_reports.supplier')) ?></th>
                <th><?= esc(lang('Copra_reports.luna')) ?></th>
                <th class="number"><?= esc(lang('Copra_reports.kilos')) ?></th>
                <th class="number"><?= esc(lang('Copra_reports.price_per_kilo')) ?></th>
                <th class="amount"><?= esc(lang('Copra_reports.total_amount')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchase_rows)): ?>
                <tr>
                    <td colspan="7" class="text-center"><?= esc(lang('Copra_reports.no_results')) ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchase_rows as $row): ?>
                    <tr>
                        <td><?= esc(to_datetime(strtotime((string) $row['receiving_time']))) ?></td>
                        <td><?= esc('CP #' . (int) $row['receiving_id']) ?></td>
                        <td><?= esc((string) ($row['supplier_name'] !== '' ? $row['supplier_name'] : lang('Suppliers.supplier'))) ?></td>
                        <td><?= esc($format_luna($row)) ?></td>
                        <td class="number"><?= esc($format_kilos($row['total_kilos'])) ?></td>
                        <td class="number"><?= esc($format_price($row['avg_price_per_kilo'])) ?>/kg</td>
                        <td class="amount"><?= to_currency((string) $row['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= view('partial/footer') ?>
