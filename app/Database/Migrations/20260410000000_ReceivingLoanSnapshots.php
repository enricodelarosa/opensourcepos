<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_ReceivingLoanSnapshots extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.6_receiving_loan_snapshots.sql');
    }

    public function down(): void
    {
        $this->forge->dropTable('ospos_receiving_loan_snapshots', true);
    }
}
