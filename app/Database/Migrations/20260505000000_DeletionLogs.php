<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_DeletionLogs extends Migration
{
    public function up(): void
    {
        helper('migration');
        execute_script(APPPATH . 'Database/Migrations/sqlscripts/4.0.10_deletion_logs.sql');
    }

    public function down(): void
    {
        $this->forge->dropTable('ospos_deletion_logs', true);
    }
}
