<?php

namespace App\Models\Reports;

use App\Models\Customer_loan;
use Config\OSPOS;

class Customer_loans_report extends Report
{
    public function getDataColumns(): array
    {
        return [
            ['transaction_time' => lang('Reports.date'), 'sortable' => false],
            ['transaction_type' => lang('Reports.loan_transaction_type')],
            ['reference'        => lang('Reports.loan_reference')],
            ['luna_label'       => lang('Reports.luna')],
            ['loan_amount'      => lang('Reports.loan_amount'), 'sorter' => 'number_sorter'],
            ['running_balance'  => lang('Reports.loan_balance'), 'sorter' => 'number_sorter'],
            ['comment'          => lang('Reports.comments')],
        ];
    }

    public function getData(array $inputs): array
    {
        return model(Customer_loan::class)->get_history(
            (int) $inputs['customer_id'],
            $this->normalizeBoundary((string) $inputs['start_date']),
            $this->normalizeBoundary((string) $inputs['end_date'], true),
            0,
            0,
            'asc',
        );
    }

    public function getSummaryData(array $inputs): array
    {
        $history_rows   = $this->getData($inputs);
        $total_debt     = 0.0;
        $total_payments = 0.0;

        foreach ($history_rows as $row) {
            $loan_amount = (float) $row['loan_amount'];

            if ($loan_amount > 0) {
                $total_debt += $loan_amount;
            } elseif ($loan_amount < 0) {
                $total_payments += abs($loan_amount);
            }
        }

        $outstanding = (float) model(Customer_loan::class)->get_loan_balance((int) $inputs['customer_id']);

        return [
            'loan_total_debt'     => $total_debt,
            'loan_total_payments' => $total_payments,
            'loan_balance'        => $outstanding,
        ];
    }

    public function getOpeningBalance(array $inputs): float
    {
        return model(Customer_loan::class)->get_balance_before_datetime(
            (int) $inputs['customer_id'],
            $this->normalizeBoundary((string) $inputs['start_date']),
        );
    }

    private function normalizeBoundary(string $value, bool $is_end = false): string
    {
        $decoded = rawurldecode($value);
        $config  = config(OSPOS::class)->settings;

        if (empty($config['date_or_time_format']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $decoded) === 1) {
            return $decoded . ($is_end ? ' 23:59:59' : ' 00:00:00');
        }

        return $decoded;
    }
}
