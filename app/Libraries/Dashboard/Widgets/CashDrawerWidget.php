<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class CashDrawerWidget extends BaseWidget
{
    protected string $id = 'cash_drawer';
    protected string $title = 'เงินสดในลิ้นชักหน้าร้าน';
    protected string $description = 'ยอดเงินสดรวมในลิ้นชักทุกกะที่เปิดอยู่หน้าร้าน';
    protected string $category = 'sales';
    protected string $requiredPermission = 'pos.view';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-cash-stack';

    public function render(array $context): string
    {
        $amount = (float)($context['stats']['cash_in_shifts'] ?? 0);

        return $this->buildSmallBox(
            'text-bg-success',
            '฿' . number_format($amount, 2),
            $this->title,
            $this->icon,
            'ตรวจนับกะเงินสด',
            site_url('admin/pos/shifts')
        );
    }
}
