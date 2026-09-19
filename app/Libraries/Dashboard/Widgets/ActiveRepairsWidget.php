<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class ActiveRepairsWidget extends BaseWidget
{
    protected string $id = 'active_repairs';
    protected string $title = 'งานซ่อมค้างส่งมอบทั้งหมด';
    protected string $description = 'จำนวนงานซ่อมที่ยังไม่ปิดจ็อบและส่งมอบให้ลูกค้า';
    protected string $category = 'repairs';
    protected string $requiredPermission = 'repairs.view';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-tools';

    public function render(array $context): string
    {
        $count = (int)($context['stats']['active_repairs'] ?? 0);

        return $this->buildSmallBox(
            'text-bg-warning',
            number_format($count) . ' <span class="fs-6 fw-normal">งาน</span>',
            $this->title,
            $this->icon,
            'ติดตามสถานะซ่อม',
            site_url('admin/repairs')
        );
    }
}
