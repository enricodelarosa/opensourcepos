<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_CopraReports extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.9_copra_reports.sql');
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM `ospos_grants` WHERE `permission_id` = 'copra_reports'");
        $this->db->query("DELETE FROM `ospos_permissions` WHERE `permission_id` = 'copra_reports'");
        $this->db->query("DELETE FROM `ospos_modules` WHERE `module_id` = 'copra_reports'");
    }
}
