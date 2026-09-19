<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class LowStockCountWidget extends BaseWidget
{
    protected string $id = 'low_stock_count';
    protected string $title = 'อะไหล่/สินค้าสต็อกต่ำ';
    protected string $description = 'จำนวนรายการสินค้าที่ยอดคงเหลือต่ำกว่าเกณฑ์แจ้งเตือน';
    protected string $category = 'inventory';
    protected string $requiredPermission = 'products';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-exclamation-triangle-fill';

    public function render(array $context): string
    {
        $count = (int)($context['stats']['low_stock_count'] ?? 0);

        return $this->buildSmallBox(
            'text-bg-danger',
            number_format($count) . ' <span class="fs-6 fw-normal">รายการ</span>',
            $this->title,
            $this->icon,
            'ออกใบสั่งซื้อ PO',
            site_url('admin/purchases/create')
        );
    }
}
