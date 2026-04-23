<?php
/**
 * @var object     $cash_ups_info
 * @var array      $employees
 * @var string     $controller_name
 * @var array      $config
 * @var array|null $cash_breakdown
 * @var float|null $expected_amount_cash
 * @var bool       $is_new_cashup
 * @var bool       $is_pending_close
 * @var bool       $is_closed_cashup
 */
$is_new_cashup ??= $cash_ups_info->cashup_id === NEW_ENTRY;
$is_closed_cashup ??= false;

$show_closing_fields = empty($is_new_cashup);
$read_only           = ! empty($is_closed_cashup);
$readonly_attribute  = $read_only ? ['readonly' => 'readonly'] : [];
$disabled_attribute  = $read_only ? ' disabled="disabled"' : '';
$disabled_checkbox   = $read_only ? ['disabled' => 'disabled'] : [];
$show_meridian       = str_contains($config['timeformat'], 'a') || str_contains($config['timeformat'], 'A');
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open('cashups/save/' . $cash_ups_info->cashup_id, ['id' => 'cashups_edit_form', 'class' => 'form-horizontal'])    // TODO: String Interpolation?>
    <fieldset id="item_basic_info">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.info'), 'cash_ups_info', ['class' => 'control-label col-xs-3']) ?>
            <?= form_label(! empty($cash_ups_info->cashup_id) ? lang('Cashups.id') . ' ' . $cash_ups_info->cashup_id : '', 'cashup_id', ['class' => 'control-label col-xs-8', 'style' => 'text-align: left']) ?>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.open_date'), 'open_date', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                    <?= form_input([
                        'name'  => 'open_date',
                        'id'    => 'open_date',
                        'class' => 'form-control input-sm datepicker',
                        'value' => to_datetime(strtotime($cash_ups_info->open_date)),
                    ] + $readonly_attribute) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.open_employee'), 'open_employee', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('open_employee_id', $employees, $cash_ups_info->open_employee_id, 'id="open_employee_id" class="form-control"' . $disabled_attribute) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.open_amount_cash'), 'open_amount_cash', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'open_amount_cash',
                        'id'    => 'open_amount_cash',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->open_amount_cash),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.transfer_amount_cash'), 'transfer_amount_cash', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'transfer_amount_cash',
                        'id'    => 'transfer_amount_cash',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->transfer_amount_cash),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (! $show_closing_fields): ?>
            <?= form_hidden('close_date', to_datetime(strtotime($cash_ups_info->close_date))) ?>
            <?= form_hidden('close_employee_id', $cash_ups_info->close_employee_id) ?>
            <?= form_hidden('closed_amount_cash', to_currency_no_money($cash_ups_info->closed_amount_cash)) ?>
            <?= form_hidden('closed_amount_due', to_currency_no_money($cash_ups_info->closed_amount_due)) ?>
            <?= form_hidden('closed_amount_card', to_currency_no_money($cash_ups_info->closed_amount_card)) ?>
            <?= form_hidden('closed_amount_check', to_currency_no_money($cash_ups_info->closed_amount_check)) ?>
            <?= form_hidden('closed_amount_total', to_currency_no_money(0)) ?>
        <?php endif; ?>

        <?php if ($show_closing_fields): ?>
        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.close_date'), 'close_date', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                    <?= form_input([
                        'name'  => 'close_date',
                        'id'    => 'close_date',
                        'class' => 'form-control input-sm datepicker',
                        'value' => to_datetime(strtotime($cash_ups_info->close_date)),
                    ] + $readonly_attribute) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.close_employee'), 'close_employee', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('close_employee_id', $employees, $cash_ups_info->close_employee_id, 'id="close_employee_id" class="form-control"' . $disabled_attribute) ?>
            </div>
        </div>

        <?php if (! empty($cash_breakdown)): ?>
        <div class="form-group form-group-sm">
            <div class="col-xs-offset-3 col-xs-8">
                <table class="table table-condensed table-bordered" style="margin-bottom: 5px;">
                    <thead>
                        <tr><th colspan="2"><?= lang('Cashups.breakdown') ?></th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= lang('Cashups.sales_cash') ?></td>
                            <td class="text-right"><?= to_currency($cash_breakdown['sales_cash']) ?></td>
                        </tr>
                        <tr>
                            <td><?= lang('Cashups.expenses_cash') ?></td>
                            <td class="text-right">-<?= to_currency($cash_breakdown['expenses_cash']) ?></td>
                        </tr>
                        <tr>
                            <td><?= lang('Cashups.loan_adjustments') ?></td>
                            <td class="text-right">-<?= to_currency($cash_breakdown['loan_adjustments']) ?></td>
                        </tr>
                        <tr>
                            <td><?= lang('Cashups.receivings_cash') ?></td>
                            <td class="text-right">-<?= to_currency($cash_breakdown['receivings_cash']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.expected_amount_cash'), 'expected_amount_cash', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'     => 'expected_amount_cash',
                        'id'       => 'expected_amount_cash',
                        'readonly' => 'true',
                        'class'    => 'form-control input-sm',
                        'value'    => to_currency_no_money((float) ($cash_ups_info->expected_amount_cash ?? 0)),
                    ]) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.closed_amount_cash'), 'closed_amount_cash', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'closed_amount_cash',
                        'id'    => 'closed_amount_cash',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->closed_amount_cash),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.note'), 'note', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_checkbox([
                    'name'    => 'note',
                    'id'      => 'note',
                    'value'   => 0,
                    'checked' => $cash_ups_info->note === 1,
                ] + $disabled_checkbox) ?>
            </div>
        </div>

        <?php if ($show_closing_fields): ?>
        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.closed_amount_due'), 'closed_amount_due', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'closed_amount_due',
                        'id'    => 'closed_amount_due',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->closed_amount_due),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.closed_amount_card'), 'closed_amount_card', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'closed_amount_card',
                        'id'    => 'closed_amount_card',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->closed_amount_card),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.closed_amount_check'), 'closed_amount_check', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'closed_amount_check',
                        'id'    => 'closed_amount_check',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($cash_ups_info->closed_amount_check),
                    ] + $readonly_attribute) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.closed_amount_total'), 'closed_amount_total', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'     => 'closed_amount_total',
                        'id'       => 'closed_amount_total',
                        'readonly' => 'true',
                        'class'    => 'form-control input-sm',
                        'value'    => to_currency_no_money($cash_ups_info->closed_amount_total),
                    ]) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cashups.description'), 'description', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_textarea([
                    'name'  => 'description',
                    'id'    => 'description',
                    'class' => 'form-control input-sm',
                    'value' => $cash_ups_info->description,
                ] + $readonly_attribute) ?>
            </div>
        </div>

        <?php if (! empty($cash_ups_info->cashup_id)) { ?>
            <div class="form-group form-group-sm">
                <?= form_label(lang('Cashups.is_deleted') . ':', 'deleted', ['class' => 'control-label col-xs-3']) ?>
                <div class="col-xs-5">
                    <?= form_checkbox([
                        'name'    => 'deleted',
                        'id'      => 'deleted',
                        'value'   => 1,
                        'checked' => $cash_ups_info->deleted === 1,
                    ] + $disabled_checkbox) ?>
                </div>
            </div>
        <?php } ?>
    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>

        <?php if (! $read_only): ?>
        $('#open_date').datetimepicker({
            format: "<?= dateformat_bootstrap($config['dateformat']) . ' ' . dateformat_bootstrap($config['timeformat']) ?>",
            startDate: "<?= date($config['dateformat'] . ' ' . esc($config['timeformat'], 'js'), mktime(0, 0, 0, 1, 1, 2010)) ?>",
            showMeridian: <?= $show_meridian ? 'true' : 'false' ?>,
            minuteStep: 1,
            autoclose: true,
            todayBtn: true,
            todayHighlight: true,
            bootcssVer: 3,
            language: '<?= current_language_code() ?>'
        });

        $('#close_date').datetimepicker({
            format: "<?= dateformat_bootstrap($config['dateformat']) . ' ' . dateformat_bootstrap($config['timeformat']) ?>",
            startDate: "<?= date($config['dateformat'] . ' ' . esc($config['timeformat'], 'js'), mktime(0, 0, 0, 1, 1, 2010)) ?>",
            showMeridian: <?= $show_meridian ? 'true' : 'false' ?>,
            minuteStep: 1,
            autoclose: true,
            todayBtn: true,
            todayHighlight: true,
            bootcssVer: 3,
            language: '<?= current_language_code() ?>'
        });
        <?php endif; ?>

        var cashMovement = <?= json_encode(
            (float) (($cash_breakdown['sales_cash'] ?? 0)
            - ($cash_breakdown['expenses_cash'] ?? 0)
            - ($cash_breakdown['loan_adjustments'] ?? 0)
            - ($cash_breakdown['receivings_cash'] ?? 0)),
        ) ?>;

        function parseMoneyValue(value) {
            var parsedValue = parseFloat(String(value || '').replace(/,/g, ''));

            return isNaN(parsedValue) ? 0 : parsedValue;
        }

        function formatMoneyValue(value) {
            return (Math.round(value * 100) / 100).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updateCashupTotals() {
            var openAmountCash = parseMoneyValue($('#open_amount_cash').val());
            var transferAmountCash = parseMoneyValue($('#transfer_amount_cash').val());
            var closedAmountCash = parseMoneyValue($('#closed_amount_cash').val());
            var expectedAmountCash = openAmountCash + transferAmountCash + cashMovement;
            var overShortAmount = closedAmountCash - expectedAmountCash;

            $('#expected_amount_cash').val(formatMoneyValue(expectedAmountCash));
            $('#closed_amount_total').val(formatMoneyValue(overShortAmount));
        }

        <?php if (! $read_only): ?>
        $('#open_amount_cash, #transfer_amount_cash, #closed_amount_cash').on('input change', updateCashupTotals);
        updateCashupTotals();
        <?php endif; ?>

        var submit_form = function() {
            $(this).ajaxSubmit({
                success: function(response) {
                    dialog_support.hide();
                    table_support.handle_submit('<?= esc('cashups') ?>', response);
                },
                dataType: 'json'
            });
        };

        $('#cashups_edit_form').validate($.extend({
            submitHandler: function(form) {
                submit_form.call(form);
            },
            rules: {

            },
            messages: {
                open_date: {
                    required: '<?= lang('Cashups.date_required') ?>'

                },
                close_date: {
                    required: '<?= lang('Cashups.date_required') ?>'

                },
                amount: {
                    required: '<?= lang('Cashups.amount_required') ?>',
                    number: '<?= lang('Cashups.amount_number') ?>'
                }
            }
        }, form_support.error));
    });
</script>
