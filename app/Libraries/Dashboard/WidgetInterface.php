<?php

namespace App\Libraries\Dashboard;

interface WidgetInterface
{
    /**
     * คืนค่า Unique Identifier ของวิดเจ็ต (เช่น 'pos_sales_today', 'active_repairs')
     */
    public function getId(): string;

    /**
     * ชื่อแสดงผลภาษาไทย
     */
    public function getTitle(): string;

    /**
     * คำอธิบายการทำงาน
     */
    public function getDescription(): string;

    /**
     * หมวดหมู่ของวิดเจ็ต: 'platform', 'owner', 'repairs', 'sales', 'inventory'
     */
    public function getCategory(): string;

    /**
     * สิทธิ์ที่จำเป็นต้องมีในการมองเห็นและใช้งานวิดเจ็ตนี้ (dot notation เช่น 'platform.dashboard', 'repairs.view')
     */
    public function getRequiredPermission(): string;

    /**
     * ขนาดความกว้างเริ่มต้น (Bootstrap 5 / AdminLTE 4 Grid เช่น 'col-lg-3 col-sm-6', 'col-lg-6', 'col-lg-12')
     */
    public function getDefaultCol(): string;

    /**
     * ไอคอน Bootstrap Icons (เช่น 'bi bi-cart-check-fill')
     */
    public function getIcon(): string;

    /**
     * Render ส่วนประกอบ HTML ด้วย Native AdminLTE 4 Component
     */
    public function render(array $context): string;
}
