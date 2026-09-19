<?php

namespace App\Services;

use App\Libraries\Dashboard\WidgetRegistry;
use App\Models\UserDashboardLayoutModel;

class DashboardLayoutService
{
    protected UserDashboardLayoutModel $layoutModel;

    public function __construct()
    {
        $this->layoutModel = new UserDashboardLayoutModel();
        WidgetRegistry::init();
    }

    /**
     * ดึง Layout ของผู้ใช้ พร้อมกรองสิทธิ์ล่าสุด (Permission-Enforced)
     * @return array<int, array{widget_id: string, col: string}>
     */
    public function getUserLayout(int $userId, ?int $tenantId, string $role, ?array $userPerms = null): array
    {
        $savedLayout = $this->layoutModel->getLayout($userId, $tenantId);

        $layout = $savedLayout ?: WidgetRegistry::getDefaultLayoutForRole($role);

        // กรองเอาเฉพาะ Widget ที่มีอยู่ในระบบและผู้ใช้มีสิทธิ์เข้าถึงจริง
        $filtered = [];
        foreach ($layout as $item) {
            $widgetId = $item['widget_id'] ?? '';
            $widget = WidgetRegistry::getWidget($widgetId);

            if ($widget && WidgetRegistry::checkPermission($widget->getRequiredPermission(), $userPerms, $role)) {
                $filtered[] = [
                    'widget_id' => $widgetId,
                    'col'       => $item['col'] ?? $widget->getDefaultCol(),
                ];
            }
        }

        // หากกรองแล้วว่างเปล่า (เช่น เคยบันทึกไว้แต่วิดเจ็ตถูกเอาออกหมด) ให้คืน default layout ตาม role ที่กรองแล้ว
        if (empty($filtered)) {
            $defaultLayout = WidgetRegistry::getDefaultLayoutForRole($role);
            foreach ($defaultLayout as $item) {
                $widgetId = $item['widget_id'] ?? '';
                $widget = WidgetRegistry::getWidget($widgetId);
                if ($widget && WidgetRegistry::checkPermission($widget->getRequiredPermission(), $userPerms, $role)) {
                    $filtered[] = [
                        'widget_id' => $widgetId,
                        'col'       => $item['col'] ?? $widget->getDefaultCol(),
                    ];
                }
            }
        }

        return $filtered;
    }

    /**
     * บันทึก Layout ของผู้ใช้ พร้อม Server-side Permission Validation
     */
    public function saveUserLayout(int $userId, ?int $tenantId, array $rawLayout, ?array $userPerms = null, ?string $role = null): array
    {
        $sanitized = [];

        foreach ($rawLayout as $item) {
            $widgetId = is_array($item) ? ($item['widget_id'] ?? '') : '';
            $col = is_array($item) ? ($item['col'] ?? '') : '';

            $widget = WidgetRegistry::getWidget($widgetId);
            if (!$widget) {
                continue;
            }

            // ตรวจสอบสิทธิ์ฝั่ง Server ห้ามผู้ใช้บันทึก Widget ที่ตนเองไม่มีสิทธิ์
            if (!WidgetRegistry::checkPermission($widget->getRequiredPermission(), $userPerms, $role)) {
                continue;
            }

            // Sanitized col class (อนุญาตเฉพาะ Bootstrap Grid col-*)
            if (!preg_match('/^col(-[a-z]+)*-[0-9]+(\s+col(-[a-z]+)*-[0-9]+)*$/', trim($col))) {
                $col = $widget->getDefaultCol();
            }

            $sanitized[] = [
                'widget_id' => $widgetId,
                'col'       => $col ?: $widget->getDefaultCol(),
            ];
        }

        if (empty($sanitized)) {
            return [
                'success' => false,
                'message' => 'ไม่มี Widget ที่ถูกต้องหรือท่านไม่มีสิทธิ์ในการบันทึก Widget ดังกล่าว',
            ];
        }

        $saved = $this->layoutModel->saveLayout($userId, $tenantId, $sanitized);

        return [
            'success' => $saved,
            'message' => $saved ? 'บันทึกการจัดวางแดชบอร์ดสำเร็จเรียบร้อย' : 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
            'layout'  => $sanitized,
        ];
    }

    /**
     * คืนค่า Layout กลับเป็นค่าเริ่มต้นของ Role
     */
    public function resetUserLayout(int $userId, ?int $tenantId, string $role): array
    {
        $deleted = $this->layoutModel->deleteLayout($userId, $tenantId);
        $defaultLayout = WidgetRegistry::getDefaultLayoutForRole($role);

        return [
            'success' => true,
            'message' => 'คืนค่าเริ่มต้นของแดชบอร์ดเรียบร้อยแล้ว',
            'layout'  => $defaultLayout,
        ];
    }
}
