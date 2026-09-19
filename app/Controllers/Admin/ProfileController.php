<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\UserSettingService;
use Config\Database;

class ProfileController extends BaseController
{
    protected UserSettingService $userSettingService;

    public function __construct()
    {
        $this->userSettingService = new UserSettingService();
    }

    /**
     * แสดงหน้า User Profile, Permissions, และ Preferences
     */
    public function index()
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $db = Database::connect();

        // 1. ดึงข้อมูล User และ Person
        $user = $db->table('users')
                   ->select('users.*, peoples.first_name, peoples.last_name, peoples.phone, peoples.avatar, peoples.bio')
                   ->join('peoples', 'peoples.id = users.person_id', 'left')
                   ->where('users.id', $userId)
                   ->get()
                   ->getRowArray();

        // 2. ดึงสิทธิ์ทั้งหมดของผู้ใช้
        $userPermRows = $db->table('user_permissions')
                           ->where('user_id', $userId)
                           ->get()
                           ->getResultArray();
        $userPermissions = array_column($userPermRows, 'permission');

        // หากผู้ใช้มี role ประจำร้าน ให้ดึงสิทธิ์จาก role มาประกอบด้วย
        $rolePermissions = [];
        if (!empty($user['role']) && !empty($user['tenant_id'])) {
            $roleRow = $db->table('roles')
                          ->where('tenant_id', $user['tenant_id'])
                          ->where('name', $user['role'])
                          ->get()
                          ->getRowArray();
            if ($roleRow && !empty($roleRow['permissions'])) {
                $rolePermissions = json_decode($roleRow['permissions'], true) ?: [];
            }
        }

        // รวมรายการสิทธิ์ทั้งหมด (Unique)
        $allPermissions = array_unique(array_merge($userPermissions, $rolePermissions));

        // 3. จัดกลุ่มสิทธิ์ให้อ่านเข้าใจง่าย
        $permissionGroups = [
            'platform'  => [
                'title' => 'ระบบและแพลตฟอร์มส่วนกลาง',
                'icon'  => 'bi bi-shield-shaded',
                'color' => 'text-bg-dark',
                'items' => ['platform.dashboard', 'tenants.manage', 'tenants.suspend', 'plans.edit', 'system.logs'],
            ],
            'shop'      => [
                'title' => 'แดชบอร์ดและการตั้งค่าร้าน',
                'icon'  => 'bi bi-shop',
                'color' => 'text-bg-primary',
                'items' => ['dashboard', 'settings.view', 'settings.update', 'reports.view'],
            ],
            'inventory' => [
                'title' => 'สินค้า สต็อก และอะไหล่',
                'icon'  => 'bi bi-box-seam',
                'color' => 'text-bg-success',
                'items' => ['products', 'products.create', 'products.edit', 'products.delete', 'inventory.transfer', 'inventory.adjust'],
            ],
            'repairs'   => [
                'title' => 'บริการและงานซ่อมบำรุง',
                'icon'  => 'bi bi-tools',
                'color' => 'text-bg-warning text-dark',
                'items' => ['repairs.view', 'repairs.create', 'repairs.parts_request', 'repairs.approve', 'repairs.deliver'],
            ],
            'sales'     => [
                'title' => 'จุดขาย POS และการเงิน',
                'icon'  => 'bi bi-cart-check',
                'color' => 'text-bg-info text-dark',
                'items' => ['pos.view', 'pos.checkout', 'cash_shifts.open', 'cash_shifts.close', 'wallets.manage'],
            ],
            'staff'     => [
                'title' => 'การบริหารพนักงานและทีมงาน',
                'icon'  => 'bi bi-people',
                'color' => 'text-bg-secondary',
                'items' => ['staff.view', 'staff.manage', 'roles.manage'],
            ],
        ];

        // 4. ดึงการตั้งค่าผู้ใช้
        $userSettings = $this->userSettingService->getUserSettings($userId);

        $data = [
            'title'            => 'โปรไฟล์และสิทธิ์การใช้งาน (User Profile & Account)',
            'user'             => $user,
            'userPermissions'  => $allPermissions,
            'permissionGroups' => $permissionGroups,
            'userSettings'     => $userSettings,
        ];

        return view('admin/profile/index', $data);
    }

    /**
     * อัปเดตข้อมูลส่วนตัว (Name, Phone, Bio, Email)
     */
    public function updateProfile()
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $db = Database::connect();

        $firstName = trim((string)$this->request->getPost('first_name'));
        $lastName  = trim((string)$this->request->getPost('last_name'));
        $phone     = trim((string)$this->request->getPost('phone'));
        $bio       = trim((string)$this->request->getPost('bio'));
        $email     = trim((string)$this->request->getPost('email'));

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$user) {
            return redirect()->back()->with('error', 'ไม่พบข้อมูลผู้ใช้');
        }

        // อัปเดต peoples
        if (!empty($user['person_id'])) {
            $db->table('peoples')->where('id', $user['person_id'])->update([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
                'bio'        => $bio,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // สร้าง record ใน peoples ถ้ายังไม่มี
            $personId = $db->table('peoples')->insert([
                'tenant_id'  => $user['tenant_id'],
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
                'bio'        => $bio,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('users')->where('id', $userId)->update(['person_id' => $personId]);
        }

        // อัปเดต email ถ้ามี
        if ($email) {
            $db->table('users')->where('id', $userId)->update([
                'email'      => $email,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // อัปเดต session
        if (session()->has('user')) {
            $sessUser = session()->get('user');
            $sessUser['name'] = trim($firstName . ' ' . $lastName) ?: $user['username'];
            $sessUser['email'] = $email;
            session()->set('user', $sessUser);
        }

        return redirect()->to(site_url('profile#info'))
                         ->with('success', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
    }

    /**
     * เปลี่ยนรหัสผ่าน (Change Password)
     */
    public function updatePassword()
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $db = Database::connect();

        $currentPwd = (string)$this->request->getPost('current_password');
        $newPwd     = (string)$this->request->getPost('new_password');
        $confirmPwd = (string)$this->request->getPost('confirm_password');

        if (strlen($newPwd) < 6) {
            return redirect()->to(site_url('profile#security'))
                             ->with('error', 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }

        if ($newPwd !== $confirmPwd) {
            return redirect()->to(site_url('profile#security'))
                             ->with('error', 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
        }

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$user) {
            return redirect()->back()->with('error', 'ไม่พบผู้ใช้ในระบบ');
        }

        // ตรวจสอบรหัสผ่านปัจจุบัน
        if (!password_verify($currentPwd, $user['password'])) {
            return redirect()->to(site_url('profile#security'))
                             ->with('error', 'รหัสผ่านเดิมไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
        }

        // บันทึกรหัสผ่านใหม่
        $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
        $db->table('users')->where('id', $userId)->update([
            'password'   => $newHash,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('profile#security'))
                         ->with('success', 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว');
    }

    /**
     * บันทึกการตั้งค่าส่วนบุคคล (User Preferences / Settings)
     */
    public function updateSettings()
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;

        $theme         = $this->request->getPost('theme') ?? 'light';
        $language      = $this->request->getPost('language') ?? 'th';
        $soundEnabled  = $this->request->getPost('sound_enabled') ? '1' : '0';
        $notifyRepair  = $this->request->getPost('notify_repair') ? '1' : '0';
        $notifyStock   = $this->request->getPost('notify_stock') ? '1' : '0';
        $itemsPerPage  = (int)($this->request->getPost('items_per_page') ?? 25);

        $this->userSettingService->saveUserSettings($userId, [
            'theme'          => $theme,
            'language'       => $language,
            'sound_enabled'  => $soundEnabled,
            'notify_repair'  => $notifyRepair,
            'notify_stock'   => $notifyStock,
            'items_per_page' => $itemsPerPage,
        ]);

        // อัปเดต session locale ทันที
        session()->set('locale', $language);

        return redirect()->to(site_url('profile#preferences'))
                         ->with('success', lang('App.saved_successfully'));
    }
}
