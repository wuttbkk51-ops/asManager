<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class PosSalesTodayWidget extends BaseWidget
{
    protected string $id = 'pos_sales_today';
    protected string $title = 'ยอดขายหน้าร้าน POS วันนี้';
    protected string $description = 'สรุปรายได้รวมจากการขายหน้าร้านวันนี้';
    protected string $category = 'owner';
    protected string $requiredPermission = 'dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-cart-check-fill';

    public function render(array $context): string
    {
        $amount = (float)($context['stats']['today_pos_sales'] ?? 0);

        return $this->buildSmallBox(
            'text-bg-primary',
            '฿' . number_format($amount, 2),
            $this->title,
            $this->icon,
            'ดูประวัติการขาย POS',
            site_url('admin/pos/orders')
        );
    }
}
