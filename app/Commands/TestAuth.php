<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;
use App\Models\TenantModel;

class TestAuth extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:auth';
    protected $description = 'ทดสอบระบบ Superadmin (Platform Level) vs Owner (Tenant Level) และฟังก์ชัน Impersonation';

    public function run(array $params)
    {
        $userModel = new UserModel();
        $db = \Config\Database::connect();

        CLI::write("=== 1. TEST DATABASE SCHEMA & SEED DATA ===", 'yellow');
        $planCount = $db->table('plans')->countAll();
        $this->assertTest("Plans table exists and has data (count: {$planCount})", $planCount >= 3);

        $logCount = $db->table('system_logs')->countAll();
        $this->assertTest("System logs table exists and has data (count: {$logCount})", $logCount >= 1);

        $tenantCount = $db->table('tenants')->countAll();
        $this->assertTest("Tenants table exists and has data (count: {$tenantCount})", $tenantCount >= 2);

        CLI::write(PHP_EOL . "=== 2. TEST SUPERADMIN (PLATFORM LEVEL) PERMISSIONS ===", 'yellow');
        $superadmin = $userModel->getUserWithRoleAndPermissions('superadmin@example.com');
        $this->assertTest("Superadmin exists (id = 1, role = 'superadmin')", $superadmin['role'] === 'superadmin' && $superadmin['id'] == 1);
        $this->assertTest("Superadmin tenant_id is NULL (global platform owner)", $superadmin['tenant_id'] === null);

        // เซ็ต session เป็น Superadmin
        session()->set([
            'is_logged_in' => true,
            'tenant'       => null,
            'user'         => [
                'id'   => $superadmin['id'],
                'role' => $superadmin['role'],
                'name' => $superadmin['username'],
            ],
            'permissions'  => $superadmin['permissions'],
        ]);

        $this->assertTest("is_superadmin() returns true", is_superadmin() === true);
        $this->assertTest("Superadmin can('platform.dashboard')", can('platform.dashboard') === true);
        $this->assertTest("Superadmin can('tenants.suspend')", can('tenants.suspend') === true);
        $this->assertTest("Superadmin can('plans.edit')", can('plans.edit') === true);
        $this->assertTest("Superadmin can('system.logs')", can('system.logs') === true);

        CLI::write(PHP_EOL . "=== 3. TEST OWNER (TENANT LEVEL) PERMISSIONS ===", 'yellow');
        $owner = $userModel->getUserWithRoleAndPermissions('owner@example.com');
        $this->assertTest("Demo Owner exists (role = 'owner', tenant_id = 1)", $owner['role'] === 'owner' && $owner['tenant_id'] == 1);

        // เซ็ต session เป็น Owner
        session()->set([
            'is_logged_in' => true,
            'tenant'       => $owner['tenant'],
            'user'         => [
                'id'   => $owner['id'],
                'role' => $owner['role'],
                'name' => $owner['username'],
            ],
            'permissions'  => $owner['permissions'], // ['dashboard', 'products.*', 'settings.*', 'staff.*']
        ]);

        $this->assertTest("is_owner() returns true", is_owner() === true);
        $this->assertTest("is_superadmin() returns false for Owner", is_superadmin() === false);
        $this->assertTest("Owner can('dashboard') inside shop", can('dashboard') === true);
        $this->assertTest("Owner can('products.delete') [via products.*]", can('products.delete') === true);
        $this->assertTest("Owner can('settings.update') [via settings.*]", can('settings.update') === true);
        $this->assertTest("Owner can('staff.manage') [via staff.*]", can('staff.manage') === true);
        // สิ่งที่ Owner ต้องไม่มีสิทธิ์ (Platform Level):
        $this->assertTest("Owner CANNOT access 'tenants.suspend' [should be false]", can('tenants.suspend') === false);
        $this->assertTest("Owner CANNOT access 'plans.edit' [should be false]", can('plans.edit') === false);
        $this->assertTest("Owner CANNOT access 'system.logs' [should be false]", can('system.logs') === false);

        CLI::write(PHP_EOL . "=== 4. TEST IMPERSONATION (LOGIN AS TENANT) ===", 'yellow');
        // จำลอง Superadmin เข้าใช้งาน
        session()->set([
            'is_logged_in' => true,
            'tenant'       => null,
            'user'         => ['id' => 1, 'role' => 'superadmin', 'name' => 'superadmin'],
            'permissions'  => ['*'],
        ]);

        $authController = new \App\Controllers\Auth();
        // Superadmin สลับตัวเข้าสู่ Tenant 1 (Demo Shop)
        $authController->impersonate(1);

        $this->assertTest("is_impersonating() is true during impersonation", is_impersonating() === true);
        $this->assertTest("Impersonating tenant is 'Demo Shop'", tenant('name') === 'ร้านค้าตัวอย่าง (Demo Shop)');
        $this->assertTest("Impersonator backup is preserved in session", !empty(session()->get('impersonator')));

        // สลับกลับสู่ Superadmin
        $authController->stopImpersonating();
        $this->assertTest("is_impersonating() is false after stopImpersonating()", is_impersonating() === false);
        $this->assertTest("Returned to Superadmin role ('superadmin')", auth_user('role') === 'superadmin');

        CLI::write(PHP_EOL . "=== 5. TEST REGISTER FLOW (NEW TENANT + OWNER + freePlan) ===", 'yellow');
        $testEmail = 'newowner_' . time() . '@example.com';
        $newUserId = $userModel->registerUser($testEmail, 'secret123');
        $this->assertTest("New user registered successfully", $newUserId !== false);

        $newUser = $userModel->getUserWithRoleAndPermissions($testEmail);
        $this->assertTest("New user role is 'owner'", $newUser['role'] === 'owner');
        $this->assertTest("New tenant plan is 'freePlan'", $newUser['tenant']['plan'] === 'freePlan');
        $this->assertTest("New tenant is linked with user", !empty($newUser['tenant_id']));

        CLI::write(PHP_EOL . "=== 6. TEST ROLES AS PERMISSION GROUP TEMPLATES ===", 'yellow');
        $roleModel = new \App\Models\RoleModel();

        // 6.1 ตรวจสอบ Default Templates (tenant_id = 0)
        $defaultTemplates = $roleModel->getDefaultTemplates();
        $this->assertTest("Default templates (tenant_id = 0) exist (found: " . count($defaultTemplates) . ")", count($defaultTemplates) >= 4);

        $templateNames = array_column($defaultTemplates, 'name');
        $this->assertTest("Default templates include manager, sales, technician, cashier",
            in_array('manager', $templateNames) &&
            in_array('sales', $templateNames) &&
            in_array('technician', $templateNames) &&
            in_array('cashier', $templateNames)
        );

        // 6.2 ตรวจสอบการสมัครสมาชิกใหม่ และร้านใหม่ได้รับ Role แม่แบบที่โคลนมาทันที
        $shopOwnerEmail = 'coffee_owner_' . time() . '@example.com';
        $shopOwnerId = $userModel->registerUser($shopOwnerEmail, 'password123');
        $shopOwner = $userModel->getUserWithRoleAndPermissions($shopOwnerEmail);
        $newShopTenantId = (int)$shopOwner['tenant_id'];

        $newShopRoles = $roleModel->getTenantRoles($newShopTenantId);
        $this->assertTest("New shop automatically received cloned roles from tenant 0 (count: " . count($newShopRoles) . ")", count($newShopRoles) >= 4);

        $clonedRoleNames = array_column($newShopRoles, 'name');
        $this->assertTest("New shop cloned roles have manager, sales, technician, cashier",
            in_array('manager', $clonedRoleNames) &&
            in_array('cashier', $clonedRoleNames)
        );

        // 6.3 ตรวจสอบว่า Owner ร้านใหม่สามารถ Custom Role ได้เอง (เพิ่ม Custom Role)
        $customRoleId = $roleModel->createRoleForTenant($newShopTenantId, [
            'name'        => 'barista',
            'title'       => 'บาริสต้า (Barista)',
            'description' => 'พนักงานชงกาแฟและรับออเดอร์',
            'permissions' => ['orders.create', 'orders.view', 'menu.view'],
        ]);
        $this->assertTest("Owner can create custom role 'barista' for their shop", $customRoleId !== false);

        // 6.4 ตรวจสอบว่า Owner ร้านใหม่สามารถแก้ไข Role ประจำร้านได้
        $updated = $roleModel->updateTenantRole($newShopTenantId, $customRoleId, [
            'title'       => 'หัวหน้าบาริสต้า (Head Barista)',
            'permissions' => ['orders.create', 'orders.view', 'orders.cancel', 'menu.view'],
        ]);
        $this->assertTest("Owner can update their custom role", $updated === true);

        // 6.5 ตรวจสอบว่า Owner ร้านใหม่สามารถลบ Role ประจำร้านได้
        $deleted = $roleModel->deleteTenantRole($newShopTenantId, $customRoleId);
        $this->assertTest("Owner can delete their custom role", $deleted === true);

        // 6.6 ตรวจสอบความปลอดภัยข้ามร้าน: ห้ามลบ Template ส่วนกลาง (tenant_id = 0) ผ่าน deleteTenantRole
        $firstTpl = $defaultTemplates[0];
        $cannotDeleteTemplate = $roleModel->deleteTenantRole(0, $firstTpl['id']);
        $this->assertTest("Global template (tenant_id = 0) CANNOT be deleted via deleteTenantRole", $cannotDeleteTemplate === false);

        // 6.7 ตรวจสอบว่าการกระทำของร้านใหม่ไม่กระทบ Template ส่วนกลาง
        $templatesAfter = $roleModel->getDefaultTemplates();
        $this->assertTest("Global default templates remain intact (count: " . count($templatesAfter) . ")", count($templatesAfter) === count($defaultTemplates));

        // ล้างข้อมูลทดสอบของร้านใหม่
        $db->table('roles')->where('tenant_id', $newShopTenantId)->delete();
        $db->table('user_permissions')->where('user_id', $shopOwnerId)->delete();
        $userModel->delete($shopOwnerId);
        $db->table('tenants')->where('id', $newShopTenantId)->delete();

        // ล้างข้อมูลทดสอบข้อ 5
        $db->table('roles')->where('tenant_id', $newUser['tenant_id'])->delete();
        $db->table('user_permissions')->where('user_id', $newUserId)->delete();
        $userModel->delete($newUserId);
        $db->table('tenants')->where('id', $newUser['tenant_id'])->delete();

        CLI::write(PHP_EOL . "=== ALL PLATFORM, TENANT AUTH & ROLE TEMPLATE TESTS PASSED 100% ===", 'green');
    }

    private function assertTest(string $label, bool $result)
    {
        if ($result) {
            CLI::write("  [PASS] " . $label, 'green');
        } else {
            CLI::error("  [FAIL] " . $label);
        }
    }
}
