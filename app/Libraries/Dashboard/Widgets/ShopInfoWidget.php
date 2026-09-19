<?php

namespace App\Libraries\Dashboard\Widgets;

use App\Libraries\Dashboard\BaseWidget;

class ShopInfoWidget extends BaseWidget
{
    protected string $id = 'shop_info_card';
    protected string $title = 'ข้อมูลร้านค้าและสถานะระบบ (Shop Info & Status)';
    protected string $description = 'สรุปชื่อร้าน แพ็กเกจที่ใช้ และลิงก์ Onboarding Wizard';
    protected string $category = 'owner';
    protected string $requiredPermission = 'dashboard';
    protected string $defaultCol = 'col-lg-12';
    protected string $icon = 'bi bi-shop';

    public function render(array $context): string
    {
        $userName = esc(function_exists('auth_user') ? (auth_user('name') ?? 'Demo User') : 'Demo User');
        $userEmail = esc(function_exists('auth_user') ? (auth_user('email') ?? '-') : '-');
        $userRole = esc(function_exists('auth_user') ? (auth_user('role') ?? 'owner') : 'owner');
        $tenantName = esc(function_exists('tenant') ? (tenant('name') ?? 'Demo Shop') : 'Demo Shop');
        $tenantSlug = esc(function_exists('tenant') ? (tenant('slug') ?? 'demo-shop') : 'demo-shop');
        $tenantPlan = esc(function_exists('tenant') ? (tenant('plan') ?? 'freePlan') : 'freePlan');
        $wizardUrl = site_url('admin/settings/wizard');

        $bodyHtml = <<<HTML
        <div class="p-3">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 border rounded bg-body-tertiary h-100">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-badge me-1 text-primary"></i> ข้อมูลผู้ใช้และร้านค้า:</h6>
                <ul class="list-unstyled mb-0 small">
                  <li><strong>ชื่อผู้ใช้:</strong> {$userName}</li>
                  <li><strong>อีเมล:</strong> {$userEmail}</li>
                  <li><strong>ร้านค้า:</strong> <span class="badge text-bg-success">{$tenantName}</span> (<code>{$tenantSlug}</code>)</li>
                  <li><strong>แพ็กเกจ (Plan):</strong> <span class="badge text-bg-info text-dark">{$tenantPlan}</span></li>
                  <li><strong>บทบาท (Role):</strong> <span class="badge text-bg-primary">{$userRole}</span></li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 border rounded bg-body-tertiary h-100 d-flex flex-column justify-content-between">
                <div>
                  <h6 class="fw-bold mb-2"><i class="bi bi-sliders me-1 text-success"></i> โหมดการทำงานและตั้งค่าร้าน (Setup Wizard):</h6>
                  <p class="small text-muted mb-3">
                    กำหนดสไตล์การทำงานของร้าน (โหมดร้านทั่วไปรับเข้าตรง ไม่บังคับ PO หรือโหมดบริษัทต้องมี PO)
                  </p>
                </div>
                <div>
                  <a href="{$wizardUrl}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-magic me-1"></i> เปิด Onboarding Wizard ตั้งค่าร้านค้า
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
HTML;

        return $this->buildCard(
            'card-secondary',
            $this->title,
            $this->icon . ' text-secondary',
            $bodyHtml
        );
    }
}
