<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_CopraFarmerLoans extends Migration
{
    /**
     * Perform a migration step.
     */
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.0_copra_farmer_loans.sql');
    }

    /**
     * Revert a migration step.
     */
    public function down(): void
    {
        $this->forge->dropTable('ospos_customer_loans', true);
        $this->forge->dropForeignKey('ospos_suppliers', 'ospos_suppliers_ibfk_customer');
        $this->forge->dropColumn('ospos_suppliers', 'customer_id');
    }
}
