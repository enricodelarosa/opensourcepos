<?php
/**
 * @var object $adjustment_info
 * @var array  $employees
 * @var bool   $can_assign_employee
 * @var string $selected_supplier_name
 * @var string $selected_luna_id
 * @var string $controller_name
 * @var array  $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("loan_adjustments/save/{$adjustment_info->adjustment_id}", ['id' => 'loan_adjustment_form', 'class' => 'form-horizontal']) ?>
    <fieldset>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.info'), 'adjustment_info', ['class' => 'control-label col-xs-3']) ?>
            <?= form_label(! empty($adjustment_info->adjustment_id) && $adjustment_info->adjustment_id !== NEW_ENTRY ? lang('Loan_adjustments.adjustment_id') . " {$adjustment_info->adjustment_id}" : '', 'adjustment_info_id', ['class' => 'control-label col-xs-8', 'style' => 'text-align: left']) ?>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.date'), 'adjustment_time', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'     => 'adjustment_time',
                        'class'    => 'form-control input-sm datetime',
                        'value'    => to_datetime(strtotime($adjustment_info->adjustment_time)),
                        'readonly' => 'readonly',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.supplier'), 'supplier_name', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'supplier_name',
                    'id'    => 'supplier_name',
                    'class' => 'form-control input-sm',
                    'value' => $selected_supplier_name ?: lang('Loan_adjustments.start_typing_supplier_name'),
                ]) ?>
                <?= form_input(['type' => 'hidden', 'name' => 'supplier_id', 'id' => 'supplier_id', 'value' => $adjustment_info->supplier_id ?? '']) ?>
                <?= form_input(['type' => 'hidden', 'name' => 'customer_id', 'id' => 'customer_id', 'value' => $adjustment_info->customer_id ?? '']) ?>
            </div>
            <div class="col-xs-2">
                <a id="remove_supplier_button" class="btn btn-danger btn-sm" title="<?= lang('Common.remove') ?>">
                    <span class="glyphicon glyphicon-remove"></span>
                </a>
            </div>
        </div>

        <div class="form-group form-group-sm" id="loan_balance_row" style="display: none;">
            <?= form_label(lang('Loan_adjustments.current_loan_balance'), '', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <p class="form-control-static" id="loan_balance_display">—</p>
            </div>
        </div>

        <div class="form-group form-group-sm" id="loan_breakdown_row" style="display: none;">
            <?= form_label(lang('Loan_adjustments.loan_breakdown'), '', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <div id="loan_breakdown_display"></div>
            </div>
        </div>

        <div class="form-group form-group-sm" id="luna_row" style="display: none;">
            <?= form_label(lang('Loan_adjustments.select_luna'), 'luna_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('luna_id', ['' => lang('Loan_adjustments.no_luna')], $selected_luna_id, ['class' => 'form-control', 'id' => 'luna_id']) ?>
                <p class="help-block" id="luna_help_text" style="display:none; margin-bottom:0;"><?= lang('Suppliers.no_lunas') ?></p>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.direction'), 'direction', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('direction', [
                    'increase' => lang('Loan_adjustments.increase_loan'),
                    'decrease' => lang('Loan_adjustments.decrease_loan'),
                ], $adjustment_info->loan_amount < 0 ? 'decrease' : 'increase', ['class' => 'form-control', 'id' => 'direction']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.amount'), 'amount', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group input-group-sm">
                    <?php if (! is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'amount',
                        'id'    => 'amount',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money(abs($adjustment_info->loan_amount)),
                    ]) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.comment'), 'comment', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_textarea([
                    'name'  => 'comment',
                    'id'    => 'comment',
                    'class' => 'form-control input-sm',
                    'value' => $adjustment_info->comment ?? '',
                    'rows'  => 3,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Loan_adjustments.employee'), 'employee_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?php if ($can_assign_employee): ?>
                    <?= form_dropdown('employee_id', $employees, $adjustment_info->employee_id, 'id="employee_id" class="form-control"') ?>
                <?php else: ?>
                    <?= form_hidden('employee_id', $adjustment_info->employee_id) ?>
                    <?= form_input(['name' => 'employee_name', 'value' => esc($employees[$adjustment_info->employee_id] ?? ''), 'class' => 'form-control', 'readonly' => 'readonly']) ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (! empty($adjustment_info->adjustment_id) && $adjustment_info->adjustment_id !== NEW_ENTRY): ?>
            <div class="form-group form-group-sm">
                <?= form_label(lang('Loan_adjustments.is_deleted') . ':', 'deleted', ['class' => 'control-label col-xs-3']) ?>
                <div class="col-xs-5">
                    <?= form_checkbox([
                        'name'    => 'deleted',
                        'id'      => 'deleted',
                        'value'   => 1,
                        'checked' => $adjustment_info->deleted === 1,
                    ]) ?>
                </div>
            </div>
        <?php endif; ?>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>
        var initialLunaId = <?= json_encode($selected_luna_id) ?>;

        // Supplier autocomplete
        $('#supplier_name').click(function() {
            $(this).attr('value', '');
        });

        $('#supplier_name').autocomplete({
            source: '<?= 'loan_adjustments/supplierSuggest' ?>',
            minChars: 0,
            delay: 10,
            select: function(event, ui) {
                $('#supplier_id').val(ui.item.value);
                $(this).val(ui.item.label);
                $(this).attr('readonly', 'readonly');
                $('#remove_supplier_button').css('display', 'inline-block');
                loadSupplierLunas(ui.item.value, '');
                loadLoanBalance(ui.item.value);
                return false;
            }
        });

        $('#supplier_name').blur(function() {
            $(this).attr('value', "<?= lang('Loan_adjustments.start_typing_supplier_name') ?>");
        });

        $('#remove_supplier_button').css('display', 'none');

        $('#remove_supplier_button').click(function() {
            $('#supplier_id').val('');
            $('#customer_id').val('');
            $('#supplier_name').removeAttr('readonly').val('');
            $(this).css('display', 'none');
            $('#loan_balance_row').hide();
            $('#loan_breakdown_row').hide();
            $('#loan_breakdown_display').empty();
            resetLunaOptions();
        });

        // Pre-fill on edit
        <?php if (! empty($adjustment_info->supplier_id)): ?>
            $('#supplier_name').val('<?= esc($selected_supplier_name, 'js') ?>').attr('readonly', 'readonly');
            $('#remove_supplier_button').css('display', 'inline-block');
            loadSupplierLunas(<?= (int) $adjustment_info->supplier_id ?>, initialLunaId);
            loadLoanBalance(<?= (int) $adjustment_info->supplier_id ?>);
        <?php endif; ?>

        function loadSupplierLunas(supplierId, selectedLunaId) {
            if (!supplierId) {
                resetLunaOptions();
                return;
            }

            $.getJSON('<?= 'suppliers/getLunas/' ?>' + supplierId, function(rows) {
                renderLunaOptions(rows || [], selectedLunaId || '');
            });
        }

        function loadLoanBalance(supplierId) {
            if (!supplierId) {
                return;
            }

            $.getJSON('<?= 'loan_adjustments/balance/' ?>' + supplierId, function(data) {
                if (data.customer_id) {
                    $('#customer_id').val(data.customer_id);
                    $('#loan_balance_display').text('<?= esc($config['currency_symbol']) ?>' + parseFloat(data.balance).toFixed(2));
                    $('#loan_balance_row').show();
                    renderLoanBreakdown(data.breakdown || []);
                } else {
                    $('#customer_id').val('');
                    $('#loan_balance_row').hide();
                    $('#loan_breakdown_row').hide();
                }
            });
        }

        function resetLunaOptions() {
            $('#luna_id').html('<option value=""><?= esc(lang('Loan_adjustments.no_luna'), 'js') ?></option>').val('');
            $('#luna_help_text').hide();
            $('#luna_row').hide();
        }

        function renderLunaOptions(rows, selectedId) {
            if (!rows.length) {
                resetLunaOptions();
                return;
            }

            var options = ['<option value=""><?= esc(lang('Loan_adjustments.no_luna'), 'js') ?></option>'];

            $.each(rows, function(index, row) {
                var label = row.area_name || '';
                if (row.barangay) {
                    label += ' (' + row.barangay + ')';
                }
                if (row.landowner_name) {
                    label += ' - ' + row.landowner_name;
                }

                options.push(
                    '<option value="' + $('<div>').text(row.luna_id).html() + '">' +
                        $('<div>').text(label).html() +
                    '</option>'
                );
            });

            $('#luna_id').html(options.join(''));
            $('#luna_id').val(selectedId);
            $('#luna_help_text').hide();
            $('#luna_row').show();
        }

        function renderLoanBreakdown(rows) {
            if (!rows.length) {
                $('#loan_breakdown_display').empty();
                $('#loan_breakdown_row').hide();
                return;
            }

            var html = ['<table class="table table-condensed table-striped" style="margin-bottom:0;">'];
            html.push('<thead><tr>');
            html.push('<th><?= esc(lang('Suppliers.area_name'), 'js') ?></th>');
            html.push('<th><?= esc(lang('Suppliers.barangay'), 'js') ?></th>');
            html.push('<th class="text-right"><?= esc(lang('Loan_adjustments.amount'), 'js') ?></th>');
            html.push('</tr></thead><tbody>');

            $.each(rows, function(index, row) {
                var areaName = row.luna_id ? (row.area_name || '') : <?= json_encode(lang('Loan_adjustments.general_advance')) ?>;
                var barangay = row.luna_id ? (row.barangay || '') : '';
                if (row.luna_id && row.landowner_name) {
                    areaName += ' - ' + row.landowner_name;
                }
                var balance = parseFloat(row.balance || 0).toFixed(2);

                html.push('<tr>');
                html.push('<td>' + $('<div>').text(areaName).html() + '</td>');
                html.push('<td>' + $('<div>').text(barangay).html() + '</td>');
                html.push('<td class="text-right"><?= esc($config['currency_symbol']) ?>' + balance + '</td>');
                html.push('</tr>');
            });

            html.push('</tbody></table>');

            $('#loan_breakdown_display').html(html.join(''));
            $('#loan_breakdown_row').show();
        }

        // Form validation and submit
        $('#loan_adjustment_form').validate($.extend({
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
                supplier_name: 'required',
                adjustment_time: { required: true },
                amount: {
                    required: true,
                    remote: "<?= "{$controller_name}/checkNumeric" ?>"
                }
            },
            messages: {
                supplier_name: "<?= lang('Loan_adjustments.supplier_required') ?>",
                adjustment_time: { required: "<?= lang('Loan_adjustments.date_required') ?>" },
                amount: {
                    required: "<?= lang('Loan_adjustments.amount_required') ?>",
                    remote: "<?= lang('Loan_adjustments.amount_number') ?>"
                }
            }
        }, form_support.error));
    });
</script>
