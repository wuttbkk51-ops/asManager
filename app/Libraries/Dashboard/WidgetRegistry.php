<?php

namespace App\Libraries\Dashboard;

use App\Libraries\Dashboard\Widgets\AdminTenantStatsWidget;
use App\Libraries\Dashboard\Widgets\AdminPlanOverviewWidget;
use App\Libraries\Dashboard\Widgets\AdminSystemLogsWidget;
use App\Libraries\Dashboard\Widgets\PosSalesTodayWidget;
use App\Libraries\Dashboard\Widgets\GrossProfitWidget;
use App\Libraries\Dashboard\Widgets\CustomerDebtWidget;
use App\Libraries\Dashboard\Widgets\ShopInfoWidget;
use App\Libraries\Dashboard\Widgets\ActiveRepairsWidget;
use App\Libraries\Dashboard\Widgets\RepairsInProgressWidget;
use App\Libraries\Dashboard\Widgets\RecentRepairsTableWidget;
use App\Libraries\Dashboard\Widgets\CashDrawerWidget;
use App\Libraries\Dashboard\Widgets\LowStockCountWidget;
use App\Libraries\Dashboard\Widgets\LowStockListWidget;

class WidgetRegistry
{
    protected static array $widgets = [];
    protected static bool $initialized = false;

    /**
     * กำหนดรายการ Widget ทั้งหมดในระบบ
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::register(new AdminTenantStatsWidget());
        self::register(new AdminPlanOverviewWidget());
        self::register(new AdminSystemLogsWidget());
        self::register(new PosSalesTodayWidget());
        self::register(new GrossProfitWidget());
        self::register(new CustomerDebtWidget());
        self::register(new ShopInfoWidget());
        self::register(new ActiveRepairsWidget());
        self::register(new RepairsInProgressWidget());
        self::register(new RecentRepairsTableWidget());
        self::register(new CashDrawerWidget());
        self::register(new LowStockCountWidget());
        self::register(new LowStockListWidget());

        self::$initialized = true;
    }

    /**
     * ลงทะเบียน Widget
     */
    public static function register(WidgetInterface $widget): void
    {
        self::$widgets[$widget->getId()] = $widget;
    }

    /**
     * ดึง Widget ตาม ID
     */
    public static function getWidget(string $id): ?WidgetInterface
    {
        self::init();
        return self::$widgets[$id] ?? null;
    }

    /**
     * ดึง Widget ทั้งหมดที่ลงทะเบียนไว้
     * @return array<string, WidgetInterface>
     */
    public static function getAllWidgets(): array
    {
        self::init();
        return self::$widgets;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ตาม permission ที่ระบุหรือไม่
     */
    public static function checkPermission(string $permission, ?array $userPerms = null, ?string $userRole = null): bool
    {
        // 1. ถ้าส่ง role = 'superadmin' หรือสิทธิ์มี '*' ให้ผ่านทันที
        if ($userRole === 'superadmin') {
            return true;
        }

        if ($userPerms !== null) {
            if (in_array('*', $userPerms, true)) {
                return true;
            }
            if (in_array($permission, $userPerms, true)) {
                return true;
            }
            $controller = explode('.', $permission, 2)[0];
            if (in_array($controller . '.*', $userPerms, true)) {
                return true;
            }
            // หาก role เป็น owner มีสิทธิ์ dashboard ทั่วไป
            if ($userRole === 'owner' && !str_starts_with($permission, 'platform.')) {
                return true;
            }
            return false;
        }

        // 2. ใช้ helper 'can' ถ้าไม่ได้ส่ง params มา
        if (function_exists('is_superadmin') && is_superadmin()) {
            return true;
        }
        if (function_exists('is_owner') && is_owner() && !str_starts_with($permission, 'platform.')) {
            return true;
        }
        if (function_exists('can')) {
            return can($permission);
        }

        return false;
    }

    /**
     * ดึงเฉพาะ Widgets ที่ผู้ใช้ปัจจุบันมีสิทธิ์เข้าถึง (Permission-Enforced)
     * @return array<string, WidgetInterface>
     */
    public static function getAvailableWidgets(?array $userPerms = null, ?string $userRole = null): array
    {
        self::init();
        $available = [];

        foreach (self::$widgets as $id => $widget) {
            $perm = $widget->getRequiredPermission();
            if (self::checkPermission($perm, $userPerms, $userRole)) {
                $available[$id] = $widget;
            }
        }

        return $available;
    }

    /**
     * Layout เริ่มต้นสำหรับแต่ละบทบาท (Default Role Layouts)
     */
    public static function getDefaultLayoutForRole(string $role): array
    {
        $role = strtolower($role);

        switch ($role) {
            case 'superadmin':
                return [
                    ['widget_id' => 'admin_tenant_stats',   'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'admin_plan_overview',   'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'pos_sales_today',      'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'gross_profit_summary', 'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'admin_system_logs',    'col' => 'col-lg-12'],
                ];

            case 'technician':
                return [
                    ['widget_id' => 'active_repairs',       'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'repairs_in_progress',  'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'low_stock_count',      'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'recent_repairs_table', 'col' => 'col-lg-8'],
                    ['widget_id' => 'low_stock_list',       'col' => 'col-lg-4'],
                ];

            case 'sales':
            case 'cashier':
                return [
                    ['widget_id' => 'pos_sales_today',      'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'cash_drawer',          'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'low_stock_count',      'col' => 'col-lg-4 col-sm-6'],
                    ['widget_id' => 'low_stock_list',       'col' => 'col-lg-12'],
                ];

            case 'owner':
            case 'manager':
            default:
                return [
                    ['widget_id' => 'pos_sales_today',      'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'active_repairs',       'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'cash_drawer',          'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'low_stock_count',      'col' => 'col-lg-3 col-sm-6'],
                    ['widget_id' => 'recent_repairs_table', 'col' => 'col-lg-8'],
                    ['widget_id' => 'low_stock_list',       'col' => 'col-lg-4'],
                    ['widget_id' => 'shop_info_card',       'col' => 'col-lg-12'],
                ];
        }
    }
}
