<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up()
    {
        // 1. ตาราง plans (แพ็กเกจกลางของระบบ SaaS)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'max_products' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'default'    => 50,
            ],
            'max_users' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'default'    => 2,
            ],
            'max_branches' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'default'    => 1,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('plans', true);

        // 2. ตาราง tenants (ร้านค้า / องค์กร / สาขา)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'freePlan',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active', // active, suspended, banned
            ],
            'trial_ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants', true);

        // 3. ตาราง roles (Permission Group / Template อิสระ ไม่ผูก FK โดยตรงกับตารางใด)
        // tenant_id = 0 คือ Default Template ส่วนกลาง, หาก > 0 คือ Role ประจำร้านนั้นๆ
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'default'    => 0, // 0 = default template ส่วนกลาง
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // e.g. 'sales', 'technician', 'manager', 'cashier'
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 100, // e.g. 'พนักงานขาย', 'ช่างซ่อม', 'ผู้จัดการร้าน'
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'permissions' => [
                'type' => 'TEXT', // JSON Array เก็บรายการสิทธิ์ เช่น ["dashboard", "products"]
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('roles', true);

        // 4. ตาราง peoples (ข้อมูลบุคคล)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'price_tier_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('peoples', true);

        // 5. ตาราง users (บัญชีผู้ใช้)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'person_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => 'owner',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('person_id', 'peoples', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('users', true);

        // 6. ตาราง user_permissions (สิทธิ์ประจำตัวผู้ใช้ เป็นแกนหลัก 100%)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
            ],
            'permission' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'permission']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_permissions', true);

        // 7. ตาราง system_logs (Audit Logs / Platform Security)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'tenant_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->createTable('system_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('system_logs', true);
        $this->forge->dropTable('user_permissions', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('peoples', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('tenants', true);
        $this->forge->dropTable('plans', true);
    }
}
