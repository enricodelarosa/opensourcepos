<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_ReceivingExpensesAndShares extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('landowner_share_percent', 'receivings')) {
            $this->forge->addColumn('receivings', [
                'landowner_share_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                    'after'      => 'reference',
                ],
            ]);
        }

        if (! $this->db->fieldExists('tenant_share_percent', 'receivings')) {
            $this->forge->addColumn('receivings', [
                'tenant_share_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                    'after'      => 'landowner_share_percent',
                ],
            ]);
        }

        if ($this->db->tableExists('receiving_expenses')) {
            if ($this->db->fieldExists('expense_type', 'receiving_expenses')) {
                $this->forge->dropColumn('receiving_expenses', 'expense_type');
            }

            if ($this->db->fieldExists('paid_in_advance', 'receiving_expenses')) {
                $this->forge->dropColumn('receiving_expenses', 'paid_in_advance');
            }

            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'receiving_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 10,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('receiving_id');
        $this->forge->addForeignKey('receiving_id', 'receivings', 'receiving_id', 'CASCADE', 'CASCADE', 'ospos_receiving_expenses_ibfk_receiving');
        $this->forge->createTable('receiving_expenses', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('receiving_expenses')) {
            $this->forge->dropTable('receiving_expenses', true);
        }

        if ($this->db->fieldExists('tenant_share_percent', 'receivings')) {
            $this->forge->dropColumn('receivings', 'tenant_share_percent');
        }

        if ($this->db->fieldExists('landowner_share_percent', 'receivings')) {
            $this->forge->dropColumn('receivings', 'landowner_share_percent');
        }
    }
}
