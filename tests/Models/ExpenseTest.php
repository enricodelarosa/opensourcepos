<?php

namespace Tests\Models;

use App\Models\Expense;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\OSPOS;
use ReflectionClass;

/**
 * @internal
 */
final class ExpenseTest extends CIUnitTestCase
{
    private BaseConnection $expenseDb;
    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $config           = (new ReflectionClass(OSPOS::class))->newInstanceWithoutConstructor();
        $config->settings = [];
        Factories::injectMock('config', OSPOS::class, $config);

        $this->expenseDb = Database::connect([
            'DBDriver' => 'SQLite3',
            'database' => ':memory:',
            'DBPrefix' => 'ospos_',
        ], false);
        $this->expenseDb->initialize();
        $this->expenseDb->connID->createFunction('CONCAT', static fn (...$parts): string => implode('', $parts));
        $this->expenseDb->query('CREATE TABLE ospos_people (person_id INTEGER, first_name TEXT, last_name TEXT)');
        $this->expenseDb->query('CREATE TABLE ospos_suppliers (person_id INTEGER, company_name TEXT)');
        $this->expenseDb->query('CREATE TABLE ospos_expense_categories (expense_category_id INTEGER, category_name TEXT, deleted INTEGER)');
        $this->expenseDb->query('CREATE TABLE ospos_expenses (expense_id INTEGER, date TEXT, amount REAL, tax_amount REAL, payment_type TEXT, expense_category_id INTEGER, description TEXT, employee_id INTEGER, supplier_id INTEGER, supplier_tax_code TEXT, deleted INTEGER)');
        $this->expenseDb->query("INSERT INTO ospos_expense_categories VALUES (2, 'ATM', 0), (3, 'Supplies', 0), (4, ' atm ', 1)");

        foreach ([2, 3, null, 4] as $index => $categoryId) {
            $this->expenseDb->table('expenses')->insert([
                'expense_id'          => $index + 1,
                'date'                => '2026-10-17 12:00:00',
                'amount'              => 100,
                'tax_amount'          => 0,
                'payment_type'        => 'Cash',
                'expense_category_id' => $categoryId,
                'deleted'             => 0,
            ]);
        }
        $this->expense = new Expense($this->expenseDb);
    }

    protected function tearDown(): void
    {
        $this->expenseDb->close();
        parent::tearDown();
    }

    public function testAtmToggleUpdatesRowsCountAndPaymentSummary(): void
    {
        $filters = [
            'start_date'     => '2026-10-17 00:00:00',
            'end_date'       => '2026-10-17 23:59:59',
            'use_time_range' => true,
            'is_deleted'     => false,
            'only_cash'      => false,
            'only_due'       => false,
            'only_check'     => false,
            'only_credit'    => false,
            'only_debit'     => false,
        ];

        // Existing callers omit the toggle; turning it off restores the same results.
        foreach ([null, true, false] as $hideAtm) {
            if ($hideAtm !== null) {
                $filters['hide_atm'] = $hideAtm;
            }
            $expectedIds = $hideAtm ? [2, 3] : [1, 2, 3, 4];
            $rows        = $this->expense->search('', $filters)->getResultArray();
            $this->assertSame($expectedIds, array_column($rows, 'expense_id'));
            $this->assertSame(count($expectedIds), $this->expense->get_found_rows('', $filters));
            $summary = $this->expense->get_payments_summary('', $filters);
            $this->assertSame(count($expectedIds) * 100.0, $summary[0]['amount']);
        }

        $filters['hide_atm'] = true;
        $this->expenseDb->table('expenses')->whereIn('expense_id', [2, 3])->delete();
        $this->assertSame([], $this->expense->search('', $filters)->getResultArray());
        $this->assertSame(0, $this->expense->get_found_rows('', $filters));
        $this->assertSame([], $this->expense->get_payments_summary('', $filters));
    }
}
