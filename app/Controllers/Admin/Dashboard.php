<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Dashboard\WidgetRegistry;
use App\Services\DashboardLayoutService;
use App\Services\ReportService;
use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends BaseController
{
    protected ReportService $reportService;
    protected DashboardLayoutService $layoutService;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->layoutService = new DashboardLayoutService();
        WidgetRegistry::init();
    }

    public function index(): string
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userRole = function_exists('auth_user') && auth_user('role') ? (string)auth_user('role') : 'owner';
        $userPerms = session()->get('permissions') ?? [];

        // ถ้าเป็น Superadmin จะไม่มี tenant_id
        if (function_exists('is_superadmin') && is_superadmin()) {
            $tenantId = null;
        }

        $stats = $this->reportService->getDashboardStats($tenantId ?: 1);

        $context = [
            'tenant_id' => $tenantId ?: 1,
            'user_id'   => $userId,
            'role'      => $userRole,
            'stats'     => $stats,
        ];

        // ดึง Layout ที่ผู้ใช้จัดวางไว้ (หรือ Default ตาม Role) ที่ผ่านการกรองสิทธิ์แล้ว
        $userLayout = $this->layoutService->getUserLayout($userId, $tenantId, $userRole, $userPerms);

        // ดึงรายการ Widget ทั้งหมดที่ User คนนี้มีสิทธิ์ (สำหรับแสดงใน Drawer "+ เพิ่มวิดเจ็ต")
        $availableWidgets = WidgetRegistry::getAvailableWidgets($userPerms, $userRole);

        // Render HTML ของแต่ละ Widget ใน Layout
        $renderedWidgets = [];
        foreach ($userLayout as $item) {
            $widgetId = $item['widget_id'];
            $widget = WidgetRegistry::getWidget($widgetId);
            if ($widget) {
                $renderedWidgets[] = [
                    'widget_id' => $widgetId,
                    'title'     => $widget->getTitle(),
                    'col'       => $item['col'],
                    'icon'      => $widget->getIcon(),
                    'category'  => $widget->getCategory(),
                    'html'      => $widget->render($context),
                ];
            }
        }

        $data = [
            'title'            => 'แดชบอร์ดภาพรวมระบบและร้านค้า (Custom Dashboard)',
            'stats'            => $stats,
            'userLayout'       => $userLayout,
            'renderedWidgets'  => $renderedWidgets,
            'availableWidgets' => $availableWidgets,
            'userRole'         => $userRole,
        ];

        return view('dashboard', $data);
    }

    /**
     * AJAX บันทึกการจัดวาง Layout แดชบอร์ด
     */
    public function saveLayout(): ResponseInterface
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userRole = function_exists('auth_user') && auth_user('role') ? (string)auth_user('role') : 'owner';
        $userPerms = session()->get('permissions') ?? [];

        if (function_exists('is_superadmin') && is_superadmin()) {
            $tenantId = null;
        }

        $json = $this->request->getJSON(true);
        $rawLayout = $json['layout'] ?? [];

        if (!is_array($rawLayout)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'รูปแบบข้อมูลไม่ถูกต้อง',
            ])->setStatusCode(400);
        }

        $result = $this->layoutService->saveUserLayout($userId, $tenantId, $rawLayout, $userPerms, $userRole);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX คืนค่า Layout กลับเป็นค่าเริ่มต้นตาม Role
     */
    public function resetLayout(): ResponseInterface
    {
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userRole = function_exists('auth_user') && auth_user('role') ? (string)auth_user('role') : 'owner';

        if (function_exists('is_superadmin') && is_superadmin()) {
            $tenantId = null;
        }

        $result = $this->layoutService->resetUserLayout($userId, $tenantId, $userRole);

        return $this->response->setJSON($result);
    }
}
