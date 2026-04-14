<?php
/**
 * @var bool   $print_after_sale
 * @var string $transaction_time
 * @var int    $receiving_id
 * @var string $employee
 * @var array  $cart
 * @var bool   $show_stock_locations
 * @var float  $total
 * @var string $mode
 * @var string $payment_type
 * @var float  $amount_tendered
 * @var float  $amount_change
 * @var string $barcode
 * @var array  $config
 * @var float|null $landowner_share_percent
 * @var float|null $tenant_share_percent
 * @var array $copra_expenses
 */
?>

<?= view('partial/header') ?>

<?php
if (isset($error_message)) {
    echo '<div class="alert alert-dismissible alert-danger">' . esc($error_message) . '</div>';

    exit;
}

echo view('partial/print_receipt', ['print_after_sale', $print_after_sale, 'selected_printer' => 'receipt_printer']) ?>

<?php
$copra_expenses ??= [];
$loan_deduction ??= 0.0;
$partner_loan_deduction ??= 0.0;
$negative_loan_amount ??= 0.0;
$amount_tendered ??= 0.0;
$partner_amount_tendered ??= 0.0;
$has_supplier = ! empty($supplier);
$landowner_display_name = trim((string) ($supplier ?? lang('Reports.landowner')));
$tenant_display_name    = trim((string) ($partner_supplier_name ?? lang('Reports.tenant')));
$show_copra_split_breakdown = $has_supplier && $landowner_share_percent !== null && $tenant_share_percent !== null;
$show_copra_expenses        = $show_copra_split_breakdown && ! empty($copra_expenses);
$landowner_base_share       = null;
$tenant_base_share          = null;
$shared_total               = 0.0;
$shared_transfer_amount     = 0.0;
$net_amount_for_split       = null;
$landowner_split_share      = null;
$tenant_split_share         = null;
$has_shared_expense_transfer = false;
$landowner_share_after_split  = null;
$tenant_share_after_split     = null;

$format_adjustment = static function (float $amount): string {
    if ($amount > 0) {
        return '+' . to_currency($amount);
    }

    if ($amount < 0) {
        return '-' . to_currency(abs($amount));
    }

    return to_currency(0);
};

if ($show_copra_split_breakdown) {
    foreach ($copra_expenses as $expense) {
        $amount = (float) ($expense['amount'] ?? 0);
        $shared_total += $amount;
    }

    $shared_transfer_amount       = round($shared_total / 2, 2);
    $has_shared_expense_transfer  = $shared_transfer_amount > 0.009;
    $landowner_base_share         = round($total * ($landowner_share_percent / 100), 2);
    $tenant_base_share            = round($total - $landowner_base_share, 2);
    $landowner_share_after_split  = round($landowner_base_share - $shared_transfer_amount, 2);
    $tenant_share_after_split     = round($tenant_base_share + $shared_transfer_amount, 2);
    $net_amount_for_split         = round($total - $shared_total, 2);
    $landowner_split_share        = $landowner_share_after_split;
    $tenant_split_share           = round($tenant_share_after_split - $shared_total, 2);
}
?>

<div class="print_hide" id="control_buttons" style="text-align: right;">
    <a href="javascript:printdoc();">
        <div class="btn btn-info btn-sm" id="show_print_button"><?= '<span class="glyphicon glyphicon-print">&nbsp;</span>' . lang('Common.print') ?></div>
    </a>
    <?= anchor('receivings', '<span class="glyphicon glyphicon-save">&nbsp;</span>' . lang('Receivings.register'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button']) ?>
</div>

<div id="receipt_wrapper">
    <div id="receipt_header">
        <?php if ($config['company_logo'] !== '') { ?>
            <div id="company_name">
                <img id="image" src="<?= base_url('uploads/' . esc($config['company_logo'], 'url')) ?>" alt="company_logo">
            </div>
        <?php } ?>

        <?php if ($config['receipt_show_company_name']) { ?>
            <div id="company_name"><?= esc($config['company']) ?></div>
        <?php } ?>

        <div id="company_address"><?= nl2br(esc($config['address'])) ?></div>
        <div id="company_phone"><?= esc($config['phone']) ?></div>
        <div id="sale_receipt"><?= lang('Receivings.receipt') ?></div>
        <div id="sale_time"><?= esc($transaction_time) ?></div>
    </div>

    <div id="receipt_general_info">
        <?php
        $receipt_luna_label = '';
        if (isset($selected_luna) && $selected_luna) {
            $receipt_luna_label = $selected_luna->area_name;
            if (! empty($selected_luna->barangay)) {
                $receipt_luna_label .= ' (' . $selected_luna->barangay . ')';
            }
        }
        ?>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <?php if (isset($supplier)) { ?>
                        <div id="customer"><?= lang('Suppliers.supplier') . esc(": {$supplier}") ?></div>
                    <?php } ?>
                    <?php if ($receipt_luna_label !== '') { ?>
                        <div id="luna"><?= lang('Suppliers.luna') . esc(": {$receipt_luna_label}") ?></div>
                    <?php } ?>
                    <div id="sale_id"><?= lang('Receivings.id') . ": {$receiving_id}" ?></div>
                    <?php if (! empty($reference)) { ?>
                        <div id="reference"><?= lang('Receivings.reference') . esc(": {$reference}") ?></div>
                    <?php } ?>
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <?php if ($receipt_luna_label !== '') { ?>
                        <div id="last_harvest"><?= lang('Suppliers.last_harvest') . esc(': ' . ($selected_luna->last_harvest_date ?? lang('Suppliers.no_harvest_recorded'))) ?></div>
                        <div id="next_expected_harvest"><?= lang('Suppliers.next_expected_harvest') . esc(': ' . ($selected_luna->next_expected_harvest_date ?? lang('Suppliers.no_harvest_recorded'))) ?></div>
                    <?php } ?>
                </td>
            </tr>
        </table>
    </div>

    <table id="receipt_items">
        <tr>
            <th style="width: 40%;"><?= lang('Items.item') ?></th>
            <th style="width: 20%;"><?= lang('Common.price') ?></th>
            <th style="width: 20%;"><?= lang('Sales.quantity') ?></th>
            <th style="width: 15%; text-align: right;"><?= lang('Sales.total') ?></th>
        </tr>

        <?php foreach (array_reverse($cart, true) as $line => $item) { ?>
            <tr>
                <td><?= esc($item['name'] . ' ' . $item['attribute_values']) ?></td>
                <td><?= to_currency($item['price']) ?></td>
                <td><?= to_quantity_decimals($item['quantity']) . ' ' . ($show_stock_locations ? ' [' . esc($item['stock_name']) . ']' : '') ?>&nbsp;&nbsp;&nbsp;x <?= $item['receiving_quantity'] !== 0 ? to_quantity_decimals($item['receiving_quantity']) : 1 ?></td>
                <td><div class="total-value"><?= to_currency($item['total']) ?></div></td>
            </tr>
            <tr>
                <td><?= esc($item['serialnumber']) ?></td>
            </tr>
            <?php if ($item['discount'] > 0) { ?>
                <tr>
                    <?php if ($item['discount_type'] === FIXED) { ?>
                        <td colspan="3" class="discount"><?= to_currency($item['discount']) . ' ' . lang('Sales.discount') ?></td>
                    <?php } elseif ($item['discount_type'] === PERCENT) { ?>
                        <td colspan="3" class="discount"><?= to_decimals($item['discount']) . ' ' . lang('Sales.discount_included') ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
        <?php } ?>
        <tr>
            <td colspan="3" style="text-align: right; border-top: 2px solid #000000;"><?= lang('Sales.total') ?></td>
            <td style="border-top: 2px solid #000000;">
                <div class="total-value"><?= to_currency($total) ?></div>
            </td>
        </tr>
        <?php if ($show_copra_split_breakdown && $has_shared_expense_transfer) { ?>
            <tr>
                <td colspan="3" style="text-align: right;"><?= lang('Receivings.shared_expenses_deducted_from_total') ?></td>
                <td>
                    <div class="total-value"><?= esc($format_adjustment(-$shared_total)) ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right;"><?= lang('Receivings.net_amount_for_split') ?></td>
                <td>
                    <div class="total-value"><?= to_currency((float) $net_amount_for_split) ?></div>
                </td>
            </tr>
        <?php } ?>
        <?php if ($show_copra_split_breakdown) { ?>
            <tr>
                <td colspan="4" style="padding-top: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th style="width: 34%; text-align: left; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0;"><?= lang('Receivings.copra_split_breakdown') ?></th>
                            <th style="width: 33%; text-align: right; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0;">
                                <?= esc(lang('Reports.landowner')) ?><br>
                                <span style="font-weight: normal; color: #666;"><?= esc($landowner_display_name) ?></span>
                            </th>
                            <th style="width: 33%; text-align: right; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0;">
                                <?= esc(lang('Reports.tenant')) ?><br>
                                <span style="font-weight: normal; color: #666;"><?= esc($tenant_display_name) ?></span>
                            </th>
                        </tr>
                        <tr>
                            <td style="padding-top: 4px;"><?= lang('Receivings.initial_split_guide') ?></td>
                            <td style="text-align: right; padding-top: 4px;"><?= to_decimals($landowner_share_percent) ?>%</td>
                            <td style="text-align: right; padding-top: 4px;"><?= to_decimals($tenant_share_percent) ?>%</td>
                        </tr>
                        <?php if ($has_shared_expense_transfer) { ?>
                            <tr>
                                <td><?= lang('Receivings.split_share') ?></td>
                                <td style="text-align: right;"><?= to_currency((float) $landowner_split_share) ?></td>
                                <td style="text-align: right;"><?= to_currency((float) $tenant_split_share) ?></td>
                            </tr>
                            <tr>
                                <td><?= lang('Receivings.shared_expense_added_to_tenant') ?></td>
                                <td style="text-align: right;"><?= to_currency(0) ?></td>
                                <td style="text-align: right;"><?= esc($format_adjustment($shared_total)) ?></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td><?= lang('Receivings.base_share') ?></td>
                                <td style="text-align: right;"><?= to_currency((float) $landowner_base_share) ?></td>
                                <td style="text-align: right;"><?= to_currency((float) $tenant_base_share) ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td><strong><?= lang('Receivings.share_after_split') ?></strong></td>
                            <td style="text-align: right;"><strong><?= to_currency((float) $landowner_share_after_split) ?></strong></td>
                            <td style="text-align: right;"><strong><?= to_currency((float) $tenant_share_after_split) ?></strong></td>
                        </tr>
                        <tr>
                            <td><?= lang('Receivings.loan_deduction') ?></td>
                            <td style="text-align: right;"><?= $loan_deduction > 0 ? '-' . to_currency((float) $loan_deduction) : to_currency(0) ?></td>
                            <td style="text-align: right;"><?= $partner_loan_deduction > 0 ? '-' . to_currency((float) $partner_loan_deduction) : to_currency(0) ?></td>
                        </tr>
                        <?php if ($negative_loan_amount > 0) { ?>
                            <tr>
                                <td><?= lang('Receivings.remaining_as_landowner_negative_loan') ?></td>
                                <td style="text-align: right;"><?= to_currency((float) $negative_loan_amount) ?></td>
                                <td style="text-align: right;"><?= to_currency(0) ?></td>
                            </tr>
                        <?php } ?>
                        <?php if (isset($loan_balance_after) || isset($partner_loan_balance_after)) { ?>
                            <tr>
                                <td><?= lang('Receivings.loan_balance_after') ?></td>
                                <td style="text-align: right;"><?= isset($loan_balance_after) ? to_currency((float) $loan_balance_after) : '-' ?></td>
                                <td style="text-align: right;"><?= isset($partner_loan_balance_after) ? to_currency((float) $partner_loan_balance_after) : '-' ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td style="border-top: 1px solid #000; padding-top: 4px;"><strong><?= lang('Receivings.cash_after_loan') ?></strong></td>
                            <td style="text-align: right; border-top: 1px solid #000; padding-top: 4px;"><strong><?= to_currency((float) $amount_tendered) ?></strong></td>
                            <td style="text-align: right; border-top: 1px solid #000; padding-top: 4px;"><strong><?= to_currency((float) $partner_amount_tendered) ?></strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php } ?>
        <?php if ($show_copra_expenses) { ?>
            <tr>
                <td colspan="3" style="text-align: right;"><strong><?= lang('Receivings.copra_expenses') ?></strong></td>
                <td></td>
            </tr>
            <?php foreach ($copra_expenses as $expense) { ?>
                <?php $expense_description = trim((string) ($expense['description'] ?? '')); ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= esc($expense_description) ?></td>
                    <td>
                        <div class="total-value"><?= to_currency((float) ($expense['amount'] ?? 0)) ?></div>
                    </td>
                </tr>
            <?php } ?>
        <?php } ?>
        <?php if ($mode !== 'requisition') { ?>
            <tr>
                <td colspan="3" style="text-align: right;"><?= lang('Sales.payment') ?></td>
                <td>
                    <div class="total-value"><?= esc($payment_type) ?></div>
                </td>
            </tr>

            <?php
            $partner_loan_deduction ??= 0;
            $negative_loan_amount ??= 0;
            $has_any_loan_activity = (isset($loan_deduction) && $loan_deduction > 0) || $partner_loan_deduction > 0 || $negative_loan_amount > 0;
            ?>
            <?php if (! $show_copra_split_breakdown && $has_any_loan_activity) { ?>
                <?php if ((isset($loan_deduction) && $loan_deduction > 0) || $negative_loan_amount > 0) { ?>
                <?php if ($receipt_luna_label !== '') { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong><?= esc($supplier ?? '') . ' - ' . esc($receipt_luna_label) ?></strong></td>
                    <td></td>
                </tr>
                <?php } ?>
                <?php if (isset($loan_deduction) && $loan_deduction > 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong><?= lang('Sales.loan_deduction') ?></strong></td>
                    <td>
                        <div class="total-value" style="color: #d9534f;"><strong>-<?= to_currency($loan_deduction) ?></strong></div>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($negative_loan_amount > 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong><?= lang('Receivings.remaining_as_landowner_negative_loan') ?><?= ! empty($supplier) ? ' - ' . esc($supplier) : '' ?></strong></td>
                    <td>
                        <div class="total-value" style="color: #5cb85c;"><strong>-<?= to_currency($negative_loan_amount) ?></strong></div>
                    </td>
                </tr>
                <?php } ?>
                <?php if (isset($loan_balance_after)) { ?>
                    <tr>
                        <td colspan="3" style="text-align: right;"><?= $receipt_luna_label !== '' ? lang('Receivings.luna_loan_balance') : lang('Receivings.loan_balance') ?></td>
                        <td>
                            <div class="total-value"><?= to_currency($loan_balance_after) ?></div>
                        </td>
                    </tr>
                <?php } ?>
                <?php } ?>
                <?php if ($partner_loan_deduction > 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;">
                        <strong>
                            <?= lang('Suppliers.tenant') ?><?= isset($partner_supplier_name) ? ': ' . esc($partner_supplier_name) : '' ?>
                            <?php if ($receipt_luna_label !== ''): ?>
                                <?= ' - ' . esc($receipt_luna_label) ?>
                            <?php endif; ?>
                        </strong>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= lang('Receivings.loan_deduction') ?></td>
                    <td>
                        <div class="total-value" style="color: #d9534f;"><strong>-<?= to_currency($partner_loan_deduction) ?></strong></div>
                    </td>
                </tr>
                <?php if (isset($partner_loan_balance_after)) { ?>
                    <tr>
                        <td colspan="3" style="text-align: right;"><?= lang('Receivings.luna_loan_balance') ?></td>
                        <td>
                            <div class="total-value"><?= to_currency($partner_loan_balance_after) ?></div>
                        </td>
                    </tr>
                <?php } ?>
                <?php } ?>
                <?php
                    $partner_amount_tendered ??= 0;
                $primary_cash = (float) ($amount_tendered ?? 0);
                ?>
                <?php if ($primary_cash > 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= lang('Receivings.cash_to_landowner') ?><?= ! empty($supplier) ? ' - ' . esc($supplier) : '' ?></td>
                    <td>
                        <div class="total-value"><?= to_currency($primary_cash) ?></div>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($partner_amount_tendered > 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= lang('Receivings.cash_to_tenant') ?><?= ! empty($partner_supplier_name) ? ' - ' . esc($partner_supplier_name) : '' ?></td>
                    <td>
                        <div class="total-value"><?= to_currency($partner_amount_tendered) ?></div>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($partner_amount_tendered <= 0 && $primary_cash <= 0) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= ! empty($selected_luna) ? lang('Receivings.cash_to_landowner') . (! empty($supplier) ? ' - ' . esc($supplier) : '') : lang('Sales.amount_tendered') ?></td>
                    <td>
                        <div class="total-value"><?= to_currency($primary_cash) ?></div>
                    </td>
                </tr>
                <?php } ?>
            <?php } elseif (! $show_copra_split_breakdown && isset($amount_change)) { ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><?= lang('Sales.amount_tendered') ?></td>
                    <td>
                        <div class="total-value"><?= to_currency($amount_tendered) ?></div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="text-align: right;"><?= lang('Sales.change_due') ?></td>
                    <td>
                        <div class="total-value"><?= $amount_change ?></div>
                    </td>
                </tr>
            <?php } ?>
        <?php } ?>
    </table>

    <div id="sale_return_policy">
        <?= nl2br(esc($config['return_policy'])) ?>
    </div>

    <div id="barcode">
        <?= $barcode ?><br>
        <?= $receiving_id ?>
    </div>
</div>

<?= view('partial/footer') ?>
