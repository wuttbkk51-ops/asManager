<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use Config\Database;

class RepairsInProgressWidget extends BaseWidget
{
    protected string $id = 'repairs_in_progress';
    protected string $title = 'งานที่กำลังลงมือซ่อม (In Progress)';
    protected string $description = 'จำนวนงานซ่อมที่ช่างกำลังดำเนินการเปลี่ยนอะไหล่และแก้ไข';
    protected string $category = 'repairs';
    protected string $requiredPermission = 'repairs.view';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-gear-wide-connected';

    public function render(array $context): string
    {
        $tenantId = (int)($context['tenant_id'] ?? 1);
        $db = Database::connect();
        $count = $db->table('repair_jobs')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'in_progress')
                    ->countAllResults();

        return $this->buildSmallBox(
            'text-bg-info',
            number_format($count) . ' <span class="fs-6 fw-normal">เครื่อง</span>',
            $this->title,
            $this->icon,
            'ดูงานซ่อมที่กำลังทำ',
            site_url('admin/repairs?status=in_progress')
        );
    }
}
