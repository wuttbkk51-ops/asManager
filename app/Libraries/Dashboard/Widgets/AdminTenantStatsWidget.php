<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use Config\Database;

class AdminTenantStatsWidget extends BaseWidget
{
    protected string $id = 'admin_tenant_stats';
    protected string $title = 'ร้านค้าทั้งหมดในระบบ SaaS';
    protected string $description = 'จำนวนร้านค้า/Tenant ที่เปิดใช้งานอยู่บนแพลตฟอร์ม';
    protected string $category = 'platform';
    protected string $requiredPermission = 'platform.dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-buildings-fill';

    public function render(array $context): string
    {
        $db = Database::connect();
        $totalTenants = $db->table('tenants')->where('status', 'active')->countAllResults();
        $totalUsers = $db->table('users')->where('status', 'active')->countAllResults();

        return $this->buildSmallBox(
            'text-bg-primary',
            number_format($totalTenants) . " <span class=\"fs-6 fw-normal\">ร้าน</span>",
            "ร้านค้าเปิดใช้งาน (รวม {$totalUsers} ผู้ใช้)",
            'bi bi-buildings-fill',
            'จัดการ Tenants',
            site_url('admin/tenants')
        );
    }
}
