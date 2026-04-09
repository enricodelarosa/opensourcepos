<?php
/**
 * @var object $luna_info
 * @var array  $purchase_rows
 * @var array  $purchase_totals
 */
?>

<?php
$luna_name   = trim((string) ($luna_info->luna_name ?? ''));
$tenant_name = trim((string) ($luna_info->tenant_name ?? ''));
?>

<div class="container-fluid">
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12">
            <h4 style="margin-top: 0; margin-bottom: 5px;"><?= esc($luna_name) ?></h4>
            <div><strong><?= esc(lang('Lunas.land_owner')) ?>:</strong> <?= esc((string) ($luna_info->landowner_name ?? '')) ?></div>
            <div><strong><?= esc(lang('Lunas.tenant')) ?>:</strong> <?= esc($tenant_name !== '' ? $tenant_name : lang('Lunas.no_tenant_assigned')) ?></div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 15px;">
        <div class="col-sm-3 col-xs-12">
            <div class="well well-sm" style="margin-bottom: 10px;">
                <div><strong><?= esc(lang('Lunas.total_kilos')) ?></strong></div>
                <div><?= to_quantity_decimals((string) ($purchase_totals['total_kilos'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-12">
            <div class="well well-sm" style="margin-bottom: 10px;">
                <div><strong><?= esc(lang('Lunas.average_kilo_yield')) ?></strong></div>
                <div><?= to_quantity_decimals((string) ($purchase_totals['average_kilos'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-12">
            <div class="well well-sm" style="margin-bottom: 10px;">
                <div><strong><?= esc(lang('Lunas.price_per_kilo')) ?></strong></div>
                <div><?= to_currency((string) ($purchase_totals['price_per_kilo'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-sm-3 col-xs-12">
            <div class="well well-sm" style="margin-bottom: 10px;">
                <div><strong><?= esc(lang('Lunas.total_amount')) ?></strong></div>
                <div><?= to_currency((string) ($purchase_totals['total_amount'] ?? 0)) ?></div>
            </div>
        </div>
    </div>

    <div class="panel panel-default" style="margin-bottom: 0;">
        <div class="panel-heading">
            <strong><?= esc(lang('Lunas.related_purchases')) ?></strong>
            <span class="pull-right"><?= esc(lang('Lunas.purchase_count')) ?>: <?= (int) ($purchase_totals['purchase_count'] ?? 0) ?></span>
        </div>
        <div class="panel-body" style="padding-bottom: 0;">
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th><?= esc(lang('Common.date')) ?></th>
                            <th><?= esc(lang('Lunas.purchase')) ?></th>
                            <th><?= esc(lang('Suppliers.supplier')) ?></th>
                            <th style="text-align: right;"><?= esc(lang('Lunas.number_of_kilos')) ?></th>
                            <th style="text-align: right;"><?= esc(lang('Lunas.price_per_kilo')) ?></th>
                            <th style="text-align: right;"><?= esc(lang('Lunas.total_amount')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchase_rows)) { ?>
                            <tr>
                                <td colspan="6" style="text-align: center;"><?= esc(lang('Lunas.no_purchases')) ?></td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($purchase_rows as $row) { ?>
                                <tr>
                                    <td><?= esc(to_datetime(strtotime((string) $row['receiving_time']))) ?></td>
                                    <td><?= esc('Purchase #' . (int) $row['receiving_id']) ?></td>
                                    <td><?= esc((string) ($row['supplier_name'] !== '' ? $row['supplier_name'] : lang('Suppliers.supplier'))) ?></td>
                                    <td style="text-align: right;"><?= to_quantity_decimals((string) $row['kilos']) ?></td>
                                    <td style="text-align: right;"><?= to_currency((string) $row['price_per_kilo']) ?></td>
                                    <td style="text-align: right;"><?= to_currency((string) $row['total_amount']) ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
