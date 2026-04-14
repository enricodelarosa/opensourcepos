<?php
/**
 * @var string $title
 * @var string $subtitle
 * @var array  $overall_summary_data
 * @var array  $context_data
 * @var array  $details_data
 * @var array  $headers
 * @var array  $summary_data
 * @var array  $config
 */
?>

<?= view('partial/header') ?>

<div id="page_title"><?= esc($title) ?></div>

<div id="page_subtitle"><?= esc($subtitle) ?></div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <!-- Toggle Button -->
        <button id="toggleCostProfitButton" class="btn btn-default btn-sm print_hide">
            <?= lang('Reports.toggle_cost_and_profit'); ?>
        </button>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<div id="report_summary">
    <?php foreach ($overall_summary_data as $name => $value) { ?>
        <div class="summary_row"><?= lang("Reports.{$name}") . ': ' . esc(to_currency($value)) ?></div>
    <?php } ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        <?= view('partial/bootstrap_tables_locale') ?>

        var details_data = <?= json_encode(esc($details_data)) ?>;
        var context_data = <?= json_encode($context_data ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        <?php if ($config['customer_reward_enable'] && ! empty($details_data_rewards)) { ?>
            var details_data_rewards = <?= json_encode(esc($details_data_rewards)) ?>;
        <?php } ?>
        <?= view('partial/visibility_js') ?>

        var getDetailRowId = function(row) {
            return ((!isNaN(row.id) && row.id) || $(row[0] || row.id).text().replace(/(POS|RECV)\s*/g, ''));
        };

        var escapeHtml = function(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html();
        };

        var renderReceivingSplit = function(split) {
            if (!split) {
                return '';
            }

            return '<div class="table-responsive">'
                + '<div><strong><?= esc(lang('Receivings.copra_expenses')) ?></strong></div>'
                + '<table class="table table-bordered table-condensed">'
                + '<thead>'
                + '<tr>'
                + '<th><?= esc(lang('Receivings.copra_split_breakdown')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Reports.landowner')) ?><br><span class="text-muted">' + escapeHtml(split.landowner_name) + '</span></th>'
                + '<th class="text-right"><?= esc(lang('Reports.tenant')) ?><br><span class="text-muted">' + escapeHtml(split.tenant_name) + '</span></th>'
                + '</tr>'
                + '</thead>'
                + '<tbody>'
                + '<tr>'
                + '<td><?= esc(lang('Receivings.initial_split_guide')) ?></td>'
                + '<td class="text-right">' + escapeHtml(split.landowner_share_percent) + '</td>'
                + '<td class="text-right">' + escapeHtml(split.tenant_share_percent) + '</td>'
                + '</tr>'
                + '<tr>'
                + '<td><?= esc(lang('Receivings.base_share')) ?></td>'
                + '<td class="text-right">' + escapeHtml(split.landowner_base_share) + '</td>'
                + '<td class="text-right">' + escapeHtml(split.tenant_base_share) + '</td>'
                + '</tr>'
                + (split.has_shared_expense_transfer ? '<tr>'
                    + '<td><?= esc(lang('Receivings.shared_expense_split')) ?></td>'
                    + '<td class="text-right">' + escapeHtml(split.shared_transfer_amount) + '</td>'
                    + '<td class="text-right">' + escapeHtml(split.shared_transfer_amount) + '</td>'
                    + '</tr>'
                    + '<tr>'
                    + '<td><?= esc(lang('Receivings.shared_expense_added_to_tenant')) ?></td>'
                    + '<td class="text-right"><?= esc(to_currency(0)) ?></td>'
                    + '<td class="text-right">' + escapeHtml(split.shared_total) + '</td>'
                    + '</tr>' : '')
                + '<tr>'
                + '<td><strong><?= esc(lang('Receivings.share_after_split')) ?></strong></td>'
                + '<td class="text-right"><strong>' + escapeHtml(split.landowner_share_after_split) + '</strong></td>'
                + '<td class="text-right"><strong>' + escapeHtml(split.tenant_share_after_split) + '</strong></td>'
                + '</tr>'
                + '</tbody>'
                + '</table>'
                + '</div>';
        };

        var renderReceivingExpenses = function(expenses) {
            if (!expenses || !expenses.length) {
                return '';
            }

            var rowsHtml = '';
            $.each(expenses, function(index, expenseRow) {
                rowsHtml += '<tr>'
                    + '<td>' + escapeHtml(expenseRow.description) + '</td>'
                    + '<td class="text-right">' + escapeHtml(expenseRow.amount) + '</td>'
                    + '</tr>';
            });

            return '<div class="table-responsive">'
                + '<table class="table table-bordered table-condensed">'
                + '<thead>'
                + '<tr>'
                + '<th><?= esc(lang('Receivings.expense_description')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Receivings.expense_amount')) ?></th>'
                + '</tr>'
                + '</thead>'
                + '<tbody>' + rowsHtml + '</tbody>'
                + '</table>'
                + '</div>';
        };

        var renderReceivingLoanRows = function(rows) {
            if (!rows || !rows.length) {
                return '';
            }

            var rowsHtml = '';
            $.each(rows, function(index, detailRow) {
                rowsHtml += '<tr>'
                    + '<td>' + escapeHtml(detailRow.party_label) + '</td>'
                    + '<td>' + escapeHtml(detailRow.supplier_name) + '</td>'
                    + '<td class="text-right">' + escapeHtml(detailRow.cash_amount) + '</td>'
                    + '<td class="text-right">' + escapeHtml(detailRow.loan_balance_before) + '</td>'
                    + '<td class="text-right">' + escapeHtml(detailRow.loan_deduction_amount) + '</td>'
                    + '<td class="text-right">' + escapeHtml(detailRow.loan_balance_after) + '</td>'
                    + '</tr>';
            });

            return '<div class="table-responsive">'
                + '<table class="table table-bordered table-condensed">'
                + '<thead>'
                + '<tr>'
                + '<th><?= esc(lang('Reports.party')) ?></th>'
                + '<th><?= esc(lang('Reports.name')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Reports.cash_paid')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Reports.loan_balance_before')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Reports.loan_deduction')) ?></th>'
                + '<th class="text-right"><?= esc(lang('Reports.loan_balance_after')) ?></th>'
                + '</tr>'
                + '</thead>'
                + '<tbody>' + rowsHtml + '</tbody>'
                + '</table>'
                + '</div>';
        };

        var renderReceivingContext = function(context) {
            if (!context || (!context.luna_label && !context.split && (!context.expenses || !context.expenses.length) && (!context.rows || !context.rows.length))) {
                return '';
            }

            var lunaHtml = '';
            if (context.luna_label) {
                lunaHtml = '<div class="receiving-context__luna"><strong><?= esc(lang('Reports.luna')) ?>:</strong> ' + escapeHtml(context.luna_label) + '</div>';
            }

            return '<div class="panel panel-default receiving-context">'
                + '<div class="panel-heading"><strong><?= esc(lang('Reports.receiving_context')) ?></strong></div>'
                + '<div class="panel-body">'
                + lunaHtml
                + renderReceivingSplit(context.split)
                + renderReceivingExpenses(context.expenses)
                + renderReceivingLoanRows(context.rows)
                + '</div>'
                + '</div>';
        };

        var init_dialog = function () {
            <?php if (isset($editable)) { ?>
                table_support.submit_handler('<?= esc(site_url("reports/get_detailed_{$editable}_row")) ?>');
                dialog_support.init("a.modal-dlg");
            <?php } ?>
        };

        $('#table')
            .addClass("table-striped")
            .addClass("table-bordered")
            .bootstrapTable({
                columns: applyColumnVisibility(<?= transform_headers(esc($headers['summary']), true) ?>),
                stickyHeader: true,
                stickyHeaderOffsetLeft: $('#table').offset().left + 'px',
                stickyHeaderOffsetRight: $('#table').offset().right + 'px',
                pageSize: <?= $config['lines_per_page'] ?>,
                pagination: true,
                sortable: true,
                showColumns: true,
                uniqueId: 'id',
                showExport: true,
                exportDataType: 'all',
                exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
                data: <?= json_encode($summary_data) ?>,
                iconSize: 'sm',
                paginationVAlign: 'bottom',
                detailView: true,
                escape: true,
                search: true,
                onPageChange: init_dialog,
                onPostBody: function () {
                    dialog_support.init("a.modal-dlg");
                },
                onExpandRow: function (index, row, $detail) {
                    var rowId = getDetailRowId(row);
                    var renderedContext = renderReceivingContext(context_data[rowId]);

                    $detail.empty();

                    if (renderedContext !== '') {
                        $detail.append(renderedContext);
                    }

                    var $itemsTable = $('<table></table>');
                    $detail.append($itemsTable);
                    $itemsTable.bootstrapTable({
                        columns: <?= transform_headers_readonly(esc($headers['details'])) ?>,
                        data: details_data[rowId]
                    });

                    <?php if ($config['customer_reward_enable'] && ! empty($details_data_rewards)) { ?>
                        var $rewardTable = $('<table></table>');
                        $detail.append($rewardTable);
                        $rewardTable.bootstrapTable({
                            columns: <?= transform_headers_readonly(esc($headers['details_rewards'])) ?>,
                            data: details_data_rewards[rowId]
                        });
                    <?php } ?>
                }
            });

        init_dialog();
    });
</script>

<?= view('partial/footer') ?>
