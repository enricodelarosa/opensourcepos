<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_CashMovements extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.8_cash_movements.sql');
    }

    public function down(): void
    {
        $this->forge->dropTable('ospos_cash_movements', true);
    }
}
