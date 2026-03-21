<?php

namespace App\Models\Reports;

class Customer_loans_report extends Report
{
    public function getDataColumns(): array
    {
        return [
            ['transaction_time' => lang('Reports.date'),                  'sortable' => false],
            ['transaction_type' => lang('Reports.loan_transaction_type')],
            ['reference'        => lang('Reports.loan_reference')],
            ['loan_amount'      => lang('Reports.loan_amount'),           'sorter' => 'number_sorter'],
            ['running_balance'  => lang('Reports.loan_balance'),          'sorter' => 'number_sorter'],
            ['comment'          => lang('Reports.comments')],
        ];
    }

    public function getData(array $inputs): array
    {
        $builder = $this->db->table('customer_loans');
        $builder->select('loan_id, transaction_time, sale_id, receiving_id, loan_amount, comment');
        $builder->where('customer_id', $inputs['customer_id']);
        $builder->where('transaction_time >=', rawurldecode($inputs['start_date']));
        $builder->where('transaction_time <=', rawurldecode($inputs['end_date']));
        $builder->orderBy('transaction_time', 'ASC');

        return $builder->get()->getResultArray();
    }

    public function getSummaryData(array $inputs): array
    {
        // Total new debt (sales on loan) within the period
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'total');
        $builder->where('customer_id', $inputs['customer_id']);
        $builder->where('transaction_time >=', rawurldecode($inputs['start_date']));
        $builder->where('transaction_time <=', rawurldecode($inputs['end_date']));
        $builder->where('loan_amount >', 0);
        $total_debt = (float)($builder->get()->getRow()->total ?? 0);

        // Total payments (supplier purchase deductions) within the period
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'total');
        $builder->where('customer_id', $inputs['customer_id']);
        $builder->where('transaction_time >=', rawurldecode($inputs['start_date']));
        $builder->where('transaction_time <=', rawurldecode($inputs['end_date']));
        $builder->where('loan_amount <', 0);
        $total_payments = (float)($builder->get()->getRow()->total ?? 0);

        // Outstanding balance across ALL time (not filtered by date)
        $builder = $this->db->table('customer_loans');
        $builder->selectSum('loan_amount', 'total');
        $builder->where('customer_id', $inputs['customer_id']);
        $outstanding = (float)($builder->get()->getRow()->total ?? 0);

        return [
            'loan_total_debt'     => $total_debt,
            'loan_total_payments' => abs($total_payments),
            'loan_balance'        => $outstanding,
        ];
    }
}
