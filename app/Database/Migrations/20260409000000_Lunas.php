<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_Lunas extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.5_lunas.sql');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('ospos_receivings', 'ospos_receivings_ibfk_luna');
        $this->forge->dropColumn('ospos_receivings', 'luna_id');
        $this->forge->dropForeignKey('ospos_customer_loans', 'ospos_customer_loans_ibfk_luna');
        $this->forge->dropColumn('ospos_customer_loans', 'luna_id');
        $this->forge->dropTable('ospos_lunas', true);

        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.3_supplier_partnership.sql');
    }
}
