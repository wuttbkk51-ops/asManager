<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TenantSettingService;

class Settings extends BaseController
{
    protected TenantSettingService $settingService;

    public function __construct()
    {
        $this->settingService = new TenantSettingService();
    }

    /**
     * หน้าตั้งค่าร้านค้า (Shop / Tenant Settings) ตาม Native AdminLTE 4 Tab Layout
     */
    public function index()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $tenant = $this->settingService->getTenantProfile($tenantId);
        $settings = $this->settingService->getSettings($tenantId);

        $data = [
            'title'    => lang('Settings.settings_title'),
            'tenant'   => $tenant,
            'settings' => $settings,
        ];

        return view('admin/settings/index', $data);
    }

    /**
     * แท็บ 1: บันทึกข้อมูลทั่วไปและข้อมูลร้านค้า (General & Shop Info)
     */
    public function updateGeneral()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $name = $this->request->getPost('name');
        $phone = $this->request->getPost('shop_phone');
        $address = $this->request->getPost('shop_address');
        $taxId = $this->request->getPost('shop_tax_id');
        $shopLanguage = $this->request->getPost('shop_language') ?? 'th';
        $headerNote = $this->request->getPost('print_header_note') ?? '';
        $footerNote = $this->request->getPost('print_footer_note') ?? '';

        if ($name) {
            $this->settingService->updateTenantProfile($tenantId, ['name' => $name]);
        }

        $this->settingService->saveSettings($tenantId, [
            'shop_phone'        => $phone,
            'shop_address'      => $address,
            'shop_tax_id'       => $taxId,
            'shop_language'     => $shopLanguage,
            'print_header_note' => $headerNote,
            'print_footer_note' => $footerNote,
        ]);

        return redirect()->to(site_url('dashboard/settings#general'))
                         ->with('success', lang('Settings.saved_general_success'));
    }

    /**
     * แท็บ 2: บันทึกการตั้งค่างานซ่อม (Repairs Workflow)
     */
    public function updateRepair()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;

        $partsApproval = $this->request->getPost('parts_approval_required') ? 1 : 0;
        $requireSerial = $this->request->getPost('repair_require_serial') ? 1 : 0;
        $defaultLabor = (float)($this->request->getPost('repair_default_labor_fee') ?? 300);
        $notifyCustomer = $this->request->getPost('repair_notify_customer') ? 1 : 0;
        $warrantyDays = (int)($this->request->getPost('repair_warranty_days') ?? 30);
        $warrantyTerms = trim((string)$this->request->getPost('repair_warranty_terms'));

        $this->settingService->saveSettings($tenantId, [
            'parts_approval_required'  => $partsApproval,
            'repair_require_serial'    => $requireSerial,
            'repair_default_labor_fee' => $defaultLabor,
            'repair_notify_customer'   => $notifyCustomer,
            'repair_warranty_days'     => $warrantyDays,
            'repair_warranty_terms'    => $warrantyTerms,
        ]);

        return redirect()->to(site_url('dashboard/settings#repair'))
                         ->with('success', lang('Settings.saved_repair_success'));
    }

    /**
     * แท็บ 3: บันทึกการตั้งค่างานขาย (Sales & POS)
     */
    public function updateSales()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;

        $requireCashShift = $this->request->getPost('require_cash_shift') ? 1 : 0;
        $walletCredit = $this->request->getPost('enable_wallet_credit') ? 1 : 0;
        $paperSize = $this->request->getPost('pos_paper_size') ?? 'slip_80mm';
        $allowDiscount = $this->request->getPost('pos_allow_manual_discount') ? 1 : 0;
        $vatMode = $this->request->getPost('pos_vat_mode') ?? 'none';
        $vatRate = (float)($this->request->getPost('pos_vat_rate') ?? 7);

        $this->settingService->saveSettings($tenantId, [
            'require_cash_shift'        => $requireCashShift,
            'enable_wallet_credit'      => $walletCredit,
            'pos_paper_size'            => $paperSize,
            'print_paper_size'          => $paperSize,
            'pos_allow_manual_discount' => $allowDiscount,
            'pos_vat_mode'              => $vatMode,
            'pos_vat_rate'              => $vatRate,
        ]);

        return redirect()->to(site_url('dashboard/settings#sales'))
                         ->with('success', lang('Settings.saved_sales_success'));
    }

    /**
     * แท็บ 4: บันทึกการตั้งค่าคลังสินค้าและจัดซื้อ (Stock & Inventory)
     */
    public function updateStock()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;

        $workflowMode = $this->request->getPost('workflow_mode') === 'standard' ? 'standard' : 'simple';
        $costingMethod = $this->request->getPost('costing_method') ?? 'moving_average';
        $enableLandedCost = $this->request->getPost('enable_landed_cost') ? 1 : 0;
        $landedCostMethod = $this->request->getPost('landed_cost_method') ?? 'by_value';
        $serialTracking = $this->request->getPost('enable_serial_tracking') ? 1 : 0;
        $stockLowThreshold = (int)($this->request->getPost('stock_low_threshold') ?? 5);

        $this->settingService->saveSettings($tenantId, [
            'workflow_mode'            => $workflowMode,
            'costing_method'           => $costingMethod,
            'inventory_costing_method' => $costingMethod,
            'enable_landed_cost'       => $enableLandedCost,
            'landed_cost_method'       => $landedCostMethod,
            'enable_serial_tracking'   => $serialTracking,
            'stock_low_threshold'      => $stockLowThreshold,
            'enable_direct_inbound'    => $workflowMode === 'simple' ? 1 : 0,
        ]);

        return redirect()->to(site_url('dashboard/settings#stock'))
                         ->with('success', lang('Settings.saved_stock_success'));
    }

    // =========================================================================
    // Backward-Compatibility Aliases
    // =========================================================================
    public function updateShop()
    {
        return $this->updateGeneral();
    }

    public function updateWorkflow()
    {
        return $this->updateStock();
    }

    public function updateCosting()
    {
        return $this->updateStock();
    }

    public function updatePrint()
    {
        return $this->updateGeneral();
    }
}
