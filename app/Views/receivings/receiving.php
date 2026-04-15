<?php
/**
 * @var string      $controller_name
 * @var array       $modes
 * @var string      $mode
 * @var bool        $show_stock_locations
 * @var array       $stock_locations
 * @var int         $stock_source
 * @var string      $stock_destination
 * @var array       $cart
 * @var bool        $items_module_allowed
 * @var float       $total
 * @var string      $comment
 * @var bool        $print_after_sale
 * @var string      $reference
 * @var array       $payment_options
 * @var array       $config
 * @var string      $loan_balance
 * @var bool        $has_linked_customer
 * @var int|null    $linked_customer_id
 * @var array       $lunas
 * @var int         $selected_luna_id
 * @var object|null $selected_luna
 * @var bool        $has_partner_supplier
 * @var string      $partner_supplier_name
 * @var string      $partner_loan_balance
 * @var int|null    $partner_customer_id
 * @var int         $supplier_category
 * @var array       $copra_split
 * @var array       $copra_expenses
 * @var bool        $show_copra_split_tools
 * @var bool        $copra_split_partner_ready
 */
?>

<?= view('partial/header') ?>

<style>
    #register_wrapper {
        width: 55%;
    }

    #overall_sale {
        width: 40%;
    }
</style>

<?php
if (isset($error)) {
    echo '<div class="alert alert-dismissible alert-danger">' . esc($error) . '</div>';
}

if (! empty($warning)) {
    echo '<div class="alert alert-dismissible alert-warning">' . esc($warning) . '</div>';
}

if (isset($success)) {
    echo '<div class="alert alert-dismissible alert-success">' . esc($success) . '</div>';
}
?>

<div id="register_wrapper">

    <!-- Top register controls -->

    <?= form_open("{$controller_name}/changeMode", ['id' => 'mode_form', 'class' => 'form-horizontal panel panel-default']) ?>

    <div class="panel-body form-group">
        <ul>
            <li class="pull-left first_li">
                <label class="control-label"><?= lang(ucfirst($controller_name) . '.mode') ?></label>
            </li>
            <li class="pull-left">
                <?= form_dropdown('mode', $modes, $mode, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
            </li>

            <?php if ($show_stock_locations) { ?>
                <li class="pull-left">
                    <label class="control-label"><?= lang(ucfirst($controller_name) . '.stock_source') ?></label>
                </li>
                <li class="pull-left">
                    <?= form_dropdown('stock_source', $stock_locations, $stock_source, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
                </li>

                <?php if ($mode === 'requisition') { ?>
                    <li class="pull-left">
                        <label class="control-label"><?= lang(ucfirst($controller_name) . '.stock_destination') ?></label>
                    </li>
                    <li class="pull-left">
                        <?= form_dropdown('stock_destination', $stock_locations, $stock_destination, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
                    </li>
            <?php
                }
            }
?>
        </ul>
    </div>

    <?= form_close() ?>

    <?= form_open("{$controller_name}/add", ['id' => 'add_item_form', 'class' => 'form-horizontal panel panel-default']) ?>

    <div class="panel-body form-group">
        <ul>
            <li class="pull-left first_li">
                <label for="item" class="control-label">
                    <?php if ($mode === 'receive' || $mode === 'requisition') { ?>
                        <?= lang(ucfirst($controller_name) . '.find_or_scan_item') ?>
                    <?php } else { ?>
                        <?= lang(ucfirst($controller_name) . '.find_or_scan_item_or_receipt') ?>
                    <?php } ?>
                </label>
            </li>

            <li class="pull-left">
                <?= form_input(['name' => 'item', 'id' => 'item', 'class' => 'form-control input-sm', 'size' => '50', 'tabindex' => '1']) ?>
            </li>

            <li class="pull-right">
                <button id="new_item_button" class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-btn-new="<?= lang('Common.new') ?>" data-href="<?= 'items/view' ?>" title="<?= lang('Sales.new_item') ?>">
                    <span class="glyphicon glyphicon-tag">&nbsp;</span><?= lang('Sales.new_item') ?>
                </button>
            </li>
        </ul>
    </div>

    <?= form_close() ?>

    <!-- Receiving Items List -->

    <table class="sales_table_100" id="register">
        <thead>
            <tr>
                <th style="width: 5%;"><?= lang('Common.delete') ?></th>
                <th style="width: 18%;"><?= lang('Sales.item_number') ?></th>
                <th style="width: 30%;"><?= lang(ucfirst($controller_name) . '.item_name') ?></th>
                <th style="width: 14%;"><?= lang(ucfirst($controller_name) . '.cost') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.quantity') ?></th>
                <th style="width: 18%;"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 5%;"><?= lang(ucfirst($controller_name) . '.update') ?></th>
            </tr>
        </thead>

        <tbody id="cart_contents">
            <?php if (count($cart) === 0) { ?>
                <tr>
                    <td colspan="6">
                        <div class="alert alert-dismissible alert-info"><?= lang('Sales.no_items_in_cart') ?></div>
                    </td>
                </tr>
                <?php
            } else {
                foreach (array_reverse($cart, true) as $line => $item) {
                    ?>

                    <?= form_open("{$controller_name}/editItem/{$line}", ['class' => 'form-horizontal', 'id' => "cart_{$line}"]) ?>

                    <tr>
                        <td><?= anchor("{$controller_name}/deleteItem/{$line}", '<span class="glyphicon glyphicon-trash"></span>') ?></td>
                        <td><?= esc($item['item_number']) ?></td>
                        <td style="text-align: center;">
                            <?= esc($item['name'] . ' ' . implode(' ', [$item['attribute_values'], $item['attribute_dtvalues']])) ?><br>
                            <?= '[' . to_quantity_decimals($item['in_stock']) . ' in ' . esc($item['stock_name']) . ']' ?>
                            <?= form_hidden('location', (string) $item['item_location']) ?>
                        </td>

                        <?php if ($items_module_allowed && $mode !== 'requisition') { ?>
                            <td>
                                <?= form_input([
                                    'name'    => 'price',
                                    'class'   => 'form-control input-sm',
                                    'value'   => to_currency_no_money($item['price']),
                                    'onClick' => 'this.select();',
                                ]) ?>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?= $item['price'] ?>
                                <?= form_hidden('price', to_currency_no_money($item['price'])) ?>
                            </td>
                        <?php } ?>

                        <td>
                            <?= form_input(['name' => 'quantity', 'class' => 'form-control input-sm', 'value' => to_quantity_decimals($item['quantity']), 'onClick' => 'this.select();']) ?>
                        </td>
                        <?= form_hidden('receiving_quantity', (string) $item['receiving_quantity']) ?>
                        <?= form_hidden('discount', (string) $item['discount']) ?>
                        <?= form_hidden('discount_type', (string) $item['discount_type']) ?>
                        <td>
                            <?= to_currency(($item['discount_type'] === PERCENT) ? $item['price'] * $item['quantity'] * $item['receiving_quantity'] - $item['price'] * $item['quantity'] * $item['receiving_quantity'] * $item['discount'] / 100 : $item['price'] * $item['quantity'] * $item['receiving_quantity'] - $item['discount']) ?>
                        </td>
                        <td>
                            <a href="javascript:$('#<?= esc("cart_{$line}", 'js') ?>').submit();" title=<?= lang(ucfirst($controller_name) . '.update') ?>>
                                <span class="glyphicon glyphicon-refresh"></span>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <?php if ($item['allow_alt_description'] === 1) {    // TODO: ===?
                            ?>
                            <td style="color: #2F4F4F;"><?= lang('Sales.description_abbrv') . ':' ?></td>
                        <?php } ?>
                        <td colspan="2" style="text-align: left;">
                            <?php
                                if ($item['allow_alt_description'] === 1) {    // TODO: ===?
                                    echo form_input([
                                        'name'  => 'description',
                                        'class' => 'form-control input-sm',
                                        'value' => $item['description'],
                                    ]);
                                } else {
                                    if ($item['description'] !== '') {    // TODO: !==?
                                        echo esc($item['description']);
                                        echo form_hidden('description', $item['description']);
                                    } else {
                                        echo '<i>' . lang('Sales.no_description') . '</i>';
                                        echo form_hidden('description', '');
                                    }
                                }
                    ?>
                        </td>
                        <td colspan="4"></td>
                    </tr>

                    <?= form_close() ?>

            <?php
                }
            }
?>
        </tbody>
    </table>
</div>

<!-- Overall Receiving -->

<div id="overall_sale" class="panel panel-default">
    <div class="panel-body">
        <?php if (isset($supplier)) { ?>

            <table class="sales_table_100">
                <tr>
                    <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.supplier') ?></th>
                    <th style="width: 45%; text-align: right;"><?= esc($supplier) ?></th>
                </tr>
                <?php if (! empty($supplier_email)) { ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.supplier_email') ?></th>
                        <th style="width: 45%; text-align: right;"><?= esc($supplier_email) ?></th>
                    </tr>
                <?php } ?>
                <?php if (! empty($supplier_address)) { ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.supplier_address') ?></th>
                        <th style="width: 45%; text-align: right;"><?= esc($supplier_address) ?></th>
                    </tr>
                <?php } ?>
                <?php if (! empty($supplier_location)) { ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.supplier_location') ?></th>
                        <th style="width: 45%; text-align: right;"><?= esc($supplier_location) ?></th>
                    </tr>
                <?php } ?>
            </table>

            <?php
                $selected_luna_label = '';
            ?>
            <?php if (! empty($lunas)) { ?>
                <?php
                $luna_options        = [-1 => '-- ' . lang('Receivings.select_luna') . ' --'];
                $last_harvest_label  = lang('Suppliers.no_harvest_recorded');
                $next_expected_label = lang('Suppliers.no_harvest_recorded');

                if ($selected_luna) {
                    $selected_luna_label = $selected_luna->area_name;
                    if (! empty($selected_luna->barangay)) {
                        $selected_luna_label .= ' (' . $selected_luna->barangay . ')';
                    }
                    $last_harvest_label  = $selected_luna->last_harvest_date ?? $last_harvest_label;
                    $next_expected_label = $selected_luna->next_expected_harvest_date ?? $next_expected_label;
                }

                foreach ($lunas as $luna_row) {
                    $label = $luna_row['area_name'];
                    if (! empty($luna_row['tenant_name'])) {
                        $label .= ' - ' . $luna_row['tenant_name'];
                    }
                    $luna_options[$luna_row['luna_id']] = $label;
                }
                ?>
                <?= form_open("{$controller_name}/selectLuna", ['id' => 'select_luna_form', 'class' => 'form-horizontal', 'style' => 'margin-top: 12px;']) ?>
                    <table class="sales_table_100">
                        <tr>
                            <th style="width: 55%;"><?= lang('Receivings.select_luna') ?></th>
                            <td style="width: 45%; text-align: right;">
                                <?= form_dropdown('luna_id', $luna_options, $selected_luna_id, ['class' => 'form-control input-sm', 'id' => 'luna_id_selector']) ?>
                            </td>
                        </tr>
                    </table>
                <?= form_close() ?>

                <?php if ($selected_luna) { ?>
                    <table class="sales_table_100" style="margin-top: 8px;">
                        <tr>
                            <th style="width: 55%;"><?= lang('Suppliers.luna') ?></th>
                            <td style="width: 45%; text-align: right;"><?= esc($selected_luna_label) ?></td>
                        </tr>
                        <tr>
                            <th style="width: 55%;"><?= lang('Suppliers.last_harvest') ?></th>
                            <td style="width: 45%; text-align: right;"><?= esc($last_harvest_label) ?></td>
                        </tr>
                        <tr>
                            <th style="width: 55%;"><?= lang('Suppliers.next_expected_harvest') ?></th>
                            <td style="width: 45%; text-align: right;"><?= esc($next_expected_label) ?></td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } ?>

            <?= anchor(
                "{$controller_name}/removeSupplier",
                '<span class="glyphicon glyphicon-remove">&nbsp;</span>' . lang('Common.remove') . ' ' . lang('Suppliers.supplier'),
                [
                    'class' => 'btn btn-danger btn-sm',
                    'id'    => 'remove_supplier_button',
                    'title' => lang('Common.remove') . ' ' . lang('Suppliers.supplier'),
                ],
            ) ?>

        <?php } else { ?>

            <?= form_open("{$controller_name}/selectSupplier", ['id' => 'select_supplier_form', 'class' => 'form-horizontal']) ?>

            <div class="form-group" id="select_customer">
                <label id="supplier_label" for="supplier" class="control-label" style="margin-bottom: 1em; margin-top: -1em;">
                    <?= lang(ucfirst($controller_name) . '.select_supplier') ?>
                </label>
                <?= form_input([
                    'name'  => 'supplier',
                    'id'    => 'supplier',
                    'class' => 'form-control input-sm',
                    'value' => lang(ucfirst($controller_name) . '.start_typing_supplier_name'),
                ]) ?>

                <button id="new_supplier_button" class="btn btn-info btn-sm modal-dlg modal-dlg-wide" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= 'suppliers/view' ?>" title="<?= lang(ucfirst($controller_name) . '.new_supplier') ?>">
                    <span class="glyphicon glyphicon-user">&nbsp;</span><?= lang(ucfirst($controller_name) . '.new_supplier') ?>
                </button>

            </div>

            <?= form_close() ?>

        <?php } ?>

        <table class="sales_table_100" id="sale_totals">
            <tr>
                <?php if ($mode !== 'requisition') { ?>
                    <th style="width: 55%;"><?= lang('Sales.total') ?></th>
                    <th style="width: 45%; text-align: right;"><?= to_currency($total) ?></th>
                <?php } else { ?>
                    <th style="width: 55%;"></th>
                    <th style="width: 45%; text-align: right;"></th>
                <?php } ?>
            </tr>
        </table>

        <?php if (count($cart) > 0) { ?>
            <div id="finish_sale">
                <?php if ($mode === 'requisition') { ?>

                    <?= form_open("{$controller_name}/requisitionComplete", ['id' => 'finish_receiving_form', 'class' => 'form-horizontal']) ?>

                    <div class="form-group form-group-sm">
                        <label id="comment_label" for="comment"><?= lang('Common.comments') ?></label>
                        <?= form_textarea([
                            'name'  => 'comment',
                            'id'    => 'comment',
                            'class' => 'form-control input-sm',
                            'value' => $comment,
                            'rows'  => '4',
                        ]) ?>

                        <div class="btn btn-sm btn-danger pull-left" id="cancel_receiving_button">
                            <span class="glyphicon glyphicon-remove">&nbsp;</span><?= lang(ucfirst($controller_name) . '.cancel_receiving') ?>
                        </div>
                        <div class="btn btn-sm btn-success pull-right" id="finish_receiving_button">
                            <span class="glyphicon glyphicon-ok">&nbsp;</span><?= lang(ucfirst($controller_name) . '.complete_receiving') ?>
                        </div>
                    </div>

                    <?= form_close() ?>

                <?php } else { ?>

                    <?= form_open("{$controller_name}/complete", ['id' => 'finish_receiving_form', 'class' => 'form-horizontal']) ?>

                    <div class="form-group form-group-sm">
                        <label id="comment_label" for="comment"><?= lang('Common.comments') ?></label>
                        <?= form_textarea([
                            'name'  => 'comment',
                            'id'    => 'comment',
                            'class' => 'form-control input-sm',
                            'value' => $comment,
                            'rows'  => '4',
                        ]) ?>
                        <?= form_input([
                            'type'  => 'hidden',
                            'name'  => 'selected_luna_id',
                            'id'    => 'selected_luna_id',
                            'value' => (string) $selected_luna_id,
                        ]) ?>
                        <div id="payment_details">
                            <table class="sales_table_100">
                                <tr>
                                    <td><?= lang(ucfirst($controller_name) . '.print_after_sale') ?></td>
                                    <td>
                                        <?= form_checkbox([
                                            'name'    => 'recv_print_after_sale',
                                            'id'      => 'recv_print_after_sale',
                                            'class'   => 'checkbox',
                                            'value'   => 1,
                                            'checked' => $print_after_sale === 1,
                                        ]) ?>
                                    </td>
                                </tr>
                                <?php if ($mode === 'receive') { ?>
                                    <tr>
                                        <td><?= lang(ucfirst($controller_name) . '.reference') ?></td>
                                        <td>
                                            <?= form_input([
                                                'name'  => 'recv_reference',
                                                'id'    => 'recv_reference',
                                                'class' => 'form-control input-sm',
                                                'value' => $reference,
                                                'size'  => 5,
                                            ]) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td><?= lang('Sales.payment') ?></td>
                                    <td>
                                        <?= form_dropdown(
                                            'payment_type',
                                            $payment_options,
                                            [],
                                            [
                                                'id'         => 'payment_types',
                                                'class'      => 'selectpicker show-menu-arrow',
                                                'data-style' => 'btn-default btn-sm',
                                                'data-width' => 'auto',
                                            ],
                                        ) ?>
                                    </td>
                                </tr>
                                <?php if ($show_copra_split_tools): ?>
                                    <tr>
                                        <td colspan="2" style="padding-top: 12px;">
                                            <strong style="font-size: 1.05em;"><?= esc(lang('Receivings.copra_split_setup')) ?></strong>
                                            <hr style="margin: 4px 0; border-color: #aaa;">
                                        </td>
                                    </tr>
                                    <?php if ($copra_split_partner_ready): ?>
                                        <tr>
                                            <td><?= esc(lang('Receivings.initial_split_guide')) ?></td>
                                            <td style="text-align: right;">
                                                <div id="copra_initial_split_guide" style="font-weight: 600;"></div>
                                                <div id="copra_initial_split_amounts" style="color: #666; font-size: 12px;"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?= esc(lang('Receivings.landowner_share_percent')) ?></td>
                                            <td>
                                                <?= form_input([
                                                    'name'  => 'landowner_share_percent',
                                                    'id'    => 'landowner_share_percent',
                                                    'value' => number_format((float) ($copra_split['landowner_share_percent'] ?? 50), 2, '.', ''),
                                                    'class' => 'form-control input-sm',
                                                    'type'  => 'number',
                                                    'step'  => '0.01',
                                                    'min'   => '0',
                                                    'max'   => '100',
                                                ]) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?= esc(lang('Receivings.tenant_share_percent')) ?></td>
                                            <td>
                                                <?= form_input([
                                                    'name'  => 'tenant_share_percent',
                                                    'id'    => 'tenant_share_percent',
                                                    'value' => number_format((float) ($copra_split['tenant_share_percent'] ?? 50), 2, '.', ''),
                                                    'class' => 'form-control input-sm',
                                                    'type'  => 'number',
                                                    'step'  => '0.01',
                                                    'min'   => '0',
                                                    'max'   => '100',
                                                ]) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top: 8px;">
                                                <div style="font-weight: 600; margin-bottom: 6px;"><?= esc(lang('Receivings.copra_expenses')) ?></div>
                                                <div class="table-responsive">
                                                    <table class="table table-condensed table-bordered" style="margin-bottom: 8px;">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 46%;"><?= esc(lang('Receivings.expense_description')) ?></th>
                                                                <th style="width: 20%; text-align: right;"><?= esc(lang('Receivings.expense_amount')) ?></th>
                                                                <th style="width: 28%;"><?= esc(lang('Receivings.expense_add_back_to')) ?></th>
                                                                <th style="width: 6%; text-align: center;"><?= esc(lang('Common.delete')) ?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="copra_expense_body"></tbody>
                                                    </table>
                                                </div>
                                                <button type="button" class="btn btn-default btn-sm" id="add_copra_expense_button">
                                                    <span class="glyphicon glyphicon-plus">&nbsp;</span><?= esc(lang('Receivings.add_expense')) ?>
                                                </button>
                                                <?= form_input([
                                                    'type'  => 'hidden',
                                                    'name'  => 'copra_expenses_json',
                                                    'id'    => 'copra_expenses_json',
                                                    'value' => '[]',
                                                ]) ?>
                                            </td>
                                        </tr>
                                        <tr id="copra_split_validation_row" style="display: none;">
                                            <td colspan="2">
                                                <div id="copra_split_validation_message" class="alert alert-danger" style="margin-bottom: 0;"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?= esc(lang('Receivings.suggested_cash_to_landowner')) ?></td>
                                            <td style="text-align: right;"><strong id="suggested_cash_to_landowner"><?= to_currency(0) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td><?= esc(lang('Receivings.suggested_cash_to_tenant')) ?></td>
                                            <td style="text-align: right;"><strong id="suggested_cash_to_tenant"><?= to_currency(0) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2">
                                                <div class="alert alert-info" style="margin-bottom: 0;"><?= esc(lang('Receivings.select_luna_with_tenant_for_split')) ?></div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php
                                    $show_partner_loan = $has_partner_supplier && $partner_customer_id && $partner_loan_balance > 0;
                    $show_any_loan                     = $has_linked_customer || $show_partner_loan;
                    ?>
                                <?php if ($show_any_loan) { ?>
                                    <!-- Primary supplier loan section -->
                                    <?php if ($has_linked_customer && $loan_balance > 0) { ?>
                                    <tr>
                                        <td colspan="2" style="padding-top: 12px;">
                                            <strong style="font-size: 1.05em;"><?= esc($supplier) ?><?php if ($selected_luna_label !== '') { ?> - <?= esc($selected_luna_label) ?><?php } ?></strong>
                                            <hr style="margin: 4px 0; border-color: #aaa;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?= $selected_luna ? lang('Receivings.luna_loan_balance') : lang('Receivings.loan_balance') ?></td>
                                        <td><strong style="color: #d9534f;"><?= to_currency($loan_balance) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><?= lang('Receivings.loan_deduction') ?></td>
                                        <td>
                                            <?= form_input([
                                                'name'        => 'loan_deduction',
                                                'id'          => 'loan_deduction',
                                                'value'       => $loan_deduction ?? '',
                                                'class'       => 'form-control input-sm',
                                                'size'        => '5',
                                                'placeholder' => '0.00',
                                            ]) ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <!-- Selected luna loan section -->
                                    <?php if ($show_partner_loan) { ?>
                                    <tr>
                                        <td colspan="2" style="padding-top: 12px;">
                                            <strong style="font-size: 1.05em;"><?= esc($partner_supplier_name) ?><?php if ($selected_luna_label !== '') { ?> - <?= esc($selected_luna_label) ?><?php } ?></strong>
                                            <hr style="margin: 4px 0; border-color: #aaa;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?= lang('Receivings.luna_loan_balance') ?></td>
                                        <td><strong style="color: #d9534f;"><?= to_currency($partner_loan_balance) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><?= lang('Receivings.loan_deduction') ?></td>
                                        <td>
                                            <?= form_input([
                                                'name'        => 'partner_loan_deduction',
                                                'id'          => 'partner_loan_deduction',
                                                'value'       => $partner_loan_deduction ?? '',
                                                'class'       => 'form-control input-sm',
                                                'size'        => '5',
                                                'placeholder' => '0.00',
                                            ]) ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>
                                <?php $show_cash_to_pay_summary = $show_any_loan || $selected_luna_id > 0 || $has_partner_supplier; ?>
                                <?php if ($show_cash_to_pay_summary) { ?>
                                    <tr>
                                        <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?= lang('Receivings.cash_to_pay') ?></strong></td>
                                        <td><strong id="cash_to_pay"><?= to_currency($total) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                    </tr>
                                <?php } ?>
                                <?php if ($has_partner_supplier) { ?>
                                <?php if ($has_linked_customer && $selected_luna_id > 0) { ?>
                                <tr>
                                    <td colspan="2">
                                        <label for="store_negative_loan" style="display: block; font-weight: normal; margin-bottom: 0;">
                                            <?= form_checkbox([
                                                'name'    => 'store_negative_loan',
                                                'id'      => 'store_negative_loan',
                                                'value'   => 1,
                                                'checked' => ! empty($store_negative_loan),
                                            ]) ?>
                                            <span style="font-weight: 600;"><?= esc(lang('Receivings.store_cash_to_negative_loan_balance_for')) ?></span>
                                            <div style="padding-left: 22px; color: #666; font-size: 12px;"><?= esc(lang('Reports.landowner')) ?><?= ! empty($supplier) ? ' - ' . esc($supplier) : '' ?></div>
                                        </label>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td>
                                        <div id="primary_payment_label" style="font-weight: 600;"><?= esc(lang('Receivings.cash_to_landowner')) ?></div>
                                        <div style="color: #666; font-size: 12px;"><?= ! empty($supplier) ? esc($supplier) : esc(lang('Reports.landowner')) ?></div>
                                    </td>
                                    <td>
                                        <?= form_input([
                                            'name'        => 'amount_tendered',
                                            'id'          => 'amount_tendered',
                                            'value'       => $amount_tendered ?? '',
                                            'class'       => 'form-control input-sm',
                                            'size'        => '5',
                                            'placeholder' => '0.00',
                                        ]) ?>
                                    </td>
                                </tr>
                                <?php if ($has_linked_customer && $selected_luna_id > 0) { ?>
                                <tr id="negative_loan_divider"<?= empty($store_negative_loan) || (float) ($negative_loan_amount ?? 0) <= 0 ? ' style="display:none;"' : '' ?>>
                                    <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                </tr>
                                <tr id="negative_loan_row"<?= empty($store_negative_loan) || (float) ($negative_loan_amount ?? 0) <= 0 ? ' style="display:none;"' : '' ?>>
                                    <td><strong><?= lang('Receivings.remaining_as_landowner_negative_loan') ?></strong></td>
                                    <td><strong id="negative_loan_amount" style="color: #5cb85c;"><?= to_currency((float) ($negative_loan_amount ?? 0)) ?></strong></td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?= esc(lang('Receivings.cash_to_tenant')) ?></div>
                                        <div style="color: #666; font-size: 12px;"><?= ! empty($partner_supplier_name) ? esc($partner_supplier_name) : esc(lang('Reports.tenant')) ?></div>
                                    </td>
                                    <td>
                                        <?= form_input([
                                            'name'        => 'partner_amount_tendered',
                                            'id'          => 'partner_amount_tendered',
                                            'value'       => $partner_amount_tendered ?? '',
                                            'class'       => 'form-control input-sm',
                                            'size'        => '5',
                                            'placeholder' => '0.00',
                                        ]) ?>
                                    </td>
                                </tr>
                                <?php } else { ?>
                                <?php if ($selected_luna_id > 0 && $has_linked_customer) { ?>
                                <tr>
                                    <td colspan="2">
                                        <label for="store_negative_loan" style="display: block; font-weight: normal; margin-bottom: 0;">
                                            <?= form_checkbox([
                                                'name'    => 'store_negative_loan',
                                                'id'      => 'store_negative_loan',
                                                'value'   => 1,
                                                'checked' => ! empty($store_negative_loan),
                                            ]) ?>
                                            <span style="font-weight: 600;"><?= esc(lang('Receivings.store_cash_to_negative_loan_balance_for')) ?></span>
                                            <div style="padding-left: 22px; color: #666; font-size: 12px;"><?= esc(lang('Reports.landowner')) ?><?= ! empty($supplier) ? ' - ' . esc($supplier) : '' ?></div>
                                        </label>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td>
                                        <?php if ($selected_luna_id > 0) { ?>
                                            <div id="primary_payment_label" style="font-weight: 600;"><?= esc(lang('Receivings.cash_to_landowner')) ?></div>
                                            <div style="color: #666; font-size: 12px;"><?= ! empty($supplier) ? esc($supplier) : esc(lang('Reports.landowner')) ?></div>
                                        <?php } else { ?>
                                            <?= lang('Sales.amount_tendered') ?>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?= form_input([
                                            'name'  => 'amount_tendered',
                                            'id'    => 'amount_tendered',
                                            'value' => $amount_tendered ?? '',
                                            'class' => 'form-control input-sm',
                                            'size'  => '5',
                                        ]) ?>
                                    </td>
                                </tr>
                                <?php if ($has_linked_customer && $selected_luna_id > 0) { ?>
                                <tr id="negative_loan_divider"<?= empty($store_negative_loan) || (float) ($negative_loan_amount ?? 0) <= 0 ? ' style="display:none;"' : '' ?>>
                                    <td colspan="2"><hr style="margin: 10px 0; border-color: #ddd;"></td>
                                </tr>
                                <tr id="negative_loan_row"<?= empty($store_negative_loan) || (float) ($negative_loan_amount ?? 0) <= 0 ? ' style="display:none;"' : '' ?>>
                                    <td><strong><?= lang('Receivings.remaining_as_landowner_negative_loan') ?></strong></td>
                                    <td><strong id="negative_loan_amount" style="color: #5cb85c;"><?= to_currency((float) ($negative_loan_amount ?? 0)) ?></strong></td>
                                </tr>
                                <?php } ?>
                                <?php } ?>
                            </table>
                        </div>

                        <div class="btn btn-sm btn-danger pull-left" id="cancel_receiving_button">
                            <span class="glyphicon glyphicon-remove">&nbsp;</span><?= lang(ucfirst($controller_name) . '.cancel_receiving') ?>
                        </div>
                        <div class="btn btn-sm btn-success pull-right" id="finish_receiving_button">
                            <span class="glyphicon glyphicon-ok">&nbsp;</span><?= lang(ucfirst($controller_name) . '.complete_receiving') ?>
                        </div>
                    </div>

                    <?= form_close() ?>

                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#item").autocomplete({
            source: '<?= esc("{$controller_name}/stockItemSearch") ?>',
            minChars: 0,
            delay: 10,
            autoFocus: false,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $("#add_item_form").submit();
                return false;
            }
        });

        $('#item').focus();

        $('#item').keypress(function(e) {
            if (e.which == 13) {
                $('#add_item_form').submit();
                return false;
            }
        });

        $('#item').blur(function() {
            $(this).attr('value', "<?= lang('Sales.start_typing_item_name') ?>");
        });

        $('#comment').keyup(function() {
            $.post('<?= esc("{$controller_name}/setComment") ?>', {
                comment: $('#comment').val()
            });
        });

        $('#recv_reference').keyup(function() {
            $.post('<?= esc("{$controller_name}/setReference") ?>', {
                recv_reference: $('#recv_reference').val()
            });
        });

        $("#recv_print_after_sale").change(function() {
            $.post('<?= esc("{$controller_name}/setPrintAfterSale") ?>', {
                recv_print_after_sale: $(this).is(":checked")
            });
        });

        $('#item,#supplier').click(function() {
            $(this).attr('value', '');
        });

        $("#supplier").autocomplete({
            source: '<?= esc("{$controller_name}/supplierSearch") ?>',
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $("#select_supplier_form").submit();
            }
        });

        dialog_support.init("a.modal-dlg, button.modal-dlg");

        $('#supplier').blur(function() {
            $(this).attr('value', "<?= lang(ucfirst($controller_name) . '.start_typing_supplier_name') ?>");
        });

        $('#luna_id_selector').change(function() {
            $('#select_luna_form').submit();
        });

        $("#finish_receiving_button").click(function() {
            $('#finish_receiving_form').submit();
        });

        $("#cancel_receiving_button").click(function() {
            if (confirm('<?= lang(ucfirst($controller_name) . '.confirm_cancel_receiving') ?>')) {
                $('#finish_receiving_form').attr('action', '<?= esc("{$controller_name}/cancelReceiving") ?>');
                $('#finish_receiving_form').submit();
            }
        });

        $("#cart_contents input").keypress(function(event) {
            if (event.which == 13) {
                $(this).parents("tr").prevAll("form:first").submit();
            }
        });

        table_support.handle_submit = function(resource, response, stay_open) {
            if (response.success) {
                if (resource.match(/suppliers$/)) {
                    $("#supplier").val(response.id);
                    $("#select_supplier_form").submit();
                } else {
                    $("#item").val(response.id);
                    if (stay_open) {
                        $("#add_item_form").ajaxSubmit();
                    } else {
                        $("#add_item_form").submit();
                    }
                }
            }
        }

        $('[name="price"],[name="quantity"],[name="receiving_quantity"],[name="discount"],[name="description"],[name="serialnumber"]').change(function() {
            $(this).parents("tr").prevAll("form:first").submit()
        });

        <?php if ($has_linked_customer || ($partner_customer_id && $partner_loan_balance > 0) || $has_partner_supplier) { ?>
        var receivingTotal = <?= json_encode((float) $total) ?>;
        var maxLoanDeduction = Math.max(0, Math.min(<?= json_encode((float) ($loan_balance ?? 0)) ?>, receivingTotal));
        var maxPartnerDeduction = Math.max(0, Math.min(<?= json_encode((float) ($partner_loan_balance ?? 0)) ?>, receivingTotal));
        var currencySymbol = <?= json_encode($config['currency_symbol']) ?>;
        var copraSplitEnabled = <?= json_encode((bool) $copra_split_partner_ready) ?>;
        var copraSplitVisible = <?= json_encode((bool) $show_copra_split_tools) ?>;
        var landownerName = <?= json_encode(! empty($supplier) ? $supplier : lang('Reports.landowner')) ?>;
        var tenantName = <?= json_encode(! empty($partner_supplier_name) ? $partner_supplier_name : lang('Reports.tenant')) ?>;
        var initialCopraExpenses = <?= json_encode($copra_expenses) ?>;
        var copraSettingsUrl = <?= json_encode(site_url("{$controller_name}/setCopraSettings")) ?>;
        var deleteLabel = <?= json_encode(lang('Common.delete')) ?>;
        var addBackToTenantLabel = <?= json_encode(lang('Receivings.add_back_to_tenant')) ?>;
        var addBackToLandownerLabel = <?= json_encode(lang('Receivings.add_back_to_landowner')) ?>;
        var invalidSplitMessages = {
            total: <?= json_encode(lang('Receivings.copra_split_total_invalid')) ?>,
            negative: <?= json_encode(lang('Receivings.copra_split_negative_result')) ?>
        };
        var noCopraExpensesLabel = <?= json_encode(lang('Receivings.no_copra_expenses')) ?>;
        var shareSyncLock = false;
        var copraSaveTimer = null;

        function roundCurrency(amount) {
            return Math.round(amount * 100) / 100;
        }

        function formatDecimal(amount) {
            return roundCurrency(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatCurrency(amount) {
            return currencySymbol + formatDecimal(amount);
        }

        function parseNumericValue(value) {
            var normalizedValue = String(value || '').replace(/,/g, '');
            var parsedValue = parseFloat(normalizedValue);

            return isNaN(parsedValue) ? 0 : parsedValue;
        }

        function setFormattedInputValue(selector, amount) {
            $(selector).val(formatDecimal(Math.max(0, amount)));
        }

        function normalizeFormattedInput(selector) {
            if (!$(selector).length) {
                return;
            }

            var amount = Math.max(0, parseNumericValue($(selector).val()));
            setFormattedInputValue(selector, amount);
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizePercentage(value) {
            var numberValue = parseNumericValue(value);

            if (isNaN(numberValue)) {
                return 0;
            }

            if (numberValue < 0) {
                numberValue = 0;
            }

            if (numberValue > 100) {
                numberValue = 100;
            }

            return roundCurrency(numberValue);
        }

        function normalizeExpense(rawExpense) {
            var description = $.trim((rawExpense && rawExpense.description) || '');
            var amount = parseNumericValue(rawExpense && rawExpense.amount);
            var addBackTo = ((rawExpense && rawExpense.add_back_to) === 'landowner' || (rawExpense && rawExpense.add_back_to) === 'supplier')
                ? 'landowner'
                : 'tenant';

            if (isNaN(amount) || amount < 0) {
                amount = 0;
            }

            return {
                description: description,
                amount: roundCurrency(amount),
                add_back_to: addBackTo
            };
        }

        function buildExpenseRow(expense) {
            var normalizedExpense = normalizeExpense(expense);

            return '' +
                '<tr class="copra-expense-row">' +
                    '<td><input type="text" class="form-control input-sm copra-expense-description" value="' + escapeHtml(normalizedExpense.description) + '" /></td>' +
                    '<td><input type="number" class="form-control input-sm copra-expense-amount text-right" min="0" step="0.01" style="min-width: 110px;" value="' + (normalizedExpense.amount > 0 ? normalizedExpense.amount.toFixed(2) : '') + '" /></td>' +
                    '<td><select class="form-control input-sm copra-expense-add-back-to">'
                        + '<option value="tenant"' + (normalizedExpense.add_back_to === 'tenant' ? ' selected' : '') + '>' + escapeHtml(addBackToTenantLabel) + '</option>'
                        + '<option value="landowner"' + (normalizedExpense.add_back_to === 'landowner' ? ' selected' : '') + '>' + escapeHtml(addBackToLandownerLabel) + '</option>'
                    + '</select></td>' +
                    '<td style="text-align: center;"><button type="button" class="btn btn-link text-danger copra-expense-delete" style="padding: 0 2px;" title="' + escapeHtml(deleteLabel) + '"><span class="glyphicon glyphicon-trash"></span></button></td>' +
                '</tr>';
        }

        function updateCopraExpensePayload() {
            if (!$('#copra_expenses_json').length) {
                return;
            }

            $('#copra_expenses_json').val(JSON.stringify(getCopraExpenses()));
        }

        function renderCopraExpenses(expenses) {
            if (!$('#copra_expense_body').length) {
                return;
            }

            if (!expenses.length) {
                $('#copra_expense_body').html(
                    '<tr class="copra-expense-empty">' +
                        '<td colspan="4" style="text-align: center; color: #777;">' + escapeHtml(noCopraExpensesLabel) + '</td>' +
                    '</tr>'
                );
                updateCopraExpensePayload();

                return;
            }

            var rows = '';

            $.each(expenses, function(_, expense) {
                rows += buildExpenseRow(expense);
            });

            $('#copra_expense_body').html(rows);
            updateCopraExpensePayload();
        }

        function getCopraExpenses() {
            var expenses = [];

            $('#copra_expense_body .copra-expense-row').each(function() {
                var expense = normalizeExpense({
                    description: $(this).find('.copra-expense-description').val(),
                    amount: $(this).find('.copra-expense-amount').val(),
                    add_back_to: $(this).find('.copra-expense-add-back-to').val()
                });

                if (expense.description !== '' && expense.amount > 0) {
                    expenses.push(expense);
                }
            });

            return expenses;
        }

        function queueCopraSettingsSave() {
            if (!copraSplitVisible || !$('#landowner_share_percent').length || !$('#tenant_share_percent').length) {
                return;
            }

            updateCopraExpensePayload();

            if (copraSaveTimer !== null) {
                window.clearTimeout(copraSaveTimer);
            }

            copraSaveTimer = window.setTimeout(function() {
                $.post(copraSettingsUrl, {
                    landowner_share_percent: $('#landowner_share_percent').val(),
                    tenant_share_percent: $('#tenant_share_percent').val(),
                    expenses: $('#copra_expenses_json').val()
                });
            }, 200);
        }

        function getCopraSplitSummary(purchaseTotal) {
            if (!copraSplitEnabled || !$('#landowner_share_percent').length || !$('#tenant_share_percent').length) {
                return null;
            }

            var landownerSharePercent = normalizePercentage($('#landowner_share_percent').val());
            var tenantSharePercent = normalizePercentage($('#tenant_share_percent').val());
            var expenses = getCopraExpenses();
            var sharedTotal = 0;
            var landownerAddBackTotal = 0;
            var tenantAddBackTotal = 0;

            $.each(expenses, function(_, expense) {
                sharedTotal += expense.amount;

                if (expense.add_back_to === 'landowner') {
                    landownerAddBackTotal += expense.amount;
                } else {
                    tenantAddBackTotal += expense.amount;
                }
            });

            sharedTotal = roundCurrency(sharedTotal);
            landownerAddBackTotal = roundCurrency(landownerAddBackTotal);
            tenantAddBackTotal = roundCurrency(tenantAddBackTotal);

            var sharedTransferAmount = roundCurrency(sharedTotal / 2);
            var baseLandownerAmount = roundCurrency(purchaseTotal * (landownerSharePercent / 100));
            var baseTenantAmount = roundCurrency(purchaseTotal - baseLandownerAmount);
            var landownerSuggestedAmount = roundCurrency(baseLandownerAmount - sharedTransferAmount + landownerAddBackTotal);
            var tenantSuggestedAmount = roundCurrency(baseTenantAmount - sharedTransferAmount + tenantAddBackTotal);
            var validationMessage = '';
            var isValid = true;

            if (Math.abs((landownerSharePercent + tenantSharePercent) - 100) > 0.01) {
                isValid = false;
                validationMessage = invalidSplitMessages.total;
            } else if (landownerSuggestedAmount < -0.01 || tenantSuggestedAmount < -0.01) {
                isValid = false;
                validationMessage = invalidSplitMessages.negative;
            }

            return {
                landownerSharePercent: landownerSharePercent,
                tenantSharePercent: tenantSharePercent,
                baseLandownerAmount: baseLandownerAmount,
                baseTenantAmount: baseTenantAmount,
                landownerAddBackTotal: landownerAddBackTotal,
                tenantAddBackTotal: tenantAddBackTotal,
                landownerSuggestedAmount: Math.max(0, landownerSuggestedAmount),
                tenantSuggestedAmount: Math.max(0, tenantSuggestedAmount),
                isValid: isValid,
                validationMessage: validationMessage
            };
        }

        function getCashInputsTotal() {
            var supplierCash = parseNumericValue($('#amount_tendered').val());
            var partnerCash = parseNumericValue($('#partner_amount_tendered').val());

            if (supplierCash < 0) supplierCash = 0;
            if (partnerCash < 0) partnerCash = 0;

            return supplierCash + partnerCash;
        }

        function updatePrimaryPaymentMode(cashToPay) {
            if (!$('#store_negative_loan').length || !$('#amount_tendered').length) {
                if (copraSplitEnabled && $('#amount_tendered').length) {
                    $('#amount_tendered, #partner_amount_tendered').prop('readonly', true).addClass('disabled');
                }

                return;
            }

            if (copraSplitEnabled) {
                $('#amount_tendered, #partner_amount_tendered').prop('readonly', true).addClass('disabled');

                if ($('#store_negative_loan').is(':checked')) {
                    setFormattedInputValue('#amount_tendered', 0);
                }

                return;
            }

            $('#partner_amount_tendered').prop('readonly', false).removeClass('disabled');

            if ($('#store_negative_loan').is(':checked')) {
                setFormattedInputValue('#amount_tendered', 0);
                $('#amount_tendered').prop('readonly', true).addClass('disabled');

                if ($('#partner_amount_tendered').length) {
                    var partnerCash = parseNumericValue($('#partner_amount_tendered').val());
                    if (partnerCash > cashToPay) {
                        setFormattedInputValue('#partner_amount_tendered', cashToPay);
                    }
                }
            } else {
                $('#amount_tendered').prop('readonly', false).removeClass('disabled');
            }
        }

        function updateCopraSplitUi(summary, deduction, partnerDeduction) {
            if (!copraSplitEnabled) {
                return null;
            }

            if (summary === null) {
                return null;
            }

            var landownerNetCash = roundCurrency(summary.landownerSuggestedAmount - deduction);
            var tenantNetCash = roundCurrency(summary.tenantSuggestedAmount - partnerDeduction);

            $('#copra_initial_split_guide').text(
                landownerName + ' ' + summary.landownerSharePercent.toFixed(2) + '% / ' +
                tenantName + ' ' + summary.tenantSharePercent.toFixed(2) + '%'
            );
            $('#copra_initial_split_amounts').text(
                formatCurrency(summary.baseLandownerAmount) + ' to ' + landownerName + ' / ' +
                formatCurrency(summary.baseTenantAmount) + ' to ' + tenantName
            );
            $('#suggested_cash_to_landowner').text(formatCurrency(summary.landownerSuggestedAmount));
            $('#suggested_cash_to_tenant').text(formatCurrency(summary.tenantSuggestedAmount));

            if (summary.isValid) {
                if ($('#loan_deduction').length) {
                    $('#loan_deduction').attr('max', summary.landownerSuggestedAmount.toFixed(2));
                }

                if ($('#partner_loan_deduction').length) {
                    $('#partner_loan_deduction').attr('max', summary.tenantSuggestedAmount.toFixed(2));
                }

                $('#copra_split_validation_row').hide();
                $('#copra_split_validation_message').text('');
                $('#finish_receiving_button').prop('disabled', false);

                if ($('#store_negative_loan').length && $('#store_negative_loan').is(':checked')) {
                    setFormattedInputValue('#amount_tendered', 0);
                    setFormattedInputValue('#partner_amount_tendered', Math.max(0, tenantNetCash));
                } else {
                    setFormattedInputValue('#amount_tendered', Math.max(0, landownerNetCash));
                    setFormattedInputValue('#partner_amount_tendered', Math.max(0, tenantNetCash));
                }
            } else {
                $('#copra_split_validation_row').show();
                $('#copra_split_validation_message').text(summary.validationMessage);
                $('#finish_receiving_button').prop('disabled', true);
                setFormattedInputValue('#amount_tendered', 0);
                setFormattedInputValue('#partner_amount_tendered', 0);
            }

            updateCopraExpensePayload();

            return summary;
        }

        function updateNegativeLoanSummary(cashToPay) {
            if (!$('#store_negative_loan').length) {
                return;
            }

            var negativeLoan = 0;
            if ($('#store_negative_loan').is(':checked')) {
                negativeLoan = Math.max(0, cashToPay - getCashInputsTotal());
            }

            $('#negative_loan_amount').text(formatCurrency(negativeLoan));
            if ($('#store_negative_loan').is(':checked') && negativeLoan > 0) {
                $('#negative_loan_divider').show();
                $('#negative_loan_row').show();
            } else {
                $('#negative_loan_divider').hide();
                $('#negative_loan_row').hide();
            }
        }

        function updateCashToPay() {
            var deduction = parseNumericValue($('#loan_deduction').val());
            var partnerDeduction = parseNumericValue($('#partner_loan_deduction').val());

            if (deduction < 0) deduction = 0;
            if (deduction > maxLoanDeduction) deduction = maxLoanDeduction;
            if (partnerDeduction < 0) partnerDeduction = 0;
            if (partnerDeduction > maxPartnerDeduction) partnerDeduction = maxPartnerDeduction;

            var combined = deduction + partnerDeduction;
            if (combined > receivingTotal) {
                partnerDeduction = Math.max(0, receivingTotal - deduction);
            }

            if (copraSplitEnabled) {
                var copraSummary = getCopraSplitSummary(receivingTotal);
                if (copraSummary !== null) {
                    var maxLoanDeductionForShare = Math.max(0, Math.min(maxLoanDeduction, copraSummary.landownerSuggestedAmount));
                    var maxPartnerDeductionForShare = Math.max(0, Math.min(maxPartnerDeduction, copraSummary.tenantSuggestedAmount));

                    if (deduction > maxLoanDeductionForShare) {
                        deduction = maxLoanDeductionForShare;
                        $('#loan_deduction').val(deduction.toFixed(2));
                    }

                    if (partnerDeduction > maxPartnerDeductionForShare) {
                        partnerDeduction = maxPartnerDeductionForShare;
                        $('#partner_loan_deduction').val(partnerDeduction.toFixed(2));
                    }
                }
            }

            var cashToPay = receivingTotal - deduction - partnerDeduction;
            $('#cash_to_pay').text(formatCurrency(cashToPay));
            updatePrimaryPaymentMode(cashToPay);

            if (copraSplitEnabled) {
                updateCopraSplitUi(copraSummary, deduction, partnerDeduction);
            <?php if ($has_partner_supplier) { ?>
            } else if (!$('#store_negative_loan').is(':checked')) {
                var supplierCash = parseNumericValue($('#amount_tendered').val());
                if (supplierCash > cashToPay) supplierCash = cashToPay;
                setFormattedInputValue('#partner_amount_tendered', cashToPay - supplierCash);
            <?php } ?>
            }

            updateNegativeLoanSummary(cashToPay);
        }

        function syncShareInputs(changedField) {
            if (!copraSplitEnabled || shareSyncLock) {
                return;
            }

            var landownerSharePercent = normalizePercentage($('#landowner_share_percent').val());
            var tenantSharePercent = normalizePercentage($('#tenant_share_percent').val());

            shareSyncLock = true;

            if (changedField === 'landowner') {
                tenantSharePercent = roundCurrency(100 - landownerSharePercent);
                $('#tenant_share_percent').val(tenantSharePercent.toFixed(2));
                $('#landowner_share_percent').val(landownerSharePercent.toFixed(2));
            } else {
                landownerSharePercent = roundCurrency(100 - tenantSharePercent);
                $('#landowner_share_percent').val(landownerSharePercent.toFixed(2));
                $('#tenant_share_percent').val(tenantSharePercent.toFixed(2));
            }

            shareSyncLock = false;

            queueCopraSettingsSave();
            updateCashToPay();
        }

        if (copraSplitVisible && $('#copra_expense_body').length) {
            renderCopraExpenses($.isArray(initialCopraExpenses) ? initialCopraExpenses : []);

            $('#add_copra_expense_button').on('click', function() {
                var currentExpenses = getCopraExpenses();
                currentExpenses.push({
                    description: '',
                    amount: 0,
                    add_back_to: 'tenant'
                });

                renderCopraExpenses(currentExpenses);
                queueCopraSettingsSave();
            });

            $('#copra_expense_body').on('input change', '.copra-expense-description, .copra-expense-amount, .copra-expense-add-back-to', function() {
                updateCopraExpensePayload();
                queueCopraSettingsSave();
                updateCashToPay();
            });

            $('#copra_expense_body').on('click', '.copra-expense-delete', function() {
                $(this).closest('.copra-expense-row').remove();

                if (!$('#copra_expense_body .copra-expense-row').length) {
                    renderCopraExpenses([]);
                } else {
                    updateCopraExpensePayload();
                }

                queueCopraSettingsSave();
                updateCashToPay();
            });

            $('#landowner_share_percent').on('input change', function() {
                syncShareInputs('landowner');
            });

            $('#tenant_share_percent').on('input change', function() {
                syncShareInputs('tenant');
            });

            if ($('#landowner_share_percent').length) {
                $('#landowner_share_percent').val(normalizePercentage($('#landowner_share_percent').val()).toFixed(2));
            }

            if ($('#tenant_share_percent').length) {
                $('#tenant_share_percent').val(normalizePercentage($('#tenant_share_percent').val()).toFixed(2));
            }

            updateCopraExpensePayload();
        } else {
            $('#finish_receiving_button').prop('disabled', false);
        }

        $('#loan_deduction, #partner_loan_deduction').on('input change', updateCashToPay);
        $('#store_negative_loan').on('change', updateCashToPay);

        <?php if ($has_partner_supplier) { ?>
        $('#amount_tendered').on('input change', function() {
            if (copraSplitEnabled) {
                return;
            }

            if ($('#store_negative_loan').is(':checked')) {
                setFormattedInputValue('#amount_tendered', 0);
                return;
            }

            var cashToPay = parseFloat($('#cash_to_pay').text().replace(/[^0-9.-]/g, '')) || 0;
            var supplierCash = parseNumericValue($(this).val());
            if (supplierCash < 0) supplierCash = 0;
            if (supplierCash > cashToPay) supplierCash = cashToPay;
            setFormattedInputValue('#partner_amount_tendered', cashToPay - supplierCash);
            updateNegativeLoanSummary(cashToPay);
        });
        <?php } ?>

        $('#amount_tendered, #partner_amount_tendered').on('input change', function() {
            if (copraSplitEnabled) {
                return;
            }

            var cashToPay = parseFloat($('#cash_to_pay').text().replace(/[^0-9.-]/g, '')) || 0;
            updateNegativeLoanSummary(cashToPay);
        });

        $('#amount_tendered, #partner_amount_tendered').on('blur', function() {
            normalizeFormattedInput('#' + this.id);
        });

        $('#finish_receiving_form').on('submit', function() {
            if ($('#amount_tendered').length) {
                $('#amount_tendered').val(roundCurrency(parseNumericValue($('#amount_tendered').val())).toFixed(2));
            }

            if ($('#partner_amount_tendered').length) {
                $('#partner_amount_tendered').val(roundCurrency(parseNumericValue($('#partner_amount_tendered').val())).toFixed(2));
            }
        });

        updateCashToPay();
            <?php } ?>

    });
</script>

<?= view('partial/footer') ?>
