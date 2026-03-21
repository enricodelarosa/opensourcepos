<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_CustomerLoansReportPermission extends Migration
{
    public function up(): void
    {
        $this->db->query("INSERT IGNORE INTO `ospos_permissions` (`permission_id`, `module_id`) VALUES ('reports_loans', 'reports')");
        $this->db->query("INSERT IGNORE INTO `ospos_grants` (`permission_id`, `person_id`) VALUES ('reports_loans', 1)");
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM `ospos_grants` WHERE `permission_id` = 'reports_loans'");
        $this->db->query("DELETE FROM `ospos_permissions` WHERE `permission_id` = 'reports_loans'");
    }
}
