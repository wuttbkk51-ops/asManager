<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class RecentRepairsTableWidget extends BaseWidget
{
    protected string $id = 'recent_repairs_table';
    protected string $title = 'รายการงานซ่อมล่าสุด (Recent Repair Jobs)';
    protected string $description = 'ตารางแสดงงานซ่อมล่าสุดพร้อมอุปกรณ์ อาการเสีย สถานะ และยอดสุทธิ';
    protected string $category = 'repairs';
    protected string $requiredPermission = 'repairs.view';
    protected string $defaultCol = 'col-lg-8';
    protected string $icon = 'bi bi-wrench-adjustable';

    public function render(array $context): string
    {
        $recentRepairs = $context['stats']['recent_repairs'] ?? [];

        $rows = '';
        if (!empty($recentRepairs)) {
            foreach ($recentRepairs as $job) {
                $jobNo = esc($job['job_no'] ?? '-');
                $customer = esc($job['customer_name'] ?? '-');
                $device = esc(($job['brand'] ?? '') . ' ' . ($job['model'] ?? ''));
                $problem = esc($job['problem_reported'] ?? '-');
                $status = esc($job['status'] ?? 'received');
                $net = number_format((float)($job['net_total'] ?? 0), 2);

                $statusBadge = match($status) {
                    'received'    => 'text-bg-secondary',
                    'in_progress' => 'text-bg-warning',
                    'repaired'    => 'text-bg-info',
                    'delivered'   => 'text-bg-success',
                    'rejected'    => 'text-bg-danger',
                    default       => 'text-bg-secondary'
                };

                $rows .= <<<HTML
                <tr>
                  <td class="fw-bold">
                    <span class="badge bg-secondary-subtle text-secondary border">{$jobNo}</span>
                  </td>
                  <td>
                    <div class="fw-semibold text-truncate" style="max-width: 180px;">{$customer}</div>
                    <small class="text-muted">{$device}</small>
                  </td>
                  <td>
                    <small class="text-truncate d-inline-block" style="max-width: 200px;">{$problem}</small>
                  </td>
                  <td>
                    <span class="badge {$statusBadge}">{$status}</span>
                  </td>
                  <td class="text-end fw-bold text-primary">
                    ฿{$net}
                  </td>
                </tr>
HTML;
            }
        } else {
            $rows = '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-2 d-block mb-1"></i>ยังไม่มีรายการงานซ่อมในระบบ</td></tr>';
        }

        $tableHtml = <<<HTML
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>เลขที่งาน</th>
                <th>ลูกค้า / อุปกรณ์</th>
                <th>อาการเสีย</th>
                <th>สถานะ</th>
                <th class="text-end">ยอดสุทธิ</th>
              </tr>
            </thead>
            <tbody>
              {$rows}
            </tbody>
          </table>
        </div>
HTML;

        return $this->buildCard(
            'card-primary',
            $this->title,
            $this->icon . ' text-primary',
            $tableHtml,
            count($recentRepairs) . ' งานล่าสุด',
            'text-bg-primary'
        );
    }
}
