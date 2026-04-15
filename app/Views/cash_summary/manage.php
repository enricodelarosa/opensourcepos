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
.ledger-table td.amount { text-align: right; }
.ledger-table tr.cash-rem td { font-weight: bold; background: #fafafa; }
.ledger-table tr.totals td { font-weight: bold; border-top: 2px solid #333; background: #f0f0f0; }
.ledger-table tr.cash-ending td { font-weight: bold; }
.session-title { font-size: 1.2em; font-weight: bold; margin: 15px 0 8px; text-align: center; text-transform: uppercase; }
.session-times { margin-bottom: 8px; text-align: center; color: #666; }
.no-sessions { color: #999; text-align: center; padding: 30px; }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        $('#date-picker').on('change', function() {
            window.location = 'cash_summary?date=' + $(this).val();
        });
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

<div style="text-align:center; margin: 10px 0 5px; font-weight: bold; font-size: 1.1em;">
    <?= to_date(strtotime($date)) ?>
</div>

<?php if (empty($sessions)): ?>
    <div class="no-sessions"><?= lang('Cash_summary.no_results') ?></div>
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
            <thead>
                <tr>
                    <th style="width:40%"><?= lang('Cash_summary.particular') ?></th>
                    <th style="width:15%"><?= lang('Cash_summary.cn') ?></th>
                    <th style="width:15%"><?= lang('Cash_summary.ca') ?></th>
                    <th style="width:15%"><?= lang('Cash_summary.cp') ?></th>
                    <th style="width:15%"><?= lang('Cash_summary.oe') ?></th>
                </tr>
            </thead>
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
                    <td><?= esc($row['particular']) ?></td>
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
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= view('partial/footer') ?>
