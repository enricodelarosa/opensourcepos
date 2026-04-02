<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_FontSizeSetting extends Migration
{
    public function up(): void
    {
        $this->db->query("INSERT IGNORE INTO `ospos_app_config` (`key`, `value`) VALUES ('font_size', '16')");
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM `ospos_app_config` WHERE `key` = 'font_size'");
    }
}
