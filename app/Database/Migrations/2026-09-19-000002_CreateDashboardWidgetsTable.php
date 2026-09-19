<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDashboardWidgetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'user_id'     => [
                'type'       => 'INTEGER',
                'constraint' => 11,
            ],
            'tenant_id'   => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'layout_data' => [
                'type' => 'TEXT',
            ],
            'created_at'  => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at'  => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'tenant_id']);
        $this->forge->createTable('user_dashboard_layouts', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_dashboard_layouts', true);
    }
}
