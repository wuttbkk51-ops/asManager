<!--begin::Reusable Product Form Component (Minimal & Progressive Disclosure)-->
<form id="product-form" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="id" id="prod-id" value="">

  <!-- โซนจำเป็น: ข้อมูลสินค้าและราคา -->
  <div class="mb-3">
    <label class="form-label fw-medium" for="prod-name">
      <?= lang('Products.form_name') ?> <span class="text-danger">*</span>
    </label>
    <input 
      type="text" 
      class="form-control" 
      id="prod-name" 
      name="name" 
      placeholder="เช่น หน้าจอ iPhone 13 OLED, แบตเตอรี่, ค่าบริการเปลี่ยนฟิล์ม" 
      required 
      autofocus
    />
    <div class="invalid-feedback"><?= lang('Products.form_name') ?> *</div>
  </div>

  <div class="row g-3 mb-3">
    <!-- ราคาขายหน้าร้าน -->
    <div class="col-sm-6">
      <label class="form-label fw-medium" for="prod-sell-price">
        <?= lang('Products.form_price') ?> <span class="text-danger">*</span>
      </label>
      <div class="input-group">
        <span class="input-group-text">฿</span>
        <input 
          type="number" 
          step="0.01" 
          min="0" 
          class="form-control text-end" 
          id="prod-sell-price" 
          name="sell_price" 
          placeholder="0.00" 
          required 
        />
      </div>
      <div class="invalid-feedback"><?= lang('Products.form_price') ?> *</div>
    </div>

    <!-- ราคาต้นทุน -->
    <div class="col-sm-6">
      <label class="form-label fw-medium text-muted" for="prod-cost-price">
        <?= lang('Products.form_cost') ?>
      </label>
      <div class="input-group">
        <span class="input-group-text">฿</span>
        <input 
          type="number" 
          step="0.01" 
          min="0" 
          class="form-control text-end" 
          id="prod-cost-price" 
          name="cost_price" 
          placeholder="0.00" 
        />
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <!-- หมวดหมู่ -->
    <div class="col-sm-6">
      <label class="form-label fw-medium" for="prod-category"><?= lang('Products.form_category') ?></label>
      <select class="form-select" id="prod-category" name="category_id">
        <option value="">-- <?= lang('App.all') ?> --</option>
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= esc($cat['id']) ?>"><?= esc($cat['name']) ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <!-- สต็อกตั้งต้น -->
    <div class="col-sm-6" id="initial-stock-group">
      <label class="form-label fw-medium" for="prod-initial-stock">
        <?= lang('Products.form_stock') ?>
      </label>
      <input 
        type="number" 
        step="1" 
        min="0" 
        class="form-control text-end" 
        id="prod-initial-stock" 
        name="initial_stock" 
        placeholder="0" 
        value="0" 
      />
    </div>
  </div>

  <!-- โซนตัวเลือกเพิ่มเติม (พับซ่อน) -->
  <div class="accordion mb-4" id="advancedAccordion">
    <div class="accordion-item border-0 bg-body-tertiary rounded">
      <h2 class="accordion-header" id="headingAdvanced">
        <button 
          class="accordion-button collapsed py-2 px-3 bg-transparent small fw-medium text-secondary" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#collapseAdvanced" 
          aria-expanded="false" 
          aria-controls="collapseAdvanced"
        >
          ตั้งค่าเพิ่มเติม (บาร์โค้ด, SKU, Serial, หน่วยนับ)
        </button>
      </h2>
      <div id="collapseAdvanced" class="accordion-collapse collapse" aria-labelledby="headingAdvanced" data-bs-parent="#advancedAccordion">
        <div class="accordion-body p-3 pt-0 small">
          <div class="row g-3">

            <!-- รหัสสินค้า SKU -->
            <div class="col-sm-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-medium mb-0" for="prod-sku"><?= lang('Products.form_sku') ?></label>
                <button type="button" class="btn btn-link p-0 text-muted small text-decoration-none" id="btn-gen-sku">
                  <?= lang('App.create') ?> Auto
                </button>
              </div>
              <input type="text" class="form-control font-monospace" id="prod-sku" name="sku" placeholder="เว้นว่างเพื่อให้ระบบสร้างให้" />
            </div>

            <!-- บาร์โค้ด -->
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="prod-barcode"><?= lang('Products.form_barcode') ?></label>
              <input type="text" class="form-control font-monospace" id="prod-barcode" name="barcode" placeholder="สแกนหรือระบุบาร์โค้ด" />
            </div>

            <!-- หน่วยนับ -->
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="prod-unit"><?= lang('Products.form_unit') ?></label>
              <input type="text" class="form-control" id="prod-unit" name="unit" value="ชิ้น" placeholder="ชิ้น, เครื่อง, ชุด, กล่อง" />
            </div>

            <!-- สูตรคิดต้นทุนเฉพาะชิ้น -->
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="prod-costing"><?= lang('Settings.costing_method') ?></label>
              <select class="form-select" id="prod-costing" name="costing_method">
                <option value="">ตามค่าเริ่มต้นของร้าน</option>
                <option value="moving_average">ถัวเฉลี่ยเคลื่อนที่ (Moving Average)</option>
                <option value="latest_cost">ซื้อล่าสุด (Latest Cost)</option>
                <option value="highest_cost">ราคาสูงสุด (Highest Cost)</option>
                <option value="manual">กำหนดเอง (Manual Cost)</option>
              </select>
            </div>

            <!-- สวิตช์ประเภทสินค้า -->
            <div class="col-12 pt-2 border-top">
              <div class="row g-3">
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="prod-has-serial" name="has_serial" value="1">
                    <label class="form-check-label fw-medium" for="prod-has-serial">
                      <?= lang('Products.col_serial') ?>
                    </label>
                    <div class="text-muted" style="font-size: 0.75rem;">ระบุหมายเลขรายชิ้น (Serial/IMEI)</div>
                  </div>
                </div>

                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="prod-track-stock" name="track_stock" value="1" checked>
                    <label class="form-check-label fw-medium" for="prod-track-stock">
                      ตัดสต็อกในคลัง
                    </label>
                    <div class="text-muted" style="font-size: 0.75rem;">ปิดหากเป็นงานบริการ/ค่าแรง</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- สถานะเปิดใช้งาน -->
            <div class="col-12" id="is-active-group" style="display: none;">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="prod-is-active" name="is_active" value="1" checked>
                <label class="form-check-label fw-medium" for="prod-is-active">
                  <?= lang('App.active') ?>
                </label>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ปุ่ม Action บันทึก/ยกเลิก -->
  <div class="d-flex justify-content-end gap-2 pt-3 border-top">
    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="offcanvas" id="btn-cancel-form">
      <?= lang('App.cancel') ?>
    </button>
    <button type="submit" class="btn btn-primary px-4" id="btn-submit-form">
      <?= lang('Products.btn_save') ?>
    </button>
  </div>
</form>
<!--end::Reusable Product Form Component-->
