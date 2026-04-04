<?php
/**
 * @var string $controller_name
 * @var object $person_info
 * @var array $categories
 * @var array $customers_list
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("$controller_name/save/$person_info->person_id", ['id' => 'supplier_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="supplier_basic_info">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.company_name'), 'company_name', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'company_name',
                    'id'    => 'company_name_input',
                    'class' => 'form-control input-sm',
                    'value' => html_entity_decode($person_info->company_name)
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.category'), 'category', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('category', $categories, $person_info->category, ['class' => 'form-control', 'id' => 'category']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.agency_name'), 'agency_name', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'agency_name',
                    'id'    => 'agency_name_input',
                    'class' => 'form-control input-sm',
                    'value' => $person_info->agency_name
                ]) ?>
            </div>
        </div>

        <?= view('people/form_basic_info') ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.account_number'), 'account_number', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'account_number',
                    'id'    => 'account_number',
                    'class' => 'form-control input-sm',
                    'value' => $person_info->account_number
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.tax_id'), 'tax_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'tax_id',
                    'id'    => 'tax_id',
                    'class' => 'form-control input-sm',
                    'value' => $person_info->tax_id
                ]) ?>
            </div>
        </div>

        <?php if (empty($person_info->person_id)): ?>
        <div class="form-group form-group-sm">
            <div class="col-xs-offset-3 col-xs-8">
                <div class="checkbox">
                    <label>
                        <?= form_checkbox(['name' => 'create_linked_customer', 'id' => 'create_linked_customer', 'value' => '1']) ?>
                        <?= lang('Suppliers.create_linked_customer') ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.starting_loan_amount'), 'starting_loan_amount', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <?= form_input([
                    'name'  => 'starting_loan_amount',
                    'id'    => 'starting_loan_amount',
                    'class' => 'form-control input-sm',
                    'type'  => 'number',
                    'step'  => '0.01',
                    'min'   => '0',
                    'value' => '0.00'
                ]) ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm" id="linked_customer_row">
            <?= form_label(lang('Suppliers.linked_customer'), 'customer_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('customer_id', $customers_list, $person_info->customer_id ?? '', ['class' => 'form-control', 'id' => 'customer_id']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm" id="partner_supplier_row">
            <?= form_label(lang('Suppliers.partner_supplier'), 'partner_supplier_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('partner_supplier_id', $partners_list, $person_info->partner_supplier_id ?? '', ['class' => 'form-control', 'id' => 'partner_supplier_id']) ?>
            </div>
        </div>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        $('#supplier_form').validate($.extend({
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

            rules: {
                company_name: 'required',
                first_name: 'required',
                last_name: 'required',
                email: 'email'
            },

            messages: {
                company_name: "<?= lang('Suppliers.company_name_required') ?>",
                first_name: "<?= lang('Common.first_name_required') ?>",
                last_name: "<?= lang('Common.last_name_required') ?>",
                email: "<?= lang('Common.email_invalid_format') ?>"
            }
        }, form_support.error));

        <?php if (empty($person_info->person_id)): ?>
        $('#create_linked_customer').on('change', function() {
            if ($(this).is(':checked')) {
                $('#linked_customer_row').hide();
                $('#customer_id').val('').prop('disabled', true);
            } else {
                $('#linked_customer_row').show();
                $('#customer_id').prop('disabled', false);
            }
        });
        <?php endif; ?>
    });
</script>
