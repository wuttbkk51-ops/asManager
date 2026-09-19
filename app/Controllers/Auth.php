<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * แสดงหน้า Login
     */
    public function login()
    {
        if (logged_in()) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login', [
            'title' => 'เข้าสู่ระบบ',
        ]);
    }

    /**
     * ประมวลผลการ Login ด้วย Email จาก Database จริง
     */
    public function attemptLogin()
    {
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if (empty($email) || empty($password)) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกอีเมลและรหัสผ่าน');
        }

        // ค้นหาผู้ใช้พร้อมข้อมูลร้านค้าและสิทธิ์จาก user_permissions
        $user = $this->userModel->getUserWithRoleAndPermissions($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
        }

        // ตรวจสอบสถานะบัญชี
        if ($user['status'] === 'banned') {
            return redirect()->back()->with('error', 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
        }

        if ($user['status'] === 'pending') {
            return redirect()->back()->with('error', 'บัญชีนี้อยู่ระหว่างรอการอนุมัติการใช้งาน');
        }

        // กำหนดชื่อแสดงผล
        $displayName = $user['username'];
        if (!empty($user['person']['first_name'])) {
            $displayName = trim($user['person']['first_name'] . ' ' . ($user['person']['last_name'] ?? ''));
        }

        // ข้อมูลร้านค้า (Tenant)
        $tenantData = null;
        if (!empty($user['tenant'])) {
            $tenantData = [
                'id'   => $user['tenant']['id'],
                'name' => $user['tenant']['name'],
                'slug' => $user['tenant']['slug'],
                'plan' => $user['tenant']['plan'] ?? 'freePlan',
            ];
        }

        // บันทึกข้อมูลเข้า Session
        session()->set([
            'is_logged_in' => true,
            'tenant'       => $tenantData,
            'user'         => [
                'id'        => $user['id'],
                'tenant_id' => $user['tenant_id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'name'      => $displayName,
                'role'      => $user['role'], // flag เช่น 'admin', 'manager', 'staff'
                'status'    => $user['status'],
            ],
            'permissions'  => $user['permissions'], // ดึงจาก user_permissions โดยตรง 100%
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'ยินดีต้อนรับคุณ ' . $displayName);
    }

    /**
     * แสดงหน้า Register (สมัครสมาชิก)
     */
    public function register()
    {
        if (logged_in()) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/register', [
            'title' => 'สมัครสมาชิกใหม่',
        ]);
    }

    /**
     * ประมวลผลการสมัครสมาชิก (UX สั้นกระชับ: Email + Password)
     */
    public function attemptRegister()
    {
        // กฎการตรวจสอบความถูกต้อง
        $rules = [
            'email' => [
                'rules'  => 'required|valid_email|is_unique[users.email]',
                'errors' => [
                    'required'    => 'กรุณากรอกอีเมล',
                    'valid_email' => 'รูปแบบอีเมลไม่ถูกต้อง',
                    'is_unique'   => 'อีเมลนี้ถูกใช้งานแล้ว',
                ],
            ],
            'password' => [
                'rules'  => 'required|min_length[6]',
                'errors' => [
                    'required'   => 'กรุณากรอกรหัสผ่าน',
                    'min_length' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
                ],
            ],
            'password_confirm' => [
                'rules'  => 'required|matches[password]',
                'errors' => [
                    'required' => 'กรุณายืนยันรหัสผ่าน',
                    'matches'  => 'รหัสผ่านยืนยันไม่ตรงกัน',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        // บันทึกผู้ใช้ใหม่ (default tenant_id=1, role='staff', person_id=null)
        $userId = $this->userModel->registerUser($email, $password);

        if (!$userId) {
            return redirect()->back()->withInput()->with('error', 'เกิดข้อผิดพลาดในการสร้างบัญชีผู้ใช้ กรุณาลองใหม่อีกครั้ง');
        }

        // UX สูงสุด: Auto-Login ทันทีหลังสมัครเสร็จ
        $user = $this->userModel->getUserWithRoleAndPermissions($email);

        $tenantData = null;
        if (!empty($user['tenant'])) {
            $tenantData = [
                'id'   => $user['tenant']['id'],
                'name' => $user['tenant']['name'],
                'slug' => $user['tenant']['slug'],
                'plan' => $user['tenant']['plan'] ?? 'freePlan',
            ];
        }

        session()->set([
            'is_logged_in' => true,
            'tenant'       => $tenantData,
            'user'         => [
                'id'        => $user['id'],
                'tenant_id' => $user['tenant_id'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'name'      => $user['username'],
                'role'      => $user['role'],
                'status'    => $user['status'],
            ],
            'permissions'  => $user['permissions'],
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'สมัครสมาชิกสำเร็จ ยินดีต้อนรับเข้าสู่ระบบ!');
    }

    /**
     * ออกจากระบบ
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }

    /**
     * Superadmin จำลองตัวเข้าใช้งานในฐานะร้านค้า (Login As Tenant)
     */
    public function impersonate($tenantId)
    {
        if (!is_superadmin() && !is_impersonating()) {
            return redirect()->back()->with('error', 'เฉพาะ Superadmin เท่านั้นที่สามารถใช้ฟีเจอร์นี้ได้');
        }

        $db = \Config\Database::connect();
        $tenant = $db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
        if (!$tenant) {
            return redirect()->back()->with('error', 'ไม่พบข้อมูลร้านค้าที่ระบุ');
        }

        // ค้นหา Owner ของร้านค้านี้
        $owner = $this->userModel->where('tenant_id', $tenantId)->where('role', 'owner')->first();
        if (!$owner) {
            // หากไม่มี owner ให้หาผู้ใช้คนแรกในร้านนั้น
            $owner = $this->userModel->where('tenant_id', $tenantId)->first();
        }

        if (!$owner) {
            return redirect()->back()->with('error', 'ร้านค้านี้ยังไม่มีบัญชีผู้ใช้งาน');
        }

        // บันทึก Session เดิมของ Superadmin ไว้
        if (!is_impersonating()) {
            session()->set('impersonator', [
                'user'        => session()->get('user'),
                'tenant'      => session()->get('tenant'),
                'permissions' => session()->get('permissions'),
            ]);
        }

        // โหลดข้อมูลและสิทธิ์ของ Owner ร้านเป้าหมาย
        $targetUser = $this->userModel->getUserWithRoleAndPermissions($owner['email']);

        $tenantData = [
            'id'   => $tenant['id'],
            'name' => $tenant['name'],
            'slug' => $tenant['slug'],
            'plan' => $tenant['plan'] ?? 'freePlan',
        ];

        session()->set([
            'tenant'      => $tenantData,
            'user'        => [
                'id'        => $targetUser['id'],
                'tenant_id' => $targetUser['tenant_id'],
                'username'  => $targetUser['username'],
                'email'     => $targetUser['email'],
                'name'      => $targetUser['username'],
                'role'      => $targetUser['role'],
                'status'    => $targetUser['status'],
            ],
            'permissions' => $targetUser['permissions'],
        ]);

        // บันทึก Log การสลับตัว
        $db->table('system_logs')->insert([
            'user_id'    => impersonator('user')['id'] ?? null,
            'tenant_id'  => $tenantId,
            'action'     => 'tenant.impersonate',
            'details'    => "Superadmin เข้าจำลองใช้งานร้านค้า: {$tenant['name']} (ID: {$tenantId})",
            'ip_address' => service('request')->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', "เข้าสู่โหมดจำลองตัวเป็นร้านค้า: {$tenant['name']}");
    }

    /**
     * สลับกลับสู่บัญชี Superadmin ดังเดิม
     */
    public function stopImpersonating()
    {
        if (!is_impersonating()) {
            return redirect()->to(site_url('dashboard'));
        }

        $backup = session()->get('impersonator');

        session()->set([
            'tenant'      => $backup['tenant'],
            'user'        => $backup['user'],
            'permissions' => $backup['permissions'],
        ]);

        session()->remove('impersonator');

        return redirect()->to(site_url('dashboard'))->with('success', 'สลับกลับสู่บัญชี Superadmin เรียบร้อยแล้ว');
    }
}
