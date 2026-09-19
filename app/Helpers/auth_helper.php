<?php

if (!function_exists('can')) {
    /**
     * ตรวจสอบสิทธิ์ของผู้ใช้ปัจจุบัน (ใช้รูปแบบ dot notation '.' เหมือน CodeIgniter Shield)
     * กติกา:
     * - "*" : ผ่านทุกสิทธิ์ในระบบ
     * - "controller.*" : สิทธิ์ทุก Action ในโมดูลนั้น เช่น 'products.*'
     * - "controller" : สิทธิ์เข้าถึงหน้าโมดูล/ดูหน้าหลัก เช่น 'products'
     * - "controller.action" : สิทธิ์เฉพาะ Action นั้นๆ เช่น 'products.delete'
     *
     * @param string $permission เช่น "products", "products.delete"
     * @return bool
     */
    function can(string $permission): bool
    {
        $userPerms = session()->get('permissions') ?? [];

        // 1. สิทธิ์สูงสุด (*)
        if (in_array('*', $userPerms, true)) {
            return true;
        }

        // 2. สิทธิ์ตรงตัว เช่น 'products' หรือ 'products.delete'
        if (in_array($permission, $userPerms, true)) {
            return true;
        }

        // 3. สิทธิ์ระดับกลุ่ม 'controller.*' (เหมือน CodeIgniter Shield)
        // หากผู้ใช้มี 'products.*' จะครอบคลุมทั้ง 'products' และ 'products.delete'
        $controller = explode('.', $permission, 2)[0];
        if (in_array($controller . '.*', $userPerms, true)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('auth_user')) {
    /**
     * ดึงข้อมูลผู้ใช้ที่กำลังล็อกอิน
     *
     * @param string|null $key เช่น 'name', 'email', 'role'
     * @return mixed
     */
    function auth_user(?string $key = null)
    {
        $user = session()->get('user') ?? [];
        return $key !== null ? ($user[$key] ?? null) : $user;
    }
}

if (!function_exists('tenant')) {
    /**
     * ดึงข้อมูลร้านค้า/สาขาปัจจุบัน (Tenant)
     *
     * @param string|null $key เช่น 'id', 'name', 'slug'
     * @return mixed
     */
    function tenant(?string $key = null)
    {
        $tenant = session()->get('tenant') ?? [];
        return $key !== null ? ($tenant[$key] ?? null) : $tenant;
    }
}

if (!function_exists('logged_in')) {
    /**
     * ตรวจสอบสถานะการเข้าสู่ระบบ
     */
    function logged_in(): bool
    {
        return (bool) session()->get('is_logged_in');
    }
}

if (!function_exists('is_superadmin')) {
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันเป็น Superadmin หรือไม่
     */
    function is_superadmin(): bool
    {
        return auth_user('role') === 'superadmin';
    }
}

if (!function_exists('is_owner')) {
    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันเป็น Owner ของร้านค้าหรือไม่
     */
    function is_owner(): bool
    {
        return auth_user('role') === 'owner';
    }
}

if (!function_exists('is_impersonating')) {
    /**
     * ตรวจสอบว่ากำลังอยู่ในโหมดจำลองตัวเป็นร้านค้า (Login As) หรือไม่
     */
    function is_impersonating(): bool
    {
        return !empty(session()->get('impersonator'));
    }
}

if (!function_exists('impersonator')) {
    /**
     * ดึงข้อมูล Superadmin ตัวจริงขณะกำลังอยู่ในโหมดจำลองตัว
     */
    function impersonator(?string $key = null)
    {
        $impersonator = session()->get('impersonator') ?? [];
        return $key !== null ? ($impersonator[$key] ?? null) : $impersonator;
    }
}
