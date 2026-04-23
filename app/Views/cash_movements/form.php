<?php
/**
 * @var object $movement_info
 * @var array  $employees
 * @var bool   $can_assign_employee
 * @var string $controller_name
 * @var array  $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("cash_movements/save/{$movement_info->movement_id}", ['id' => 'cash_movement_form', 'class' => 'form-horizontal']) ?>
    <fieldset>
        <div class="form-group form-group-sm">
            <?= form_label(lang('Cash_movements.info'), 'movement_info', ['class' => 'control-label col-xs-3']) ?>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cash_movements.date'), 'movement_time', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'     => 'movement_time',
                        'class'    => 'form-control input-sm datetime',
                        'value'    => to_datetime(strtotime($movement_info->movement_time)),
                        'readonly' => 'readonly',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cash_movements.amount'), 'amount', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'amount',
                        'id'    => 'amount',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($movement_info->amount),
                    ]) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cash_movements.description'), 'description', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_textarea([
                    'name'  => 'description',
                    'id'    => 'description',
                    'class' => 'form-control input-sm',
                    'value' => $movement_info->description ?? '',
                    'rows'  => 3,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Cash_movements.employee'), 'employee_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?php if ($can_assign_employee): ?>
                    <?= form_dropdown('employee_id', $employees, $movement_info->employee_id, 'id="employee_id" class="form-control"') ?>
                <?php else: ?>
                    <?= form_hidden('employee_id', $movement_info->employee_id) ?>
                    <?= form_input(['name' => 'employee_name', 'value' => esc($employees[$movement_info->employee_id] ?? ''), 'class' => 'form-control', 'readonly' => 'readonly']) ?>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>

        $('#cash_movement_form').validate($.extend({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit("<?= esc($controller_name) ?>", response);
                    },
                    dataType: 'json'
                });
            },
            errorLabelContainer: '#error_message_box',
            ignore: '',
            rules: {
                movement_time: { required: true },
                amount: {
                    required: true,
                    remote: "<?= "{$controller_name}/checkNumeric" ?>"
                }
            },
            messages: {
                movement_time: { required: "<?= lang('Cash_movements.date_required') ?>" },
                amount: {
                    required: "<?= lang('Cash_movements.amount_required') ?>",
                    remote: "<?= lang('Cash_movements.amount_number') ?>"
                }
            }
        }, form_support.error));
    });
</script>
