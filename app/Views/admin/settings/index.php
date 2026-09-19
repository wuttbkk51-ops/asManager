<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Page Header (Minimal & Spacious)-->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-semibold mb-0"><?= lang('Settings.settings_title') ?></h4>
  </div>
  <div>
    <a href="<?= site_url('admin/settings/wizard') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-magic me-1"></i> <?= lang('Settings.onboarding_wizard') ?>
    </a>
  </div>
</div>
<!--end::Page Header-->

<!--begin::Flash Messages-->
<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<!--end::Flash Messages-->

<!--begin::Settings Main Card-->
<div class="card border-0 shadow-sm">
  <div class="card-header border-bottom bg-transparent p-3">
    <ul class="nav nav-pills card-header-pills" id="settings-nav" role="tablist">
      <li class="nav-item">
        <a href="#general" class="nav-link active" data-bs-toggle="pill" role="tab"><?= lang('Settings.tab_general') ?></a>
      </li>
      <li class="nav-item">
        <a href="#repair" class="nav-link" data-bs-toggle="pill" role="tab"><?= lang('Settings.tab_repair') ?></a>
      </li>
      <li class="nav-item">
        <a href="#sales" class="nav-link" data-bs-toggle="pill" role="tab"><?= lang('Settings.tab_sales') ?></a>
      </li>
      <li class="nav-item">
        <a href="#stock" class="nav-link" data-bs-toggle="pill" role="tab"><?= lang('Settings.tab_stock') ?></a>
      </li>
    </ul>
  </div>

  <div class="card-body p-4">
    <div class="tab-content">

      <!-- ============================================================= -->
      <!-- TAB 1: ทั่วไป & ข้อมูลร้าน (General & Shop Info)             -->
      <!-- ============================================================= -->
      <div class="tab-pane fade show active" id="general" role="tabpanel">
        <form action="<?= site_url('dashboard/settings/general') ?>" method="POST">
          <?= csrf_field() ?>
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium" for="shop-name"><?= lang('Settings.shop_name') ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="shop-name" name="name" value="<?= esc($tenant['name'] ?? '') ?>" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium" for="shop-slug"><?= lang('Settings.shop_slug') ?></label>
              <input type="text" class="form-control font-monospace bg-body-tertiary" id="shop-slug" value="<?= esc($tenant['slug'] ?? '') ?>" readonly />
              <div class="form-text"><?= lang('Settings.shop_slug_desc') ?></div>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-medium" for="shop-phone"><?= lang('Settings.shop_phone') ?></label>
              <input type="text" class="form-control" id="shop-phone" name="shop_phone" value="<?= esc($settings['shop_phone'] ?? '') ?>" placeholder="เช่น 02-123-4567, 089-999-8888" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-medium" for="shop-tax-id"><?= lang('Settings.shop_tax_id') ?></label>
              <input type="text" class="form-control font-monospace" id="shop-tax-id" name="shop_tax_id" value="<?= esc($settings['shop_tax_id'] ?? '') ?>" placeholder="เลข 13 หลัก" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-medium" for="shop-language"><?= lang('Settings.default_language') ?></label>
              <select class="form-select" id="shop-language" name="shop_language">
                <option value="th" <?= ($settings['shop_language'] ?? 'th') === 'th' ? 'selected' : '' ?>><?= lang('App.language_th') ?></option>
                <option value="en" <?= ($settings['shop_language'] ?? '') === 'en' ? 'selected' : '' ?>><?= lang('App.language_en') ?></option>
              </select>
              <div class="form-text"><?= lang('Settings.default_language_desc') ?></div>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium" for="shop-address"><?= lang('Settings.shop_address') ?></label>
              <textarea class="form-control" id="shop-address" name="shop_address" rows="2"><?= esc($settings['shop_address'] ?? '') ?></textarea>
              <div class="form-text"><?= lang('Settings.shop_address_desc') ?></div>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium" for="header-note"><?= lang('Settings.header_note') ?></label>
              <input type="text" class="form-control" id="header-note" name="print_header_note" value="<?= esc($settings['print_header_note'] ?? '') ?>" placeholder="<?= lang('Settings.header_note_desc') ?>" />
            </div>

            <div class="col-12">
              <label class="form-label fw-medium" for="footer-note"><?= lang('Settings.footer_note') ?></label>
              <textarea class="form-control" id="footer-note" name="print_footer_note" rows="2"><?= esc($settings['print_footer_note'] ?? '') ?></textarea>
              <div class="form-text"><?= lang('Settings.footer_note_desc') ?></div>
            </div>
          </div>

          <div class="pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-4">
              <?= lang('App.save') ?>
            </button>
          </div>
        </form>
      </div>

      <!-- ============================================================= -->
      <!-- TAB 2: งานซ่อม (Repairs Workflow)                             -->
      <!-- ============================================================= -->
      <div class="tab-pane fade" id="repair" role="tabpanel">
        <form action="<?= site_url('dashboard/settings/repair') ?>" method="POST">
          <?= csrf_field() ?>
          
          <div class="mb-4">
            <!-- อนุมัติอะไหล่ -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.parts_approval') ?></div>
                <div class="text-muted small"><?= lang('Settings.parts_approval_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="parts_approval_required" value="1" <?= ($settings['parts_approval_required'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>

            <!-- สแกน Serial อะไหล่ในงานซ่อม -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.repair_require_serial') ?></div>
                <div class="text-muted small"><?= lang('Settings.repair_require_serial_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="repair_require_serial" value="1" <?= ($settings['repair_require_serial'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>

            <!-- แจ้งเตือนลูกค้า -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.repair_notify_customer') ?></div>
                <div class="text-muted small"><?= lang('Settings.repair_notify_customer_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="repair_notify_customer" value="1" <?= ($settings['repair_notify_customer'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium" for="repair-labor-fee"><?= lang('Settings.labor_fee') ?></label>
              <div class="input-group">
                <span class="input-group-text">฿</span>
                <input type="number" step="0.01" min="0" class="form-control" id="repair-labor-fee" name="repair_default_labor_fee" value="<?= esc($settings['repair_default_labor_fee'] ?? 300) ?>" />
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium" for="repair-warranty-days"><?= lang('Settings.warranty_days') ?></label>
              <select class="form-select" id="repair-warranty-days" name="repair_warranty_days">
                <option value="7" <?= ($settings['repair_warranty_days'] ?? 30) == 7 ? 'selected' : '' ?>>7 วัน (7 Days)</option>
                <option value="15" <?= ($settings['repair_warranty_days'] ?? 30) == 15 ? 'selected' : '' ?>>15 วัน (15 Days)</option>
                <option value="30" <?= ($settings['repair_warranty_days'] ?? 30) == 30 ? 'selected' : '' ?>>30 วัน (1 Month)</option>
                <option value="60" <?= ($settings['repair_warranty_days'] ?? 30) == 60 ? 'selected' : '' ?>>60 วัน (2 Months)</option>
                <option value="90" <?= ($settings['repair_warranty_days'] ?? 30) == 90 ? 'selected' : '' ?>>90 วัน (3 Months)</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-medium" for="repair-warranty-terms"><?= lang('Settings.warranty_terms') ?></label>
              <textarea class="form-control" id="repair-warranty-terms" name="repair_warranty_terms" rows="3"><?= esc($settings['repair_warranty_terms'] ?? '') ?></textarea>
              <div class="form-text"><?= lang('Settings.warranty_terms_desc') ?></div>
            </div>
          </div>

          <div class="pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-4">
              <?= lang('App.save') ?>
            </button>
          </div>
        </form>
      </div>

      <!-- ============================================================= -->
      <!-- TAB 3: งานขาย & POS (Sales & Cashier)                         -->
      <!-- ============================================================= -->
      <div class="tab-pane fade" id="sales" role="tabpanel">
        <form action="<?= site_url('dashboard/settings/sales') ?>" method="POST">
          <?= csrf_field() ?>
          
          <div class="mb-4">
            <!-- บังคับเปิด/ปิดกะเงินสด -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.require_cash_shift') ?></div>
                <div class="text-muted small"><?= lang('Settings.require_cash_shift_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="require_cash_shift" value="1" <?= ($settings['require_cash_shift'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>

            <!-- เครดิตลูกหนี้ & มัดจำ -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.enable_wallet_credit') ?></div>
                <div class="text-muted small"><?= lang('Settings.enable_wallet_credit_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="enable_wallet_credit" value="1" <?= ($settings['enable_wallet_credit'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>

            <!-- อนุญาตให้ใส่ส่วนลดท้ายบิล -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.allow_manual_discount') ?></div>
                <div class="text-muted small"><?= lang('Settings.allow_manual_discount_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="pos_allow_manual_discount" value="1" <?= ($settings['pos_allow_manual_discount'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium" for="pos-paper-size"><?= lang('Settings.paper_size') ?></label>
              <select class="form-select" id="pos-paper-size" name="pos_paper_size">
                <option value="slip_80mm" <?= ($settings['pos_paper_size'] ?? '') === 'slip_80mm' ? 'selected' : '' ?>><?= lang('Settings.paper_slip_80mm') ?></option>
                <option value="slip_58mm" <?= ($settings['pos_paper_size'] ?? '') === 'slip_58mm' ? 'selected' : '' ?>><?= lang('Settings.paper_slip_58mm') ?></option>
                <option value="a5_landscape" <?= ($settings['pos_paper_size'] ?? '') === 'a5_landscape' ? 'selected' : '' ?>><?= lang('Settings.paper_a5') ?></option>
                <option value="a4" <?= ($settings['pos_paper_size'] ?? '') === 'a4' ? 'selected' : '' ?>><?= lang('Settings.paper_a4') ?></option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium" for="pos-vat-mode"><?= lang('Settings.vat_mode') ?></label>
              <select class="form-select" id="pos-vat-mode" name="pos_vat_mode">
                <option value="none" <?= ($settings['pos_vat_mode'] ?? '') === 'none' ? 'selected' : '' ?>><?= lang('Settings.vat_none') ?></option>
                <option value="include" <?= ($settings['pos_vat_mode'] ?? '') === 'include' ? 'selected' : '' ?>><?= lang('Settings.vat_include') ?></option>
                <option value="exclude" <?= ($settings['pos_vat_mode'] ?? '') === 'exclude' ? 'selected' : '' ?>><?= lang('Settings.vat_exclude') ?></option>
              </select>
            </div>
          </div>

          <div class="pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-4">
              <?= lang('App.save') ?>
            </button>
          </div>
        </form>
      </div>

      <!-- ============================================================= -->
      <!-- TAB 4: คลังสินค้า & จัดซื้อ (Stock & Inventory)                -->
      <!-- ============================================================= -->
      <div class="tab-pane fade" id="stock" role="tabpanel">
        <form action="<?= site_url('dashboard/settings/stock') ?>" method="POST">
          <?= csrf_field() ?>
          
          <div class="mb-4">
            <label class="form-label fw-medium"><?= lang('Settings.procurement_workflow') ?></label>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 <?= ($settings['workflow_mode'] ?? 'simple') === 'simple' ? 'border-primary' : '' ?>">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="workflow_mode" id="wf-simple" value="simple" <?= ($settings['workflow_mode'] ?? 'simple') === 'simple' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-medium" for="wf-simple">
                      <?= lang('Settings.wf_simple') ?>
                    </label>
                  </div>
                  <div class="text-muted small mt-1 ps-4">
                    <?= lang('Settings.wf_simple_desc') ?>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 <?= ($settings['workflow_mode'] ?? '') === 'standard' ? 'border-primary' : '' ?>">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="workflow_mode" id="wf-standard" value="standard" <?= ($settings['workflow_mode'] ?? '') === 'standard' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-medium" for="wf-standard">
                      <?= lang('Settings.wf_standard') ?>
                    </label>
                  </div>
                  <div class="text-muted small mt-1 ps-4">
                    <?= lang('Settings.wf_standard_desc') ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium" for="costing-method"><?= lang('Settings.costing_method') ?></label>
              <select class="form-select" id="costing-method" name="costing_method">
                <option value="moving_average" <?= ($settings['costing_method'] ?? '') === 'moving_average' ? 'selected' : '' ?>>
                  <?= lang('Settings.costing_ma') ?>
                </option>
                <option value="latest_cost" <?= ($settings['costing_method'] ?? '') === 'latest_cost' ? 'selected' : '' ?>>
                  <?= lang('Settings.costing_latest') ?>
                </option>
                <option value="highest_cost" <?= ($settings['costing_method'] ?? '') === 'highest_cost' ? 'selected' : '' ?>>
                  <?= lang('Settings.costing_highest') ?>
                </option>
                <option value="manual" <?= ($settings['costing_method'] ?? '') === 'manual' ? 'selected' : '' ?>>
                  <?= lang('Settings.costing_manual') ?>
                </option>
              </select>
              <div class="form-text"><?= lang('Settings.costing_method_desc') ?></div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-medium" for="landed-cost-method"><?= lang('Settings.landed_cost_method') ?></label>
              <select class="form-select" id="landed-cost-method" name="landed_cost_method">
                <option value="by_value" <?= ($settings['landed_cost_method'] ?? '') === 'by_value' ? 'selected' : '' ?>>
                  <?= lang('Settings.landed_by_value') ?>
                </option>
                <option value="by_qty" <?= ($settings['landed_cost_method'] ?? '') === 'by_qty' ? 'selected' : '' ?>>
                  <?= lang('Settings.landed_by_qty') ?>
                </option>
              </select>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="enable-landed-cost" name="enable_landed_cost" value="1" <?= ($settings['enable_landed_cost'] ?? 1) == 1 ? 'checked' : '' ?>>
                <label class="form-check-label small" for="enable-landed-cost">
                  <?= lang('Settings.enable_landed_cost') ?>
                </label>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <!-- สวิตช์ Serial Tracking -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.serial_tracking') ?></div>
                <div class="text-muted small"><?= lang('Settings.serial_tracking_desc') ?></div>
              </div>
              <div class="form-check form-switch fs-5 ms-3 mb-0">
                <input class="form-check-input" type="checkbox" name="enable_serial_tracking" value="1" <?= ($settings['enable_serial_tracking'] ?? 1) == 1 ? 'checked' : '' ?>>
              </div>
            </div>

            <!-- เกณฑ์เตือนสต็อก -->
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
              <div>
                <div class="fw-medium text-body"><?= lang('Settings.stock_low_threshold') ?></div>
                <div class="text-muted small"><?= lang('Settings.stock_low_threshold_desc') ?></div>
              </div>
              <div style="width: 130px;">
                <div class="input-group input-group-sm">
                  <input type="number" min="1" max="999" class="form-control text-end" id="stock-low-threshold" name="stock_low_threshold" value="<?= esc($settings['stock_low_threshold'] ?? 5) ?>" />
                  <span class="input-group-text"><?= lang('Settings.unit_pcs') ?></span>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 border-top text-end">
            <button type="submit" class="btn btn-primary px-4">
              <?= lang('App.save') ?>
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<!--end::Settings Main Card-->

<script>
document.addEventListener('DOMContentLoaded', function() {
  // สลับ Tab ตาม Hash ใน URL เช่น #general, #repair, #sales, #stock (พร้อมรองรับ hash เดิม)
  let hash = window.location.hash;
  if (hash === '#shop' || hash === '#print') hash = '#general';
  if (hash === '#workflow' || hash === '#costing') hash = '#stock';

  if (hash) {
    const triggerEl = document.querySelector(`#settings-nav a[href="${hash}"]`);
    if (triggerEl) {
      bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }
  }

  // อัปเดต Hash เมื่อคลิก Tab
  document.querySelectorAll('#settings-nav a').forEach(el => {
    el.addEventListener('shown.bs.tab', e => {
      history.replaceState(null, null, e.target.getAttribute('href'));
    });
  });
});
</script>
<?= $this->endSection() ?>
