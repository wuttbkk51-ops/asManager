<?php

namespace App\Services;

use App\Models\TenantSettingModel;

class TenantSettingService
{
    protected $settingModel;

    public function __construct()
    {
        $this->settingModel = new TenantSettingModel();
    }

    /**
     * ดึงการตั้งค่าร้านทั้งหมดพร้อม Default Values
     */
    public function getSettings(int $tenantId): array
    {
        $saved = $this->settingModel->getAllSettings($tenantId);

        return [
            'workflow_mode'           => $saved['workflow_mode'] ?? 'simple', // simple (ร้านทั่วไป), standard (บริษัท)
            'costing_method'          => $saved['costing_method'] ?? 'moving_average',
            'inventory_costing_method'=> $saved['inventory_costing_method'] ?? ($saved['costing_method'] ?? 'moving_average'),
            'enable_landed_cost'      => isset($saved['enable_landed_cost']) ? (int)$saved['enable_landed_cost'] : 1,
            'landed_cost_method'      => $saved['landed_cost_method'] ?? 'by_value',
            'require_cash_shift'      => isset($saved['require_cash_shift']) ? (int)$saved['require_cash_shift'] : 1,
            'enable_serial_tracking'  => isset($saved['enable_serial_tracking']) ? (int)$saved['enable_serial_tracking'] : 1,
            'parts_approval_required' => isset($saved['parts_approval_required']) ? (int)$saved['parts_approval_required'] : 1,
            'enable_wallet_credit'    => isset($saved['enable_wallet_credit']) ? (int)$saved['enable_wallet_credit'] : 1,
            'enable_direct_inbound'   => isset($saved['enable_direct_inbound']) ? (int)$saved['enable_direct_inbound'] : 1,
            'is_onboarded'            => isset($saved['is_onboarded']) ? (int)$saved['is_onboarded'] : 0,
            'shop_phone'              => $saved['shop_phone'] ?? '081-234-5678',
            'shop_address'            => $saved['shop_address'] ?? '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
            'shop_tax_id'             => $saved['shop_tax_id'] ?? '0105550001234',
            'shop_language'           => $saved['shop_language'] ?? 'th',
            'print_paper_size'        => $saved['print_paper_size'] ?? 'slip_80mm',
            'print_header_note'       => $saved['print_header_note'] ?? 'บริการซ่อมมือถือ คอมพิวเตอร์ และจำหน่ายอุปกรณ์ไอทีครบวงจร',
            'print_footer_note'       => $saved['print_footer_note'] ?? 'ขอบพระคุณที่ไว้วางใจใช้บริการ โปรดเก็บเอกสารนี้ไว้เป็นหลักฐาน',

            // งานซ่อม (Repairs)
            'repair_require_serial'    => isset($saved['repair_require_serial']) ? (int)$saved['repair_require_serial'] : 1,
            'repair_default_labor_fee' => isset($saved['repair_default_labor_fee']) ? (float)$saved['repair_default_labor_fee'] : 300.00,
            'repair_notify_customer'   => isset($saved['repair_notify_customer']) ? (int)$saved['repair_notify_customer'] : 1,
            'repair_warranty_days'     => isset($saved['repair_warranty_days']) ? (int)$saved['repair_warranty_days'] : 30,
            'repair_warranty_terms'    => $saved['repair_warranty_terms'] ?? 'รับประกันงานซ่อมและอะไหล่ 30 วัน (ไม่รวมตกหล่น โดนน้ำ หรือแกะเครื่องเอง)',

            // งานขาย (Sales & POS)
            'pos_paper_size'           => $saved['pos_paper_size'] ?? ($saved['print_paper_size'] ?? 'slip_80mm'),
            'pos_allow_manual_discount'=> isset($saved['pos_allow_manual_discount']) ? (int)$saved['pos_allow_manual_discount'] : 1,
            'pos_vat_mode'             => $saved['pos_vat_mode'] ?? 'none', // none (ไม่คิด vat), include (รวมใน), exclude (แยกนอก)
            'pos_vat_rate'             => isset($saved['pos_vat_rate']) ? (float)$saved['pos_vat_rate'] : 7.00,

            // คลังสินค้าและจัดซื้อ (Stock & Inventory)
            'stock_low_threshold'      => isset($saved['stock_low_threshold']) ? (int)$saved['stock_low_threshold'] : 5,
        ];
    }

    /**
     * ดึงข้อมูลร้านค้าจากตาราง tenants
     */
    public function getTenantProfile(int $tenantId): ?array
    {
        return \Config\Database::connect()->table('tenants')->where('id', $tenantId)->get()->getRowArray();
    }

    /**
     * อัปเดตข้อมูลร้านค้าในตาราง tenants
     */
    public function updateTenantProfile(int $tenantId, array $data): bool
    {
        $allowed = ['name'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        if (empty($updateData)) {
            return true;
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return (bool)\Config\Database::connect()->table('tenants')->where('id', $tenantId)->update($updateData);
    }

    /**
     * บันทึกการตั้งค่าร้าน
     */
    public function saveSettings(int $tenantId, array $data): bool
    {
        foreach ($data as $key => $val) {
            $this->settingModel->setSetting($tenantId, $key, $val);
        }
        return true;
    }

    /**
     * ประมวลผลและบันทึกคำตอบจาก Onboarding Wizard
     * ตอบ 4 ข้อง่ายๆ แล้วระบบ config สไตล์การทำงานให้อัตโนมัติ
     */
    public function saveWizardAnswers(int $tenantId, array $answers): array
    {
        $workflowMode   = ($answers['po_policy'] ?? 'direct') === 'require_po' ? 'standard' : 'simple';
        $costingMethod  = in_array($answers['costing_method'] ?? '', ['moving_average', 'latest_cost', 'highest_cost', 'manual'])
                            ? $answers['costing_method']
                            : 'moving_average';
        $landedCost     = !empty($answers['freight_policy']) && $answers['freight_policy'] === 'capitalize' ? 1 : 0;
        $shiftRequired  = !empty($answers['cash_shift_policy']) && $answers['cash_shift_policy'] === 'strict' ? 1 : 0;
        $serialTracking = !empty($answers['serial_policy']) && $answers['serial_policy'] === 'yes' ? 1 : 0;

        $settings = [
            'workflow_mode'             => $workflowMode,
            'costing_method'            => $costingMethod,
            'inventory_costing_method'  => $costingMethod,
            'enable_landed_cost'        => $landedCost,
            'require_cash_shift'        => $shiftRequired,
            'enable_serial_tracking'    => $serialTracking,
            'is_onboarded'              => 1,
        ];

        $this->saveSettings($tenantId, $settings);

        return [
            'success'  => true,
            'settings' => $settings,
            'message'  => 'ตั้งค่าร้านค้าผ่าน Wizard สำเร็จเรียบร้อย',
        ];
    }

    /**
     * ตรวจสอบว่าร้านนี้มีนโยบายบังคับต้องมีใบ PO ก่อนรับสินค้าหรือไม่
     * โหมด standard (บริษัท) = บังคับ
     * โหมด simple (ร้านทั่วไป) = ไม่บังคับ (รับเข้าตรงได้เลย)
     */
    public function isPoRequired(int $tenantId): bool
    {
        $settings = $this->getSettings($tenantId);
        return strtolower($settings['workflow_mode'] ?? 'simple') === 'standard';
    }

    /**
     * ตรวจสอบว่าเป็นโหมดร้านค้าทั่วไป (เน้นเร็ว รับของเข้าตรง) หรือไม่
     */
    public function isSimpleMode(int $tenantId): bool
    {
        $settings = $this->getSettings($tenantId);
        return strtolower($settings['workflow_mode'] ?? 'simple') === 'simple';
    }

    /**
     * ตรวจสอบว่าร้านค้าผ่านการ Onboarding แล้วหรือไม่
     */
    public function isOnboarded(int $tenantId): bool
    {
        $settings = $this->getSettings($tenantId);
        return (int)($settings['is_onboarded'] ?? 0) === 1;
    }
}
