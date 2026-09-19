<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use App\Services\ReportService;

class CustomerDebtWidget extends BaseWidget
{
    protected string $id = 'customer_debt_summary';
    protected string $title = 'ยอดหนี้ค้างชำระ (Customer Debt)';
    protected string $description = 'สรุปยอดเงินค้างชำระของลูกค้าในระบบ Debt Wallet';
    protected string $category = 'owner';
    protected string $requiredPermission = 'dashboard';
    protected string $defaultCol = 'col-lg-3 col-sm-6';
    protected string $icon = 'bi bi-wallet2';

    public function render(array $context): string
    {
        $tenantId = (int)($context['tenant_id'] ?? 1);
        $reportService = new ReportService();
        $debtReport = $reportService->getOutstandingDebtReport($tenantId);

        $totalDebt = (float)($debtReport['total_outstanding_debt'] ?? 0);
        $count = (int)($debtReport['total_debtors_count'] ?? 0);

        return $this->buildSmallBox(
            'text-bg-secondary',
            '฿' . number_format($totalDebt, 2),
            "ลูกหนี้ค้างชำระ ({$count} ราย)",
            $this->icon,
            'ดูรายชื่อลูกหนี้',
            site_url('admin/customers/debt')
        );
    }
}
