<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_SupplierPartnership extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.3_supplier_partnership.sql');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('ospos_suppliers', 'ospos_suppliers_ibfk_partner');
        $this->forge->dropColumn('ospos_suppliers', 'partner_supplier_id');
    }
}
