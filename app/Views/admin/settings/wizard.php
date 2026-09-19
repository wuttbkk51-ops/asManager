<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
  <div class="col-lg-10">
    <!--begin::Card Onboarding Wizard-->
    <div class="card card-primary card-outline shadow-sm mb-4">
      <div class="card-header py-3">
        <h4 class="card-title fw-bold mb-1">
          <i class="bi bi-magic me-2 text-primary"></i>แบบสอบถามตั้งค่าร้านค้า (Shop Onboarding Wizard)
        </h4>
        <p class="text-muted small mb-0">
          ตอบคำถาม 4 ข้อง่ายๆ เพื่อให้ระบบปรับแต่งพฤติกรรมการทำงาน (Workflow & Policy) ให้เข้ากับธุรกิจของคุณที่สุด
        </p>
      </div>
      
      <form action="<?= site_url('admin/settings/wizard') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="card-body p-4">

          <!-- ข้อที่ 1: กระบวนการสั่งซื้อและรับสินค้าเข้า -->
          <div class="mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-dark mb-2">
              <span class="badge bg-primary rounded-circle me-2">1</span>กระบวนการสั่งซื้อและรับสินค้าเข้าสต็อก
            </h5>
            <p class="text-muted small mb-3">ร้านของคุณมีระเบียบการจัดซื้อสินค้าอย่างไร?</p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer hover-shadow transition" for="po_direct">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="po_policy" id="po_direct" value="direct" <?= ($settings['workflow_mode'] ?? '') === 'simple' ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> โหมดร้านทั่วไป / โชห่วย (Direct Inbound)</strong>
                      <span class="text-muted small">เน้นความรวดเร็วและง่าย รับสินค้าเข้าคลังได้ทันทีโดยไม่ต้องออกใบสั่งซื้อ (PO)</span>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer hover-shadow transition" for="po_require">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="po_policy" id="po_require" value="require_po" <?= ($settings['workflow_mode'] ?? '') === 'standard' ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-building-check text-primary me-1"></i> โหมดบริษัท / มาตรฐาน (Corporate Workflow)</strong>
                      <span class="text-muted small">ต้องสร้างใบสั่งซื้อ (PO) ก่อน และเมื่อของมาส่งต้องตรวจรับอ้างอิงตาม PO เพื่อตรวจสินค้าขาดเกิน</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- ข้อที่ 2: สูตรการคำนวณต้นทุนสินค้า -->
          <div class="mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-dark mb-2">
              <span class="badge bg-primary rounded-circle me-2">2</span>สูตรการคำนวณต้นทุนสินค้าและอะไหล่ (Costing Method)
            </h5>
            <p class="text-muted small mb-3">เมื่อซื้อสินค้าราคาไม่เท่ากันในแต่ละล็อต ต้องการให้ระบบคิดต้นทุนแบบไหน?</p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="cost_moving_avg">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="costing_method" id="cost_moving_avg" value="moving_average" <?= ($settings['costing_method'] ?? '') === 'moving_average' ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1">ต้นทุนเฉลี่ยถ่วงน้ำหนัก (Moving Average) <span class="badge text-bg-success ms-1">แนะนำ</span></strong>
                      <span class="text-muted small">นำมูลค่าสต็อกเก่า + สต็อกใหม่ แล้วหารด้วยจำนวนรวม สะท้อนต้นทุนจริงทางบัญชี</span>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="cost_latest">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="costing_method" id="cost_latest" value="latest_cost" <?= ($settings['costing_method'] ?? '') === 'latest_cost' ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1">ต้นทุนซื้อล่าสุด (Latest Cost)</strong>
                      <span class="text-muted small">ใช้ราคาของบิลซื้อรอบล่าสุดเป็นต้นทุนของสินค้าชิ้นนั้นทันที เหมาะกับสินค้าที่ราคาเปลี่ยนเร็ว</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- ข้อที่ 3: การจัดการค่าส่งสินค้าเข้า -->
          <div class="mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-dark mb-2">
              <span class="badge bg-primary rounded-circle me-2">3</span>การจัดการค่าจัดส่งสินค้าเข้า (Landed Cost Allocation)
            </h5>
            <p class="text-muted small mb-3">เมื่อมีค่าขนส่งตอนรับสินค้า ต้องการจัดการอย่างไร?</p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="freight_cap">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="freight_policy" id="freight_cap" value="capitalize" <?= ($settings['enable_landed_cost'] ?? 1) == 1 ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-box-seam me-1 text-primary"></i> ปันส่วนค่าส่งเข้าต้นทุนสินค้า (Landed Cost)</strong>
                      <span class="text-muted small">เฉลี่ยค่าขนส่งรวมเข้าเป็นต้นทุนของสินค้าแต่ละชิ้น เพื่อคิดกำไร-ขาดทุนที่แท้จริง</span>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="freight_exp">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="freight_policy" id="freight_exp" value="expense" <?= ($settings['enable_landed_cost'] ?? 1) == 0 ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-receipt me-1 text-secondary"></i> บันทึกเป็นค่าใช้จ่ายดำเนินการหน้าร้านทันที</strong>
                      <span class="text-muted small">ไม่นำค่าส่งไปบวกกับตัวสินค้า แต่บันทึกเป็นค่าใช้จ่ายของร้านในรอบบิลนั้น</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- ข้อที่ 4: การเปิด-ปิดกะเงินสดหน้าร้าน -->
          <div class="mb-3">
            <h5 class="fw-bold text-dark mb-2">
              <span class="badge bg-primary rounded-circle me-2">4</span>การเปิด-ปิดกะลิ้นชักเงินสดหน้าร้าน (Cash Shifts)
            </h5>
            <p class="text-muted small mb-3">ต้องการบังคับให้นับเงินเปิดกะและปิดกะทุกวันหรือไม่?</p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="shift_strict">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="cash_shift_policy" id="shift_strict" value="strict" <?= ($settings['require_cash_shift'] ?? 1) == 1 ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-shield-lock-fill text-success me-1"></i> บังคับมีกะเงินสด (ควบคุมเงินหาย)</strong>
                      <span class="text-muted small">พนักงานขายต้องเปิดกะนับเงินทอน และปิดกะนับเงินจริงเพื่อตรวจยอดเงินเกิน/เงินขาด</span>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <label class="card h-100 p-3 border cursor-pointer" for="shift_flex">
                  <div class="form-check d-flex align-items-start">
                    <input class="form-check-input me-3 mt-1" type="radio" name="cash_shift_policy" id="shift_flex" value="flexible" <?= ($settings['require_cash_shift'] ?? 1) == 0 ? 'checked' : '' ?>>
                    <div>
                      <strong class="d-block text-dark mb-1"><i class="bi bi-unlock text-secondary me-1"></i> ไม่บังคับเปิดกะ (ขายได้ตลอดเวลา)</strong>
                      <span class="text-muted small">เหมาะกับร้านเจ้าของคนเดียว ไม่ต้องเสียเวลานับเงินเปิดกะ-ปิดกะทุกวัน</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
          <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
          </a>
          <button type="submit" class="btn btn-primary px-4 fw-bold">
            <i class="bi bi-check-circle me-1"></i> บันทึกการตั้งค่าร้านค้า
          </button>
        </div>
      </form>
    </div>
    <!--end::Card-->
  </div>
</div>
<?= $this->endSection() ?>
