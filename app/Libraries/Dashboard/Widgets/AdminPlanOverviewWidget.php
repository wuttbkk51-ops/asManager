<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use Config\Database;

class AdminPlanOverviewWidget extends BaseWidget
{
    protected string $id = 'admin_plan_overview';
    protected string $title = 'การกระจายแพ็กเกจ SaaS';
    protected string $description = 'สถิติจำนวนร้านค้าที่สมัครใช้งานในแต่ละ Subscription Plan';
    protected string $category = 'platform';
    protected string $requiredPermission = 'platform.dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-pie-chart-fill';

    public function render(array $context): string
    {
        $db = Database::connect();
        $plans = $db->table('plans')->where('is_active', 1)->get()->getResultArray();
        $activeTenants = $db->table('tenants')->countAllResults();

        return $this->buildSmallBox(
            'text-bg-info',
            number_format(count($plans)) . " <span class=\"fs-6 fw-normal\">แพ็กเกจ</span>",
            "แพ็กเกจระบบ (Free / Pro / Enterprise)",
            'bi bi-pie-chart-fill',
            'จัดการแพ็กเกจ',
            site_url('admin/plans')
        );
    }
}
