<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use App\Services\ReportService;

class GrossProfitWidget extends BaseWidget
{
    protected string $id = 'gross_profit_summary';
    protected string $title = 'กำไรขั้นต้นและมาร์จิน (Gross Profit)';
    protected string $description = 'สรุปกำไรขั้นต้นจากการขายสินค้าหน้าร้านและบริการงานซ่อม';
    protected string $category = 'owner';
    protected string $requiredPermission = 'dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-graph-up-arrow';

    public function render(array $context): string
    {
        $tenantId = (int)($context['tenant_id'] ?? 1);
        $reportService = new ReportService();
        $profit = $reportService->getGrossProfitReport($tenantId);

        $totalProfit = (float)($profit['total_gross_profit'] ?? 0);
        $marginPct = number_format((float)($profit['gross_profit_margin_pct'] ?? 0), 1);

        return $this->buildSmallBox(
            'text-bg-success',
            '฿' . number_format($totalProfit, 2),
            "กำไรขั้นต้นรวม (มาร์จิน {$marginPct}%)",
            $this->icon,
            'ดูรายงานผลประกอบการ',
            site_url('admin/reports/profit')
        );
    }
}
