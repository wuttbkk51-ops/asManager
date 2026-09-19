<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class LowStockListWidget extends BaseWidget
{
    protected string $id = 'low_stock_list';
    protected string $title = 'สินค้าใกล้หมด (Low Stock Alert)';
    protected string $description = 'รายการสินค้าและอะไหล่ที่มีสต็อกเหลือน้อยกว่าเกณฑ์';
    protected string $category = 'inventory';
    protected string $requiredPermission = 'products';
    protected string $defaultCol = 'col-lg-4';
    protected string $icon = 'bi bi-bell-fill';

    public function render(array $context): string
    {
        $items = $context['stats']['low_stock_items'] ?? [];

        $listItems = '';
        if (!empty($items)) {
            foreach ($items as $item) {
                $name = esc($item['name'] ?? '-');
                $sku = esc($item['sku'] ?? '-');
                $stock = number_format((float)($item['current_stock'] ?? 0));
                $price = number_format((float)($item['sell_price'] ?? 0), 2);

                $listItems .= <<<HTML
                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                  <div>
                    <div class="fw-semibold small text-truncate" style="max-width: 180px;">{$name}</div>
                    <small class="text-muted">รหัส: {$sku}</small>
                  </div>
                  <div class="text-end">
                    <span class="badge text-bg-danger rounded-pill fs-7">เหลือ {$stock}</span>
                    <div class="small text-muted mt-1">ขาย ฿{$price}</div>
                  </div>
                </li>
HTML;
            }
        } else {
            $listItems = <<<HTML
            <li class="list-group-item text-center py-4 text-muted">
              <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>สต็อกสินค้าทุกรายการอยู่ในระดับปลอดภัย
            </li>
HTML;
        }

        $bodyHtml = <<<HTML
        <ul class="list-group list-group-flush">
          {$listItems}
        </ul>
HTML;

        return $this->buildCard(
            'card-danger',
            $this->title,
            $this->icon . ' text-danger',
            $bodyHtml,
            'เกณฑ์ ≤ 10 ชิ้น',
            'text-bg-danger'
        );
    }
}
