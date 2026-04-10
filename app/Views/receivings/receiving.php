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
 */
?>

<?= view('partial/header') ?>

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
                <th style="width: 15%;"><?= lang('Sales.item_number') ?></th>
                <th style="width: 23%;"><?= lang(ucfirst($controller_name) . '.item_name') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.cost') ?></th>
                <th style="width: 8%;"><?= lang(ucfirst($controller_name) . '.quantity') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.ship_pack') ?></th>
                <th style="width: 14%;"><?= lang(ucfirst($controller_name) . '.discount') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 5%;"><?= lang(ucfirst($controller_name) . '.update') ?></th>
            </tr>
        </thead>

        <tbody id="cart_contents">
            <?php if (count($cart) === 0) { ?>
                <tr>
                    <td colspan="9">
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
                        <td>
                            <?= form_dropdown(
                                'receiving_quantity',
                                $item['receiving_quantity_choices'],
                                $item['receiving_quantity'],
                                ['class' => 'form-control input-sm'],
                            ) ?>
                        </td>

                        <?php if ($items_module_allowed && $mode !== 'requisition') { ?>
                            <td>
                                <div class="input-group">
                                    <?= form_input(['name' => 'discount', 'class' => 'form-control input-sm', 'value' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'onClick' => 'this.select();']) ?>
                                    <span class="input-group-btn">
                                        <?= form_checkbox([
                                            'id'           => 'discount_toggle',
                                            'name'         => 'discount_toggle',
                                            'value'        => 1,
                                            'data-toggle'  => 'toggle',
                                            'data-size'    => 'small',
                                            'data-onstyle' => 'success',
                                            'data-on'      => '<b>' . $config['currency_symbol'] . '</b>',
                                            'data-off'     => '<b>%</b>',
                                            'data-line'    => $line,
                                            'checked'      => $item['discount_type'] === 1,
                                        ]) ?>
                                    </span>
                                </div>
                            </td>
                        <?php } else { ?>
                            <td><?= $item['discount'] ?></td>
                            <?= form_hidden('discount', (string) $item['discount']) ?>
                        <?php } ?>
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
                        <td colspan="7"></td>
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
                                                'value'       => '',
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
                                                'value'       => '',
                                                'class'       => 'form-control input-sm',
                                                'size'        => '5',
                                                'placeholder' => '0.00',
                                            ]) ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <!-- Cash to pay summary -->
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
            source: '<?= 'suppliers/suggest' ?>',
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

        $('[name="discount_toggle"]').change(function() {
            var input = $("<input>").attr("type", "hidden").attr("name", "discount_type").val(($(this).prop('checked')) ? 1 : 0);
            $('#cart_' + $(this).attr('data-line')).append($(input));
            $('#cart_' + $(this).attr('data-line')).submit();
        });

        <?php if ($has_linked_customer || ($partner_customer_id && $partner_loan_balance > 0) || $has_partner_supplier) { ?>
        // Auto-calculate remaining cash to pay when loan deductions change
        var receivingTotal = <?= json_encode((float) $total) ?>;
        var maxLoanDeduction = Math.max(0, Math.min(<?= json_encode((float) ($loan_balance ?? 0)) ?>, receivingTotal));
        var maxPartnerDeduction = Math.max(0, Math.min(<?= json_encode((float) ($partner_loan_balance ?? 0)) ?>, receivingTotal));
        var currencySymbol = <?= json_encode($config['currency_symbol']) ?>;

        function getCashInputsTotal() {
            var supplierCash = parseFloat($('#amount_tendered').val()) || 0;
            var partnerCash = parseFloat($('#partner_amount_tendered').val()) || 0;

            if (supplierCash < 0) supplierCash = 0;
            if (partnerCash < 0) partnerCash = 0;

            return supplierCash + partnerCash;
        }

        function updatePrimaryPaymentMode(cashToPay) {
            if (!$('#store_negative_loan').length || !$('#amount_tendered').length) {
                return;
            }

            if ($('#store_negative_loan').is(':checked')) {
                $('#amount_tendered').val('0.00');
                $('#amount_tendered').prop('readonly', true).addClass('disabled');

                if ($('#partner_amount_tendered').length) {
                    var partnerCash = parseFloat($('#partner_amount_tendered').val()) || 0;
                    if (partnerCash > cashToPay) {
                        $('#partner_amount_tendered').val(cashToPay.toFixed(2));
                    }
                }
            } else {
                $('#amount_tendered').prop('readonly', false).removeClass('disabled');
            }
        }

        function updateNegativeLoanSummary(cashToPay) {
            if (!$('#store_negative_loan').length) {
                return;
            }

            var negativeLoan = 0;
            if ($('#store_negative_loan').is(':checked')) {
                negativeLoan = Math.max(0, cashToPay - getCashInputsTotal());
            }

            $('#negative_loan_amount').text(currencySymbol + negativeLoan.toFixed(2));
            if ($('#store_negative_loan').is(':checked') && negativeLoan > 0) {
                $('#negative_loan_divider').show();
                $('#negative_loan_row').show();
            } else {
                $('#negative_loan_divider').hide();
                $('#negative_loan_row').hide();
            }
        }

        function updateCashToPay() {
            var deduction = parseFloat($('#loan_deduction').val()) || 0;
            var partnerDeduction = parseFloat($('#partner_loan_deduction').val()) || 0;

            if (deduction < 0) deduction = 0;
            if (deduction > maxLoanDeduction) deduction = maxLoanDeduction;
            if (partnerDeduction < 0) partnerDeduction = 0;
            if (partnerDeduction > maxPartnerDeduction) partnerDeduction = maxPartnerDeduction;

            var combined = deduction + partnerDeduction;
            if (combined > receivingTotal) {
                partnerDeduction = Math.max(0, receivingTotal - deduction);
            }

            var cashToPay = receivingTotal - deduction - partnerDeduction;
            $('#cash_to_pay').text(currencySymbol + cashToPay.toFixed(2));
            updatePrimaryPaymentMode(cashToPay);
            updateNegativeLoanSummary(cashToPay);

            if (cashToPay <= 0) {
                $('#cash_payment_row, #amount_tendered_row').hide();
            } else {
                $('#cash_payment_row, #amount_tendered_row').show();
            }

            <?php if ($has_partner_supplier) { ?>
            // Auto-fill supplier cash; partner cash gets the remainder
            if (!$('#store_negative_loan').is(':checked')) {
                var supplierCash = parseFloat($('#amount_tendered').val()) || 0;
                if (supplierCash > cashToPay) supplierCash = cashToPay;
                $('#partner_amount_tendered').val((cashToPay - supplierCash).toFixed(2));
            }
            <?php } ?>
        }

        $('#loan_deduction, #partner_loan_deduction').on('input change', updateCashToPay);
        $('#store_negative_loan').on('change', updateCashToPay);

        <?php if ($has_partner_supplier) { ?>
        // When supplier cash changes, partner cash auto-fills the remainder
        $('#amount_tendered').on('input change', function() {
            if ($('#store_negative_loan').is(':checked')) {
                $(this).val('0.00');
                return;
            }

            var cashToPay = parseFloat($('#cash_to_pay').text().replace(/[^0-9.-]/g, '')) || 0;
            var supplierCash = parseFloat($(this).val()) || 0;
            if (supplierCash < 0) supplierCash = 0;
            if (supplierCash > cashToPay) supplierCash = cashToPay;
            $('#partner_amount_tendered').val((cashToPay - supplierCash).toFixed(2));
            updateNegativeLoanSummary(cashToPay);
        });
        <?php } ?>

        $('#amount_tendered, #partner_amount_tendered').on('input change', function() {
            var cashToPay = parseFloat($('#cash_to_pay').text().replace(/[^0-9.-]/g, '')) || 0;
            updateNegativeLoanSummary(cashToPay);
        });

        updateCashToPay();
        <?php } ?>

    });
</script>

<?= view('partial/footer') ?>
