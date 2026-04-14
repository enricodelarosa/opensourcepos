<?php
/**
 * @var object   $supplier_info
 * @var int|null $customer_id
 * @var string   $loan_balance
 * @var array    $breakdown
 */
?>

<?php
$total_balance       = (float) $loan_balance;
$total_balance_style = $total_balance > 0
    ? 'color:#d9534f; font-weight:bold;'
    : ($total_balance < 0 ? 'color:#5cb85c; font-weight:bold;' : '');
$supplier_name       = trim(($supplier_info->first_name ?? '') . ' ' . ($supplier_info->last_name ?? ''));
$supplier_category   = (int) ($supplier_info->category ?? 0);
$supplier_role       = match ($supplier_category) {
    LAND_OWNER_SUPPLIER => lang('Suppliers.land_owner'),
    TENANT_SUPPLIER => lang('Suppliers.tenant'),
    default => '',
};
?>

<div class="container-fluid">
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12">
            <h4 style="margin-top: 0; margin-bottom: 5px;"><?= esc($supplier_name . ($supplier_role !== '' ? ' - ' . $supplier_role : '')) ?></h4>
            <div><strong><?= esc(lang('Customers.loan_balance')) ?>:</strong> <span<?= $total_balance_style !== '' ? ' style="' . esc($total_balance_style) . '"' : '' ?>><?= to_currency($total_balance) ?></span></div>
        </div>
    </div>

    <?php if ($customer_id === null) { ?>
        <div class="alert alert-warning" style="margin-bottom: 0;"><?= esc(lang('Suppliers.no_linked_customer_account')) ?></div>
    <?php } else { ?>
        <div class="panel panel-default">
            <div class="panel-heading"><strong><?= esc(lang('Reports.loan_breakdown')) ?></strong></div>
            <div class="panel-body" style="padding-bottom: 0;">
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th><?= esc(lang('Reports.luna')) ?></th>
                                <th style="text-align: right;"><?= esc(lang('Customers.loan_balance')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($breakdown)) { ?>
                                <tr>
                                    <td colspan="2" style="text-align: center;"><?= esc(lang('Reports.no_data_to_display')) ?></td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($breakdown as $row) { ?>
                                    <?php
                                    $label              = lang('Reports.general_advance');
                                    $relationship_name  = '';
                                    $relationship_role  = '';
                                    $balance            = (float) ($row['balance'] ?? 0);
                                    $balance_style      = $balance > 0
                                        ? 'color:#d9534f; font-weight:bold;'
                                        : ($balance < 0 ? 'color:#5cb85c; font-weight:bold;' : '');
                                    if (! empty($row['luna_id'])) {
                                        $label = trim((string) ($row['area_name'] ?? ''));
                                        if (! empty($row['barangay'])) {
                                            $label .= ' (' . trim((string) $row['barangay']) . ')';
                                        }
                                        if ($supplier_category === LAND_OWNER_SUPPLIER) {
                                            $relationship_name = trim((string) (($row['tenant_name'] ?? '') !== '' ? $row['tenant_name'] : lang('Suppliers.no_tenant_assigned')));
                                            $relationship_role = lang('Suppliers.tenant');
                                        } elseif ($supplier_category === TENANT_SUPPLIER) {
                                            $relationship_name = trim((string) ($row['landowner_name'] ?? ''));
                                            $relationship_role = lang('Suppliers.land_owner');
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?= esc($label) ?>
                                            <?php if ($relationship_name !== '') { ?>
                                                <span class="text-muted">
                                                    -
                                                    <?= esc($relationship_role) ?>:
                                                    <?= esc($relationship_name) ?>
                                                </span>
                                            <?php } ?>
                                        </td>
                                        <td style="text-align: right;<?= $balance_style !== '' ? ' ' . esc($balance_style) : '' ?>"><?= to_currency($balance) ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel panel-default" style="margin-bottom: 0;">
            <div class="panel-heading"><strong><?= esc(lang('Reports.loan_history')) ?></strong></div>
            <div class="panel-body">
                <table id="supplier_loan_history_table"></table>
            </div>
        </div>
    <?php } ?>
</div>

<?php if ($customer_id !== null) { ?>
    <script type="text/javascript">
        $(document).ready(function () {
            <?= view('partial/bootstrap_tables_locale') ?>

            $('#supplier_loan_history_table').bootstrapTable({
                url: '<?= esc(site_url('suppliers/loanHistory/' . (int) $supplier_info->person_id)) ?>',
                sidePagination: 'server',
                pagination: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100],
                search: false,
                showColumns: false,
                uniqueId: 'loan_id',
                iconSize: 'sm',
                paginationVAlign: 'bottom',
                escape: true,
                columns: [
                    {field: 'transaction_time', title: '<?= esc(lang('Reports.date')) ?>', sortable: false},
                    {field: 'transaction_type', title: '<?= esc(lang('Reports.loan_transaction_type')) ?>', sortable: false},
                    {field: 'reference', title: '<?= esc(lang('Reports.loan_reference')) ?>', sortable: false},
                    {field: 'luna_label', title: '<?= esc(lang('Reports.luna')) ?>', sortable: false},
                    {field: 'loan_amount', title: '<?= esc(lang('Reports.loan_amount')) ?>', sortable: false},
                    {field: 'comment', title: '<?= esc(lang('Reports.comments')) ?>', sortable: false}
                ]
            });
        });
    </script>
<?php } ?>
