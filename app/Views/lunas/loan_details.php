<?php
/**
 * @var object      $luna_info
 * @var object      $supplier_info
 * @var int|null    $customer_id
 * @var string      $loan_party
 * @var string      $loan_balance
 */
?>

<?php
$total_balance       = (float) $loan_balance;
$total_balance_style = $total_balance > 0
    ? 'color:#d9534f; font-weight:bold;'
    : ($total_balance < 0 ? 'color:#5cb85c; font-weight:bold;' : '');
?>

<div class="container-fluid">
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12">
            <h4 style="margin-top: 0; margin-bottom: 5px;"><?= esc($loan_party) ?></h4>
            <div><strong><?= esc(lang('Lunas.luna_name')) ?>:</strong> <?= esc((string) ($luna_info->luna_name ?? '')) ?></div>
            <div><strong><?= esc(lang('Customers.loan_balance')) ?>:</strong> <span<?= $total_balance_style !== '' ? ' style="' . esc($total_balance_style) . '"' : '' ?>><?= to_currency($loan_balance) ?></span></div>
        </div>
    </div>

    <?php if ($customer_id === null) { ?>
        <div class="alert alert-warning" style="margin-bottom: 0;"><?= esc(lang('Suppliers.no_linked_customer_account')) ?></div>
    <?php } else { ?>
        <div class="panel panel-default" style="margin-bottom: 0;">
            <div class="panel-heading"><strong><?= esc(lang('Reports.loan_history')) ?></strong></div>
            <div class="panel-body">
                <table id="luna_loan_history_table"></table>
            </div>
        </div>
    <?php } ?>
</div>

<?php if ($customer_id !== null) { ?>
    <script type="text/javascript">
        $(document).ready(function () {
            <?= view('partial/bootstrap_tables_locale') ?>

            $('#luna_loan_history_table').bootstrapTable({
                url: '<?= esc(site_url('lunas/loanHistory/' . (int) $luna_info->luna_id . '/' . (int) $supplier_info->person_id)) ?>',
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
