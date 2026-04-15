<?php

namespace App\Libraries;

use App\Models\Cashup;
use App\Models\Expense;
use App\Models\Loan_adjustment;
use App\Models\Receiving;
use App\Models\Sale;

class CashOnHandService
{
    private Cashup $cashup;
    private Expense $expense;
    private Loan_adjustment $loanAdjustment;
    private Receiving $receiving;
    private Sale $sale;

    public function __construct()
    {
        $this->cashup         = model(Cashup::class);
        $this->expense        = model(Expense::class);
        $this->loanAdjustment = model(Loan_adjustment::class);
        $this->receiving      = model(Receiving::class);
        $this->sale           = model(Sale::class);
    }

    public function getCurrentCashData(): array
    {
        $latestCashup = $this->cashup->getLatest();

        if ($latestCashup === null) {
            return [
                'status'       => 'no_cashup_found',
                'cashup_id'    => null,
                'cash_on_hand' => null,
                'as_of'        => date('Y-m-d H:i:s'),
            ];
        }

        if ($this->isPendingCloseCashup($latestCashup)) {
            return $this->buildOpenCashupResponse($latestCashup);
        }

        return $this->buildClosedCashupResponse($latestCashup);
    }

    private function getCashSalesTotal(string $startDateTime, string $endDateTime): float
    {
        $rows = $this->sale->get_cash_sales_for_period(substr($startDateTime, 0, 10), substr($endDateTime, 0, 10));

        return array_sum(array_column($this->filterRowsByWindow($rows, $startDateTime, $endDateTime), 'amount'));
    }

    private function getCashExpensesTotal(string $startDateTime, string $endDateTime): float
    {
        $filters = [
            'start_date'     => $startDateTime,
            'end_date'       => $endDateTime,
            'use_time_range' => true,
            'only_cash'      => true,
            'only_due'       => false,
            'only_check'     => false,
            'only_credit'    => false,
            'only_debit'     => false,
            'is_deleted'     => false,
        ];

        $total = 0.0;

        foreach ($this->expense->get_payments_summary('', $filters) as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return $total;
    }

    private function buildOpenCashupResponse(object $cashup): array
    {
        $asOf                = date('Y-m-d H:i:s');
        $cashBeginning       = (float) ($cashup->open_amount_cash ?? 0);
        $transferAmountCash  = (float) ($cashup->transfer_amount_cash ?? 0);
        $cashSales           = $this->getCashSalesTotal($cashup->open_date, $asOf);
        $cashAdvances        = $this->loanAdjustment->get_cash_total_for_period($cashup->open_date, $asOf, true);
        $cashPurchases       = $this->receiving->get_cash_total_for_period($cashup->open_date, $asOf, true);
        $cashExpenses        = $this->getCashExpensesTotal($cashup->open_date, $asOf);
        $cashOnHand          = round(
            $cashBeginning
            + $transferAmountCash
            + $cashSales
            - $cashAdvances
            - $cashPurchases
            - $cashExpenses,
            2,
        );

        return [
            'status'               => 'open_cashup',
            'cashup_id'            => (int) $cashup->cashup_id,
            'description'          => $cashup->description,
            'opened_at'            => $cashup->open_date,
            'opened_today'         => substr((string) $cashup->open_date, 0, 10) === date('Y-m-d'),
            'as_of'                => $asOf,
            'cash_beginning'       => $cashBeginning,
            'transfer_amount_cash' => $transferAmountCash,
            'cash_sales'           => round($cashSales, 2),
            'cash_advances'        => round($cashAdvances, 2),
            'cash_purchases'       => round($cashPurchases, 2),
            'cash_expenses'        => round($cashExpenses, 2),
            'cash_on_hand'         => $cashOnHand,
        ];
    }

    private function buildClosedCashupResponse(object $cashup): array
    {
        return [
            'status'               => 'last_closed_cashup',
            'cashup_id'            => (int) $cashup->cashup_id,
            'description'          => $cashup->description,
            'opened_at'            => $cashup->open_date,
            'closed_at'            => $cashup->close_date,
            'cash_beginning'       => (float) ($cashup->open_amount_cash ?? 0),
            'transfer_amount_cash' => (float) ($cashup->transfer_amount_cash ?? 0),
            'cash_on_hand'         => (float) ($cashup->closed_amount_cash ?? 0),
            'cash_due'             => (float) ($cashup->closed_amount_due ?? 0),
            'cash_card'            => (float) ($cashup->closed_amount_card ?? 0),
            'cash_check'           => (float) ($cashup->closed_amount_check ?? 0),
            'cash_difference'      => (float) ($cashup->closed_amount_total ?? 0),
            'as_of'                => $cashup->close_date,
        ];
    }

    private function isPendingCloseCashup(object $cashup): bool
    {
        return (float) ($cashup->closed_amount_cash ?? 0) === 0.0
            && (float) ($cashup->closed_amount_due ?? 0) === 0.0
            && (float) ($cashup->closed_amount_card ?? 0) === 0.0
            && (float) ($cashup->closed_amount_check ?? 0) === 0.0;
    }

    private function filterRowsByWindow(array $rows, string $windowStart, string $windowEnd): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($windowStart, $windowEnd): bool {
            $transTime = $row['trans_time'] ?? null;

            if (empty($transTime)) {
                return false;
            }

            return $transTime >= $windowStart && $transTime <= $windowEnd;
        }));
    }
}
