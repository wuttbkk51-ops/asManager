<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class AuthSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now()->toDateTimeString();

        // 1. ล้างข้อมูลเดิมอย่างสะอาด (Truncate / Delete)
        $this->db->table('system_logs')->emptyTable();
        $this->db->table('user_permissions')->emptyTable();
        $this->db->table('users')->emptyTable();
        $this->db->table('peoples')->emptyTable();
        $this->db->table('roles')->emptyTable();
        $this->db->table('tenants')->emptyTable();
        $this->db->table('plans')->emptyTable();

        // 2. ข้อมูล Plans (แพ็กเกจกลางของระบบ SaaS)
        $plans = [
            [
                'id'           => 1,
                'code'         => 'freePlan',
                'name'         => 'Free Plan (เริ่มต้นฟรี)',
                'price'        => 0.00,
                'max_products' => 50,
                'max_users'    => 2,
                'max_branches' => 1,
                'is_active'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 2,
                'code'         => 'proPlan',
                'name'         => 'Pro Plan (มืออาชีพ)',
                'price'        => 590.00,
                'max_products' => 0,
                'max_users'    => 10,
                'max_branches' => 3,
                'is_active'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 3,
                'code'         => 'enterprise',
                'name'         => 'Enterprise (องค์กรขนาดใหญ่)',
                'price'        => 1990.00,
                'max_products' => 0,
                'max_users'    => 0,
                'max_branches' => 0,
                'is_active'    => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];
        $this->db->table('plans')->insertBatch($plans);

        // 3. ข้อมูล Tenants (ร้านค้า / สาขา)
        $tenants = [
            [
                'id'            => 1,
                'name'          => 'ร้านค้าตัวอย่าง (Demo Shop)',
                'slug'          => 'demo-shop',
                'plan'          => 'freePlan',
                'status'        => 'active',
                'trial_ends_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 2,
                'name'          => 'สาขาทองหล่อ (Thonglor Branch)',
                'slug'          => 'thonglor',
                'plan'          => 'proPlan',
                'status'        => 'active',
                'trial_ends_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];
        $this->db->table('tenants')->insertBatch($tenants);

        // 4. ข้อมูล Roles (Permission Group / Templates)
        // กฎ: tenant_id = 0 คือ Default Template ส่วนกลาง
        $roles = [
            // --- แม่แบบเริ่มต้นส่วนกลาง (tenant_id = 0) ---
            [
                'tenant_id'   => 0,
                'name'        => 'manager',
                'title'       => 'ผู้จัดการร้าน (Manager)',
                'description' => 'ดูแลการดำเนินงานของร้าน สินค้า ตั้งค่า และพนักงาน',
                'permissions' => json_encode(['dashboard', 'settings.*', 'products.*', 'staff.*']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'tenant_id'   => 0,
                'name'        => 'sales',
                'title'       => 'พนักงานขาย (Sales)',
                'description' => 'ดูแลการขายและรายการสินค้า',
                'permissions' => json_encode(['dashboard', 'products']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'tenant_id'   => 0,
                'name'        => 'technician',
                'title'       => 'ช่างซ่อม / บริการ (Technician)',
                'description' => 'ดูแลงานซ่อมบำรุงและรายการสินค้า',
                'permissions' => json_encode(['dashboard', 'products']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'tenant_id'   => 0,
                'name'        => 'cashier',
                'title'       => 'พนักงานแคชเชียร์ (Cashier)',
                'description' => 'คิดเงินและดูรายการสินค้า',
                'permissions' => json_encode(['dashboard', 'products']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],

            // --- Role ประจำร้านตัวอย่าง (tenant_id = 1) โคลนมาจากแม่แบบ ---
            [
                'tenant_id'   => 1,
                'name'        => 'manager',
                'title'       => 'ผู้จัดการร้าน',
                'description' => 'ผู้จัดการประจำร้าน Demo Shop',
                'permissions' => json_encode(['dashboard', 'settings.*', 'products.*', 'staff.*']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'tenant_id'   => 1,
                'name'        => 'sales',
                'title'       => 'พนักงานขาย',
                'description' => 'พนักงานขายประจำร้าน Demo Shop',
                'permissions' => json_encode(['dashboard', 'products']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'tenant_id'   => 1,
                'name'        => 'technician',
                'title'       => 'ช่างซ่อม',
                'description' => 'ช่างประจำร้าน Demo Shop',
                'permissions' => json_encode(['dashboard', 'products']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];
        $this->db->table('roles')->insertBatch($roles);

        // 5. ข้อมูล Peoples (ข้อมูลบุคคล)
        $people = [
            [
                'id'            => 1,
                'tenant_id'     => null,
                'price_tier_id' => null,
                'first_name'    => 'ศุภโชค',
                'last_name'     => 'ผู้ดูแลระบบแพลตฟอร์ม',
                'phone'         => '081-234-5678',
                'avatar'        => null,
                'bio'           => 'Super Administrator of Platform',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 2,
                'tenant_id'     => 1,
                'price_tier_id' => 2, // ผูกกับ Tier 2 (ราคาช่าง)
                'first_name'    => 'สมชาย',
                'last_name'     => 'เจ้าของร้าน',
                'phone'         => '089-876-5432',
                'avatar'        => null,
                'bio'           => 'Shop Owner of Demo Shop',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];
        $this->db->table('peoples')->insertBatch($people);

        // 6. ข้อมูล Users
        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT);
        $users = [
            // User 1: Superadmin (ไม่มี tenant_id ดูแลภาพรวมทั้งระบบ)
            [
                'id'         => 1,
                'tenant_id'  => null,
                'username'   => 'superadmin',
                'email'      => 'superadmin@example.com',
                'password'   => $defaultPassword,
                'person_id'  => 1,
                'role'       => 'superadmin',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // User 2: Owner ร้านตัวอย่าง (มี tenant_id = 1)
            [
                'id'         => 2,
                'tenant_id'  => 1,
                'username'   => 'owner',
                'email'      => 'owner@example.com',
                'password'   => $defaultPassword,
                'person_id'  => 2,
                'role'       => 'owner',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // User 3: Staff ร้านตัวอย่าง (มี tenant_id = 1)
            [
                'id'         => 3,
                'tenant_id'  => 1,
                'username'   => 'staff',
                'email'      => 'staff@example.com',
                'password'   => $defaultPassword,
                'person_id'  => null,
                'role'       => 'staff',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('users')->insertBatch($users);

        // 7. ข้อมูล User Permissions (สิทธิ์ผูกตรงกับ User ตามระดับอำนาจหน้าที่)
        $userPermissions = [
            // User 1 (Superadmin): สิทธิ์ '*'
            ['user_id' => 1, 'permission' => '*', 'created_at' => $now],

            // User 2 (Owner): สิทธิ์เต็มภายในร้านค้าตนเอง
            ['user_id' => 2, 'permission' => 'dashboard',    'created_at' => $now],
            ['user_id' => 2, 'permission' => 'products.*',   'created_at' => $now],
            ['user_id' => 2, 'permission' => 'settings.*',   'created_at' => $now],
            ['user_id' => 2, 'permission' => 'staff.*',      'created_at' => $now],

            // User 3 (Staff): สิทธิ์เฉพาะดูหน้าแดชบอร์ดและสินค้า
            ['user_id' => 3, 'permission' => 'dashboard',    'created_at' => $now],
            ['user_id' => 3, 'permission' => 'products',     'created_at' => $now],
        ];
        $this->db->table('user_permissions')->insertBatch($userPermissions);

        // 8. ข้อมูล System Logs ตัวอย่าง
        $this->db->table('system_logs')->insert([
            'user_id'    => 1,
            'tenant_id'  => null,
            'action'     => 'platform.initialized',
            'details'    => 'ระบบ SaaS ถูกติดตั้งและเริ่มใช้งานเริ่มต้นโดย Superadmin',
            'ip_address' => '127.0.0.1',
            'created_at' => $now,
        ]);

        // 9. รันต่อด้วย ERP Seeder (สาขา, คลัง, สินค้า Hybrid, สต็อก, กระเป๋าเงิน, งานซ่อม, และ Print Templates)
        $this->call('ErpSeeder');
    }
}
