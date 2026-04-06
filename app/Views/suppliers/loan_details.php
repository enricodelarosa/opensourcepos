<?php
/**
 * @var object   $supplier_info
 * @var int|null $customer_id
 * @var string   $loan_balance
 * @var array    $breakdown
 */
?>

<div class="container-fluid">
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-xs-12">
            <h4 style="margin-top: 0; margin-bottom: 5px;"><?= esc(trim(($supplier_info->first_name ?? '') . ' ' . ($supplier_info->last_name ?? ''))) ?></h4>
            <div><strong><?= esc(lang('Customers.loan_balance')) ?>:</strong> <span style="color:#d9534f; font-weight:bold;"><?= to_currency((float) $loan_balance) ?></span></div>
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
                                    $label = lang('Reports.general_advance');
                                    if (! empty($row['luna_id'])) {
                                        $label = trim((string) ($row['area_name'] ?? ''));
                                        if (! empty($row['barangay'])) {
                                            $label .= ' (' . trim((string) $row['barangay']) . ')';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= esc($label) ?></td>
                                        <td style="text-align: right;"><?= to_currency((float) ($row['balance'] ?? 0)) ?></td>
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
