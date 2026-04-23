<?php
/**
 * @var string $date
 * @var array  $sessions
 * @var array  $config
 */
?>

<?= view('partial/header') ?>
<?= view('partial/print_receipt', ['print_after_sale' => false, 'selected_printer' => 'takings_printer']) ?>

<style>
.ledger-section { margin-bottom: 30px; }
.ledger-table { width: 100%; border-collapse: collapse; font-size: inherit; }
.ledger-table th, .ledger-table td { border: 1px solid #ccc; padding: 4px 8px; }
.ledger-table th { background: #f5f5f5; text-align: center; font-weight: bold; }
.ledger-table th.action-header { vertical-align: middle; }
.ledger-table .column-action { display: inline-flex; align-items: center; justify-content: center; min-width: 72px; }
.ledger-table .column-action .glyphicon { margin-right: 4px; }
.ledger-table td.amount { text-align: right; }
.ledger-table .particular-note { color: #777; }
.ledger-table .copra-session-summary { color: #555; text-align: right; }
.ledger-table tr.cash-rem td { font-weight: bold; background: #fafafa; }
.ledger-table tr.totals td { font-weight: bold; border-top: 2px solid #333; background: #f0f0f0; }
.ledger-table tr.cash-ending td { font-weight: bold; }
.session-title { font-size: 1.2em; font-weight: bold; margin: 15px 0 8px; text-align: center; text-transform: uppercase; }
.session-times { margin-bottom: 8px; text-align: center; color: #666; }
.no-sessions { color: #999; text-align: center; padding: 30px; }

@media print {
    #cash-summary-print-area {
        font-size: 75%;
    }

    .ledger-section {
        break-inside: auto;
        page-break-inside: auto;
    }

    .session-title,
    .session-times {
        break-after: avoid;
        page-break-after: avoid;
    }

    .ledger-table thead {
        display: table-header-group;
    }

    .ledger-table tfoot {
        display: table-row-group;
    }

    .ledger-table tr,
    .ledger-table td,
    .ledger-table th {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .ledger-table .action-row {
        display: none;
    }
}
</style>

<script type="text/javascript">
    $(document).ready(function() {
        $('#date-picker').on('change', function() {
            window.location = 'cash_summary?date=' + $(this).val();
        });

        table_support.handle_submit = function(resource, response) {
            if (!response.success) {
                $.notify(response.message, {type: 'danger'});
                return false;
            }

            $.notify(response.message, {type: 'success'});
            window.location = 'cash_summary?date=' + encodeURIComponent($('#date-picker').val());
            return false;
        };

        dialog_support.init('button.modal-dlg');
    });
</script>

<div id="title_bar" class="print_hide btn-toolbar">
    <button onclick="javascript:printdoc()" class="btn btn-info btn-sm pull-right">
        <span class="glyphicon glyphicon-print">&nbsp;</span><?= lang('Common.print') ?>
    </button>
    <div class="pull-left form-inline">
        <label class="control-label"><?= lang('Common.date') ?>:&nbsp;</label>
        <?= form_input([
            'name'  => 'date-picker',
            'id'    => 'date-picker',
            'type'  => 'date',
            'value' => esc($date),
            'class' => 'form-control input-sm',
        ]) ?>
    </div>
</div>

<?php
$action_header = static function () use ($date): void { ?>
    <tr class="action-row print_hide">
        <th></th>
        <th class="action-header">
            <button class="btn btn-info btn-sm modal-dlg column-action" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= 'cash_movements/view?date=' . rawurlencode($date) ?>" title="<?= lang('Cash_movements.new') ?>">
                <span class="glyphicon glyphicon-plus"></span><?= lang('Cash_summary.add_cash_in') ?>
            </button>
        </th>
        <th class="action-header">
            <button class="btn btn-info btn-sm modal-dlg column-action" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= 'loan_adjustments/view?date=' . rawurlencode($date) ?>" title="<?= lang('Loan_adjustments.new') ?>">
                <span class="glyphicon glyphicon-plus"></span><?= lang('Cash_summary.add_cash_advance') ?>
            </button>
        </th>
        <th></th>
        <th class="action-header">
            <button class="btn btn-info btn-sm modal-dlg column-action" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= 'expenses/view?date=' . rawurlencode($date) . '&payment_type=cash' ?>" title="<?= lang('Expenses.new') ?>">
                <span class="glyphicon glyphicon-tags"></span><?= lang('Cash_summary.add_operating_expense') ?>
            </button>
        </th>
    </tr>
<?php };

$column_header = static function () use ($action_header): void { ?>
    <thead>
        <?php $action_header() ?>
        <tr>
            <th style="width:40%"><?= lang('Cash_summary.particular') ?></th>
            <th style="width:15%"><?= lang('Cash_summary.cn') ?></th>
            <th style="width:15%"><?= lang('Cash_summary.ca') ?></th>
            <th style="width:15%"><?= lang('Cash_summary.cp') ?></th>
            <th style="width:15%"><?= lang('Cash_summary.oe') ?></th>
        </tr>
    </thead>
<?php }; ?>

<div id="cash-summary-print-area">
    <div style="text-align:center; margin: 10px 0 5px; font-weight: bold; font-size: 1.1em;">
        <?= to_date(strtotime($date)) ?>
    </div>

<?php if (empty($sessions)): ?>
    <div class="ledger-section">
        <table class="ledger-table">
            <?php $column_header() ?>
            <tbody>
                <tr>
                    <td colspan="5" class="no-sessions"><?= lang('Cash_summary.no_results') ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php foreach ($sessions as $session): ?>
    <div class="ledger-section">
        <div class="session-title"><?= esc($session['label']) ?></div>
        <div class="session-times">
            <?= esc(lang('Cashups.open_date')) ?>: <?= esc($session['open_display']) ?>
            |
            <?= esc(lang('Cashups.close_date')) ?>: <?= esc($session['close_display']) ?>
        </div>
        <table class="ledger-table">
            <?php $column_header() ?>
            <tbody>
                <tr class="cash-rem">
                    <td><?= lang('Cash_summary.cash_rem') ?></td>
                    <td class="amount"><?= to_currency($session['cash_beginning']) ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php foreach ($session['rows'] as $row): ?>
                <tr>
                    <td>
                        <?= esc($row['particular']) ?>
                        <?php if (! empty($row['particular_note'])): ?>
                            <span class="particular-note"><?= esc($row['particular_note']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= $row['cn'] !== null ? to_currency($row['cn']) : '' ?></td>
                    <td class="amount"><?= $row['ca'] !== null ? to_currency($row['ca']) : '' ?></td>
                    <td class="amount"><?= $row['cp'] !== null ? to_currency($row['cp']) : '' ?></td>
                    <td class="amount"><?= $row['oe'] !== null ? to_currency($row['oe']) : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td><?= lang('Common.total') ?></td>
                    <td class="amount"><?= to_currency($session['cn_total']) ?></td>
                    <td class="amount"><?= to_currency($session['ca_total']) ?></td>
                    <td class="amount"><?= to_currency($session['cp_total']) ?></td>
                    <td class="amount"><?= to_currency($session['oe_total']) ?></td>
                </tr>
                <tr class="cash-ending">
                    <td><?= lang('Cash_summary.cash_ending') ?></td>
                    <td class="amount"><?= to_currency($session['cash_ending']) ?></td>
                    <td colspan="3" class="copra-session-summary">
                        <?php if (! empty($session['copra_summary_display'])): ?>
                            <?= esc(lang('Cash_summary.copra_summary')) ?>: <?= esc($session['copra_summary_display']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?= view('partial/footer') ?>
