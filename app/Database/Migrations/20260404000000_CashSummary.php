<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_CashSummary extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.2_cash_summary.sql');
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM `ospos_grants` WHERE `permission_id` = 'cash_summary'");
        $this->db->query("DELETE FROM `ospos_permissions` WHERE `permission_id` = 'cash_summary'");
        $this->db->query("DELETE FROM `ospos_modules` WHERE `module_id` = 'cash_summary'");
    }
}
