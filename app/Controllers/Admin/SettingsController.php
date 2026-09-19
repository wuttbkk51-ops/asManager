<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TenantSettingService;

class SettingsController extends BaseController
{
    protected $settingService;

    public function __construct()
    {
        $this->settingService = new TenantSettingService();
    }

    /**
     * แสดงหน้า Shop Onboarding Wizard
     */
    public function wizard()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $settings = $this->settingService->getSettings($tenantId);

        $data = [
            'title'    => 'ตั้งค่าร้านค้า (Shop Onboarding Wizard)',
            'settings' => $settings,
        ];

        return view('admin/settings/wizard', $data);
    }

    /**
     * บันทึกคำตอบจาก Onboarding Wizard
     */
    public function saveWizard()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $postData = $this->request->getPost();

        $result = $this->settingService->saveWizardAnswers($tenantId, $postData);

        return redirect()->to(site_url('dashboard'))
                         ->with('success', $result['message']);
    }
}
