<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_ReceivingPayments extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.4_receiving_payments.sql');
    }

    public function down(): void
    {
        $this->forge->dropTable('ospos_receiving_payments', true);
    }
}
