<?php
/**
 * @var string $controller_name
 * @var object $person_info
 * @var array  $categories
 * @var array  $customers_list
 * @var array  $tenants_list
 * @var string $luna_panel_mode
 * @var bool   $show_luna_panel
 * @var bool   $show_linked_customer_controls
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("{$controller_name}/save/{$person_info->person_id}", ['id' => 'supplier_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="supplier_basic_info">

        <div class="form-group form-group-sm" id="company_name_row">
            <?= form_label(lang('Suppliers.company_name'), 'company_name', ['class' => 'control-label col-xs-3', 'id' => 'company_name_label']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'company_name',
                    'id'    => 'company_name_input',
                    'class' => 'form-control input-sm',
                    'value' => html_entity_decode($person_info->company_name),
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.category'), 'category', ['class' => 'required control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('category', $categories, $person_info->category, ['class' => 'form-control', 'id' => 'category']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm" id="agency_name_row">
            <?= form_label(lang('Suppliers.agency_name'), 'agency_name', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'agency_name',
                    'id'    => 'agency_name_input',
                    'class' => 'form-control input-sm',
                    'value' => $person_info->agency_name,
                ]) ?>
            </div>
        </div>

        <?= view('people/form_basic_info', [
            'hide_gender' => true,
            'phone_before_email' => true,
        ]) ?>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Suppliers.account_number'), 'account_number', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_input([
                    'name'  => 'account_number',
                    'id'    => 'account_number',
                    'class' => 'form-control input-sm',
                    'value' => $person_info->account_number,
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
                    'value' => $person_info->tax_id,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm" id="create_linked_customer_row"<?= empty($person_info->person_id) && $show_linked_customer_controls ? '' : ' style="display:none;"' ?>>
            <div class="col-xs-offset-3 col-xs-8">
                <div class="checkbox">
                    <label>
                        <?= form_checkbox(['name' => 'create_linked_customer', 'id' => 'create_linked_customer', 'value' => '1']) ?>
                        <?= lang('Suppliers.create_linked_customer') ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm" id="linked_customer_row"<?= $show_linked_customer_controls ? '' : ' style="display:none;"' ?>>
            <?= form_label(lang('Suppliers.linked_customer'), 'customer_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-8">
                <?= form_dropdown('customer_id', $customers_list, $person_info->customer_id ?? '', ['class' => 'form-control', 'id' => 'customer_id']) ?>
            </div>
        </div>

        <?php if (! empty($person_info->person_id)): ?>
        <div class="form-group form-group-sm" id="luna_panel"<?= $show_luna_panel ? '' : ' style="display:none;"' ?>>
            <div class="col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong><?= lang('Suppliers.lunas') ?></strong></div>
                    <div class="panel-body">
                        <div id="luna_message" class="alert" style="display:none;"></div>

                        <div class="table-responsive">
                            <table class="table table-condensed table-striped luna-table">
                                <thead>
                                    <tr>
                                        <th><?= lang('Suppliers.area_name') ?></th>
                                        <th><?= lang('Suppliers.barangay') ?></th>
                                        <th><?= $luna_panel_mode === 'landowner' ? lang('Suppliers.tenant') : lang('Suppliers.land_owner') ?></th>
                                        <th><?= lang('Suppliers.last_harvest') ?></th>
                                        <th><?= lang('Suppliers.next_expected_harvest') ?></th>
                                        <th class="text-right"><?= lang('Suppliers.outstanding_balance') ?></th>
                                        <?php if ($luna_panel_mode === 'landowner'): ?>
                                            <th class="text-right"><?= lang('Common.delete') ?></th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="lunas_table_body">
                                    <tr>
                                        <td colspan="<?= $luna_panel_mode === 'landowner' ? 7 : 6 ?>"><?= lang('Suppliers.no_lunas') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($luna_panel_mode === 'landowner'): ?>
                        <hr>

                        <div class="row">
                            <div class="col-sm-6 luna-form-field">
                                <?= form_label(lang('Suppliers.area_name'), 'luna_area_name') ?>
                                <?= form_input([
                                    'name'  => 'luna_area_name',
                                    'id'    => 'luna_area_name',
                                    'class' => 'form-control input-sm',
                                ]) ?>
                            </div>
                            <div class="col-sm-6 luna-form-field">
                                <?= form_label(lang('Suppliers.barangay'), 'luna_barangay') ?>
                                <?= form_input([
                                    'name'  => 'luna_barangay',
                                    'id'    => 'luna_barangay',
                                    'class' => 'form-control input-sm',
                                ]) ?>
                            </div>
                        </div>
                        <div class="luna-form-field">
                            <div>
                                <?= form_label(lang('Suppliers.tenant'), 'luna_tenant_id') ?>
                                <?= form_dropdown('luna_tenant_id', $tenants_list, '', [
                                    'class'            => 'selectpicker',
                                    'id'               => 'luna_tenant_id',
                                    'data-live-search' => 'true',
                                    'data-size'        => '10',
                                    'data-width'       => '100%',
                                    'data-style'       => 'form-control input-sm',
                                ]) ?>
                            </div>
                        </div>

                        <?= form_input([
                            'type'  => 'hidden',
                            'name'  => 'luna_id',
                            'id'    => 'luna_id',
                            'value' => '',
                        ]) ?>

                        <div class="text-right" style="margin-top: 12px;">
                            <button type="button" class="btn btn-primary btn-sm" id="add_luna_button">
                                <span class="glyphicon glyphicon-plus"></span> <?= lang('Suppliers.add_luna') ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group form-group-sm" id="supplier_gender_row">
            <?= form_label(lang('Common.gender'), 'gender', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-4">
                <label class="radio-inline">
                    <?= form_radio([
                        'name'    => 'gender',
                        'type'    => 'radio',
                        'id'      => 'gender',
                        'value'   => 1,
                        'checked' => $person_info->gender === '1',
                    ]) ?> <?= lang('Common.gender_male') ?>
                </label>
                <label class="radio-inline">
                    <?= form_radio([
                        'name'    => 'gender',
                        'type'    => 'radio',
                        'id'      => 'gender',
                        'value'   => 0,
                        'checked' => $person_info->gender === '0',
                    ]) ?> <?= lang('Common.gender_female') ?>
                </label>
            </div>
        </div>

    </fieldset>
<?= form_close() ?>

<style type="text/css">
    #luna_panel .table-responsive {
        margin-bottom: 0;
        border: 0;
    }

    #luna_panel .luna-table {
        min-width: 640px;
    }

    #luna_panel > .panel > .panel-body > .luna-form-field + .luna-form-field {
        margin-top: 12px;
    }

    #luna_panel .row + .luna-form-field {
        margin-top: 12px;
    }

    #luna_panel .row .luna-form-field {
        margin-top: 0;
    }

    #luna_panel .bootstrap-select {
        width: 100% !important;
    }

    #luna_panel .bootstrap-select > .dropdown-toggle {
        background: #fff;
        border: 1px solid #ccc;
        color: #555;
        box-shadow: none;
    }

    #luna_panel .bootstrap-select > .dropdown-toggle:hover,
    #luna_panel .bootstrap-select > .dropdown-toggle:focus,
    #luna_panel .bootstrap-select.open > .dropdown-toggle,
    #luna_panel .bootstrap-select.show > .dropdown-toggle {
        background: #fff;
        border-color: #66afe9;
        color: #555;
        outline: 0 !important;
    }

    #luna_panel .bootstrap-select .dropdown-menu li.selected > a,
    #luna_panel .bootstrap-select .dropdown-menu li.selected > a:hover,
    #luna_panel .bootstrap-select .dropdown-menu li.selected > a:focus,
    #luna_panel .bootstrap-select .dropdown-menu li.active > a,
    #luna_panel .bootstrap-select .dropdown-menu li.active > a:hover,
    #luna_panel .bootstrap-select .dropdown-menu li.active > a:focus {
        background: #f5f5f5;
        color: #333;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        var supplierId = <?= json_encode((int) ($person_info->person_id ?: 0)) ?>;
        var lunaPanelMode = <?= json_encode($luna_panel_mode) ?>;
        var showLunaPanel = <?= json_encode((bool) $show_luna_panel) ?>;
        var canManageLunas = lunaPanelMode === 'landowner';
        var landOwnerCategory = <?= json_encode((string) LAND_OWNER_SUPPLIER) ?>;
        var tenantCategory = <?= json_encode((string) TENANT_SUPPLIER) ?>;
        var noHarvestRecordedText = <?= json_encode(lang('Suppliers.no_harvest_recorded')) ?>;
        var noLunasColspan = <?= json_encode($luna_panel_mode === 'landowner' ? 7 : 6) ?>;
        var currencySymbol = <?= json_encode($config['currency_symbol']) ?>;
        var currencySymbolOnRight = <?= json_encode(is_right_side_currency_symbol()) ?>;

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function formatCurrency(amount) {
            var value = parseFloat(amount || 0);
            var formatted = Math.abs(value).toFixed(2);
            var sign = value < 0 ? '-' : '';

            if (currencySymbolOnRight) {
                return sign + formatted + currencySymbol;
            }

            return sign + currencySymbol + formatted;
        }

        function arrangeSupplierSections() {
            var $phoneRow = $('#supplier_basic_info .form-group').has('label[for="phone_number"]').first();
            var $lunaPanel = $('#luna_panel');
            var $genderRow = $('#supplier_gender_row');

            if (! $phoneRow.length || ! $genderRow.length) {
                return;
            }

            if ($lunaPanel.length) {
                $lunaPanel.insertAfter($phoneRow);
                $genderRow.insertAfter($lunaPanel);
                return;
            }

            $genderRow.insertAfter($phoneRow);
        }

        function categoryAutoCreatesCustomer() {
            return $('#category').val() === landOwnerCategory || $('#category').val() === tenantCategory;
        }

        function toggleSupplierBusinessFields() {
            var showBusinessFields = !categoryAutoCreatesCustomer();

            $('#company_name_row').toggle(showBusinessFields);
            $('#agency_name_row').toggle(showBusinessFields);
            $('#company_name_label').toggleClass('required', showBusinessFields);
        }

        function toggleLinkedCustomerControls() {
            var showManualControls = !categoryAutoCreatesCustomer();
            var showCreateLinkedCustomer = !supplierId && showManualControls;

            $('#create_linked_customer_row').toggle(showCreateLinkedCustomer);

            if (!showManualControls) {
                $('#linked_customer_row').hide();
                $('#customer_id').prop('disabled', true).val('');
                $('#create_linked_customer').prop('checked', true);
                return;
            }

            var createChecked = $('#create_linked_customer').is(':checked');

            $('#linked_customer_row').toggle(!createChecked);
            $('#customer_id').prop('disabled', createChecked);
        }

        function showLunaMessage(message, type) {
            if (!message) {
                $('#luna_message').hide().removeClass('alert-danger alert-success').text('');
                return;
            }

            $('#luna_message')
                .removeClass('alert-danger alert-success')
                .addClass(type === 'success' ? 'alert-success' : 'alert-danger')
                .text(message)
                .show();
        }

        function resetLunaForm() {
            $('#luna_id').val('');
            $('#luna_area_name').val('');
            $('#luna_barangay').val('');
            $('#luna_tenant_id').val('');
        }

        function renderLunas(lunas) {
            var rows = [];

            $.each(lunas || [], function(index, luna) {
                var relatedPartyName = canManageLunas
                    ? (luna.tenant_name || <?= json_encode(lang('Suppliers.no_tenant_assigned')) ?>)
                    : (luna.landowner_name || '');
                var loanBalance = parseFloat(luna.loan_balance || 0);
                var loanBalanceClass = loanBalance > 0
                    ? ' text-danger'
                    : (loanBalance < 0 ? ' text-success' : '');
                var deleteButton = canManageLunas
                    ? '<td class="text-right">' +
                        '<button type="button" class="btn btn-danger btn-xs luna-delete" data-luna-id="' + escapeHtml(luna.luna_id) + '">' +
                            '<span class="glyphicon glyphicon-remove"></span>' +
                        '</button>' +
                    '</td>'
                    : '';

                rows.push(
                    '<tr>' +
                        '<td>' + escapeHtml(luna.area_name) + '</td>' +
                        '<td>' + escapeHtml(luna.barangay) + '</td>' +
                        '<td>' + escapeHtml(relatedPartyName) + '</td>' +
                        '<td>' + escapeHtml(luna.last_harvest_date || noHarvestRecordedText) + '</td>' +
                        '<td>' + escapeHtml(luna.next_expected_harvest_date || noHarvestRecordedText) + '</td>' +
                        '<td class="text-right' + loanBalanceClass + '">' + escapeHtml(formatCurrency(loanBalance)) + '</td>' +
                        deleteButton +
                    '</tr>'
                );
            });

            if (rows.length === 0) {
                rows.push('<tr><td colspan="' + noLunasColspan + '"><?= esc(lang('Suppliers.no_lunas'), 'js') ?></td></tr>');
            }

            $('#lunas_table_body').html(rows.join(''));
        }

        function loadLunas() {
            if (!supplierId) {
                return;
            }

            $.getJSON('<?= esc("{$controller_name}/getLunas", 'js') ?>/' + supplierId, function(response) {
                renderLunas(response);
                showLunaMessage('', 'success');
            });
        }

        function toggleLunaPanel() {
            if (!supplierId || !showLunaPanel) {
                $('#luna_panel').hide();
                return;
            }

            var selectedCategory = $('#category').val();
            var shouldShowPanel = (canManageLunas && selectedCategory == landOwnerCategory)
                || (!canManageLunas && selectedCategory == tenantCategory);

            if (shouldShowPanel) {
                $('#luna_panel').show();
                loadLunas();
            } else {
                $('#luna_panel').hide();
                showLunaMessage('', 'success');
            }
        }

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
                company_name: {
                    required: function() {
                        return !categoryAutoCreatesCustomer();
                    }
                },
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

        $('#create_linked_customer').on('change', function() {
            toggleLinkedCustomerControls();
        });

        $('#category').on('change', function() {
            toggleLinkedCustomerControls();
            toggleSupplierBusinessFields();
            toggleLunaPanel();
        });

        toggleLinkedCustomerControls();
        toggleSupplierBusinessFields();
        arrangeSupplierSections();

        $('.selectpicker').each(function() {
            var $selectpicker = $(this);
            $.fn.selectpicker.call($selectpicker, $selectpicker.data());
        });

        <?php if (! empty($person_info->person_id)): ?>
        $('#add_luna_button').on('click', function() {
            $.post('<?= esc("{$controller_name}/saveLuna", 'js') ?>/' + supplierId, {
                luna_id: $('#luna_id').val(),
                area_name: $('#luna_area_name').val(),
                barangay: $('#luna_barangay').val(),
                tenant_id: $('#luna_tenant_id').val()
            }, function(response) {
                if (response.success) {
                    renderLunas(response.lunas || []);
                    resetLunaForm();
                    showLunaMessage('', 'success');
                } else {
                    showLunaMessage(response.message || <?= json_encode(lang('Suppliers.error_adding_updating')) ?>, 'error');
                }
            }, 'json');
        });

        $(document).on('click', '.luna-delete', function() {
            var lunaId = $(this).data('luna-id');

            $.post('<?= esc("{$controller_name}/deleteLuna", 'js') ?>/' + lunaId, {}, function(response) {
                if (response.success) {
                    renderLunas(response.lunas || []);
                    showLunaMessage('', 'success');
                } else {
                    showLunaMessage(response.message || <?= json_encode(lang('Suppliers.cannot_be_deleted')) ?>, 'error');
                }
            }, 'json');
        });

        toggleLunaPanel();
        <?php endif; ?>
    });
</script>
