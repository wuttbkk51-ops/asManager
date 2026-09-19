<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;
use Config\Database;

class AdminSystemLogsWidget extends BaseWidget
{
    protected string $id = 'admin_system_logs';
    protected string $title = 'บันทึกเหตุการณ์ระบบล่าสุด (System Audit Logs)';
    protected string $description = 'ประวัติการเข้าใช้งานและกิจกรรมสำคัญในระดับแพลตฟอร์ม SaaS';
    protected string $category = 'platform';
    protected string $requiredPermission = 'platform.dashboard';
    protected string $defaultCol = 'col-lg-12';
    protected string $icon = 'bi bi-shield-lock-fill';

    public function render(array $context): string
    {
        $db = Database::connect();
        $logs = $db->table('system_logs')
                   ->orderBy('id', 'DESC')
                   ->limit(5)
                   ->get()
                   ->getResultArray();

        $rows = '';
        if (!empty($logs)) {
            foreach ($logs as $log) {
                $details = esc($log['details'] ?? '-');
                $action = esc($log['action'] ?? '-');
                $ip = esc($log['ip_address'] ?? '-');
                $time = esc($log['created_at'] ?? '-');

                $rows .= <<<HTML
                <tr>
                    <td><span class="badge text-bg-secondary border">#{$log['id']}</span></td>
                    <td><span class="badge text-bg-primary font-monospace">{$action}</span></td>
                    <td class="small text-muted">{$details}</td>
                    <td class="small font-monospace">{$ip}</td>
                    <td class="small text-end text-muted">{$time}</td>
                </tr>
HTML;
            }
        } else {
            $rows = '<tr><td colspan="5" class="text-center py-3 text-muted">ไม่พบข้อมูลบันทึกระบบ</td></tr>';
        }

        $tableHtml = <<<HTML
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP Address</th>
                <th class="text-end">Timestamp</th>
              </tr>
            </thead>
            <tbody>
              {$rows}
            </tbody>
          </table>
        </div>
HTML;

        return $this->buildCard(
            'card-dark',
            $this->title,
            $this->icon . ' text-dark',
            $tableHtml,
            count($logs) . ' รายการล่าสุด',
            'text-bg-dark'
        );
    }
}
