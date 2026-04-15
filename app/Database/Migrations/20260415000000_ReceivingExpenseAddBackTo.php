<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReceivingExpenseAddBackTo extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('receiving_expenses')) {
            return;
        }

        if (! $this->db->fieldExists('add_back_to', 'receiving_expenses')) {
            $this->forge->addColumn('receiving_expenses', [
                'add_back_to' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'tenant',
                    'after'      => 'amount',
                ],
            ]);
        }

        $builder = $this->db->table('receiving_expenses');
        $builder->where('add_back_to IS NULL', null, false)
            ->orWhere('add_back_to', '')
            ->set('add_back_to', 'tenant')
            ->update();
    }

    public function down(): void
    {
        if ($this->db->tableExists('receiving_expenses') && $this->db->fieldExists('add_back_to', 'receiving_expenses')) {
            $this->forge->dropColumn('receiving_expenses', 'add_back_to');
        }
    }
}
