<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Page Header (Minimal & Clean)-->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-semibold mb-0">Serial Number / IMEI</h4>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-print-selected">
      <i class="bi bi-printer me-1"></i> พิมพ์สติกเกอร์ (<span id="selected-count">0</span>)
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalScanner">
      <i class="bi bi-upc-scan me-1"></i> สแกนรับเข้า
    </button>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAutoGen">
      <i class="bi bi-plus-lg me-1"></i> สร้าง SN อัตโนมัติ
    </button>
  </div>
</div>
<!--end::Page Header-->

<!--begin::Card Filter & Live Search Toolbar-->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-3">
    <div class="row g-2 align-items-center">
      <!-- Search Input -->
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-end-0 text-muted">
            <i class="bi bi-search"></i>
          </span>
          <input 
            type="search" 
            class="form-control border-start-0 ps-0" 
            id="tabulator-search" 
            placeholder="ค้นหา Serial No, ชื่อสินค้า, SKU..." 
            autocomplete="off"
          />
        </div>
      </div>

      <!-- Filter Product -->
      <div class="col-md-4">
        <select class="form-select form-select-sm" id="filter-product">
          <option value="">-- สินค้าทั้งหมด --</option>
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
              <option value="<?= esc($p['id']) ?>"><?= esc($p['name']) ?> (<?= esc($p['sku']) ?>)</option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <!-- Filter Status -->
      <div class="col-md-3">
        <select class="form-select form-select-sm" id="filter-status">
          <option value="">-- สถานะทั้งหมด --</option>
          <option value="in_stock">พร้อมใช้งาน (In Stock)</option>
          <option value="sold">ขายแล้ว (Sold)</option>
          <option value="used_in_repair">ใช้ในงานซ่อม (Repaired)</option>
          <option value="in_transit">ระหว่างขนส่ง (In Transit)</option>
          <option value="damaged">ชำรุดเสียหาย (Damaged)</option>
          <option value="lost">สูญหาย (Lost)</option>
        </select>
      </div>
    </div>
  </div>
</div>
<!--end::Card Filter-->

<!--begin::Card Tabulator Table Container-->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-0">
    <div id="serials-tabulator" class="tabulator-bootstrap-5"></div>
  </div>
</div>
<!--end::Card Tabulator Table-->

<!-- =================================================================== -->
<!-- MODAL 1: BATCH AUTO-GENERATOR (สร้าง SN อัตโนมัติ)                  -->
<!-- =================================================================== -->
<div class="modal fade" id="modalAutoGen" tabindex="-1" aria-labelledby="modalAutoGenLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <form id="form-auto-gen">
        <?= csrf_field() ?>
        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fw-semibold fs-6" id="modalAutoGenLabel">
            สร้าง Serial Number อัตโนมัติ
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-medium" for="gen-product-id">สินค้า <span class="text-danger">*</span></label>
            <select class="form-select" id="gen-product-id" name="product_id" required>
              <option value="">-- เลือกสินค้า --</option>
              <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                  <option value="<?= esc($p['id']) ?>" data-sku="<?= esc($p['sku']) ?>" data-cost="<?= esc($p['cost_price']) ?>">
                    <?= esc($p['name']) ?> (<?= esc($p['sku']) ?>)
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="gen-qty">จำนวนที่ต้องการสร้าง <span class="text-danger">*</span></label>
              <input type="number" class="form-control text-end" id="gen-qty" name="qty" min="1" max="500" value="10" required />
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="gen-prefix">คำนำหน้า (Prefix)</label>
              <input type="text" class="form-control font-monospace text-uppercase" id="gen-prefix" name="prefix" placeholder="เว้นว่างใช้ SKU" />
              <div class="form-text" id="prefix-preview">รูปแบบ: PREFIX-YYMM-0001</div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="gen-branch-id">สาขา</label>
              <select class="form-select" id="gen-branch-id" name="branch_id">
                <?php if (!empty($branches)): ?>
                  <?php foreach ($branches as $b): ?>
                    <option value="<?= esc($b['id']) ?>"><?= esc($b['name']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-medium" for="gen-wh-id">คลังจัดเก็บ</label>
              <select class="form-select" id="gen-wh-id" name="warehouse_id">
                <?php if (!empty($warehouses)): ?>
                  <?php foreach ($warehouses as $w): ?>
                    <option value="<?= esc($w['id']) ?>"><?= esc($w['name']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium" for="gen-notes">หมายเหตุ</label>
            <input type="text" class="form-control" id="gen-notes" name="notes" placeholder="เช่น ล็อตสั่งผลิต, สต็อกยกมา" />
          </div>

          <div class="text-muted small">
            ระบบจะสร้างหมายเลขและบันทึกรับเข้าคลังให้ทันที
          </div>
        </div>
        <div class="modal-footer border-top py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary btn-sm px-3" id="btn-submit-gen">
            ยืนยันสร้าง
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 2: CONTINUOUS SCANNER INBOUND (สแกนรับเข้าต่อเนื่อง)            -->
<!-- =================================================================== -->
<div class="modal fade" id="modalScanner" tabindex="-1" aria-labelledby="modalScannerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <form id="form-scanner-inbound">
        <?= csrf_field() ?>
        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fw-semibold fs-6" id="modalScannerLabel">
            สแกนรับเข้า Serial Number
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-medium" for="scan-product-id">เลือกสินค้า <span class="text-danger">*</span></label>
              <select class="form-select" id="scan-product-id" name="product_id" required>
                <option value="">-- เลือกสินค้า --</option>
                <?php if (!empty($products)): ?>
                  <?php foreach ($products as $p): ?>
                    <option value="<?= esc($p['id']) ?>" data-cost="<?= esc($p['cost_price']) ?>"><?= esc($p['name']) ?> (<?= esc($p['sku']) ?>)</option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-medium" for="scan-branch-id">สาขา</label>
              <select class="form-select" id="scan-branch-id" name="branch_id">
                <?php if (!empty($branches)): ?>
                  <?php foreach ($branches as $b): ?>
                    <option value="<?= esc($b['id']) ?>"><?= esc($b['name']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-medium" for="scan-wh-id">คลังจัดเก็บ</label>
              <select class="form-select" id="scan-wh-id" name="warehouse_id">
                <?php if (!empty($warehouses)): ?>
                  <?php foreach ($warehouses as $w): ?>
                    <option value="<?= esc($w['id']) ?>"><?= esc($w['name']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <!-- ช่องยิงสแกนเนอร์ -->
          <div class="mb-3">
            <label class="form-label fw-medium" for="scanner-live-input">
              ช่องยิงบาร์โค้ด / สแกนเนอร์
            </label>
            <div class="input-group mb-1">
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="scanner-live-input" 
                placeholder="สแกนหรือพิมพ์เลข Serial แล้วกด Enter..." 
                autocomplete="off" 
              />
              <button type="button" class="btn btn-outline-secondary" id="btn-add-scanned-sn">
                เพิ่ม
              </button>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">ระบบตรวจจับรหัสซ้ำให้อัตโนมัติ</small>
              <span class="badge bg-body-secondary text-secondary-emphasis" id="scan-counter-badge">สแกนแล้ว 0 ชิ้น</span>
            </div>
          </div>

          <!-- รายการที่สแกนแล้ว -->
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-medium small">รายการพร้อมนำเข้า:</span>
              <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0" id="btn-clear-scanned">
                ล้างทั้งหมด
              </button>
            </div>
            <div class="border rounded p-2 bg-body-tertiary" style="max-height: 180px; overflow-y: auto;" id="scanned-list-box">
              <div class="text-muted text-center py-4 small" id="scan-empty-placeholder">
                ยังไม่มีรายการที่สแกน ยิงบาร์โค้ดเพื่อเริ่มต้น
              </div>
            </div>
          </div>

          <div>
            <label class="form-label fw-medium" for="scan-notes">หมายเหตุ</label>
            <input type="text" class="form-control form-control-sm" id="scan-notes" name="notes" placeholder="เช่น ล็อตรับเข้าจากซัพพลายเออร์" />
          </div>
        </div>
        <div class="modal-footer border-top py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary btn-sm px-3" id="btn-submit-scanner">
            บันทึกรับเข้าคลัง
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 3: SERIAL TIMELINE / AUDIT TRAIL (ประวัติความเคลื่อนไหว)       -->
<!-- =================================================================== -->
<div class="modal fade" id="modalTimeline" tabindex="-1" aria-labelledby="modalTimelineLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header border-bottom py-3">
        <div>
          <h5 class="modal-title fw-semibold fs-6 mb-0" id="modalTimelineLabel">
            ประวัติการเคลื่อนไหว
          </h5>
          <span class="font-monospace text-muted small" id="timeline-sn-title">-</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
        <!-- Card รายละเอียดสินค้าของ Serial นี้ -->
        <div class="p-3 bg-body-tertiary rounded mb-4">
          <div class="row g-2 small">
            <div class="col-sm-6">
              <span class="text-muted d-block">สินค้า:</span>
              <span class="fw-medium" id="timeline-prod-name">-</span>
            </div>
            <div class="col-sm-3">
              <span class="text-muted d-block">SKU:</span>
              <span class="font-monospace" id="timeline-prod-sku">-</span>
            </div>
            <div class="col-sm-3">
              <span class="text-muted d-block">สถานะ:</span>
              <span id="timeline-status-badge">-</span>
            </div>
          </div>
        </div>

        <!-- Vertical Timeline Container -->
        <div id="timeline-container">
          <div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลดข้อมูล...</div>
        </div>
      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 4: BARCODE STICKER PRINT PREVIEW (พิมพ์สติกเกอร์บาร์โค้ด)      -->
<!-- =================================================================== -->
<div class="modal fade" id="modalPrintLabels" tabindex="-1" aria-labelledby="modalPrintLabelsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-semibold fs-6" id="modalPrintLabelsLabel">
          ตัวอย่างสติกเกอร์บาร์โค้ด
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-body-tertiary">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="text-muted small">
            ขนาดสติกเกอร์ความร้อนมาตรฐาน 50x30 มม.
          </span>
          <button type="button" class="btn btn-primary btn-sm px-3" id="btn-trigger-print">
            <i class="bi bi-printer me-1"></i> พิมพ์
          </button>
        </div>

        <!-- Print Container ที่จะถูกพิมพ์จริง -->
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <div id="labels-printable-area" class="d-flex flex-wrap gap-3 justify-content-center">
              <!-- Label Items Render ผ่าน JsBarcode -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =================================================================== -->
<!-- MODAL 5: CHANGE SERIAL STATUS (ปรับสถานะ เสียหาย/สูญหาย)             -->
<!-- =================================================================== -->
<div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatusLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <form id="form-change-status">
        <?= csrf_field() ?>
        <input type="hidden" id="status-serial-id" value="">
        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fw-semibold fs-6" id="modalChangeStatusLabel">
            ปรับสถานะ Serial Number
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="mb-3 small">
            หมายเลข: <strong class="font-monospace" id="status-sn-display">-</strong>
          </p>
          <div class="mb-3">
            <label class="form-label fw-medium" for="new-status-select">สถานะใหม่ <span class="text-danger">*</span></label>
            <select class="form-select" id="new-status-select" required>
              <option value="in_stock">พร้อมใช้งาน (In Stock)</option>
              <option value="damaged">ชำรุด / เสียหาย (ตัดสต็อกออก)</option>
              <option value="lost">สูญหาย (ตัดสต็อกออก)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium" for="status-notes-input">เหตุผล / หมายเหตุ</label>
            <textarea class="form-control" id="status-notes-input" rows="2" placeholder="เช่น แตกหักระหว่างขนย้าย, ส่งเคลม"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary btn-sm px-3">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--begin::Toast Notification-->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
  <div id="serialToast" class="toast align-items-center border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="serialToastMsg">
        เรียบร้อย
      </div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<!--end::Toast Notification-->

<style>
.sticker-label-card {
  width: 220px;
  min-height: 120px;
  padding: 8px 10px;
  background: #fff;
  border: 1px dashed #ccc;
  border-radius: 4px;
  text-align: center;
  box-sizing: border-box;
}
.sticker-title {
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}
.sticker-barcode-svg {
  width: 100% !important;
  height: 45px !important;
}
.sticker-sn {
  font-family: monospace;
  font-size: 11px;
  letter-spacing: 0.5px;
}

@media print {
  body * {
    visibility: hidden;
  }
  #labels-printable-area, #labels-printable-area * {
    visibility: visible;
  }
  #labels-printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 0;
    display: block !important;
  }
  .sticker-label-card {
    border: none !important;
    page-break-after: always;
    page-break-inside: avoid;
    width: 100% !important;
    max-width: 50mm;
    height: 30mm;
    margin: 0 auto;
    padding: 2mm;
  }
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toastEl = document.getElementById('serialToast');
  const toastMsg = document.getElementById('serialToastMsg');
  const bsToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3500 }) : null;

  function showToast(msg, isSuccess = true) {
    if (!toastEl) return;
    toastEl.className = 'toast align-items-center border-0 shadow-sm ' + (isSuccess ? 'text-bg-dark' : 'text-bg-danger');
    toastMsg.textContent = msg;
    if (bsToast) bsToast.show();
  }

  // 1. กำหนดค่า Tabulator Table แบบแถวเดียว (Single-line row) พร้อม Pagination
  const table = new Tabulator("#serials-tabulator", {
    ajaxURL: "<?= site_url('admin/serials/data') ?><?= !empty($_GET['preview']) ? '?preview=1' : '' ?>",
    layout: "fitColumns",
    pagination: true,
    paginationMode: "local",
    paginationSize: 15,
    paginationSizeSelector: [10, 15, 25, 50, 100],
    paginationCounter: function(pageSize, currentRow, currentPage, totalRows, totalPages){
      const end = Math.min(currentRow + pageSize - 1, totalRows);
      return `แสดง ${currentRow}-${end} จาก ${totalRows} รายการ &nbsp;|&nbsp; `;
    },
    placeholder: "<div class='py-4 text-center text-muted'><i class='bi bi-inbox fs-4 d-block mb-1'></i>ไม่มีข้อมูล Serial Number</div>",
    columns: [
      {
        formatter: "rowSelection", 
        titleFormatter: "rowSelection", 
        hozAlign: "center", 
        headerSort: false, 
        width: 35
      },
      {
        title: "Serial Number / IMEI", 
        field: "serial_no", 
        width: 175,
        formatter: function(cell) {
          const sn = cell.getValue();
          return `
            <div class="d-flex align-items-center justify-content-between w-100">
              <span class="font-monospace fw-medium">${sn}</span>
              <button type="button" class="btn btn-sm text-secondary p-1 btn-copy-sn" data-sn="${sn}" title="คัดลอก">
                <i class="bi bi-copy"></i>
              </button>
            </div>
          `;
        }
      },
      {
        title: "ชื่อสินค้า", 
        field: "product_name", 
        minWidth: 160,
        formatter: function(cell) {
          return `<span class="fw-medium text-body">${cell.getValue()}</span>`;
        }
      },
      {
        title: "SKU", 
        field: "product_sku", 
        width: 120,
        formatter: function(cell) {
          const val = cell.getValue();
          return val ? `<span class="font-monospace text-muted small">${val}</span>` : `<span class="text-muted small">—</span>`;
        }
      },
      {
        title: "สาขา", 
        field: "branch_name", 
        width: 120,
        formatter: function(cell) {
          return `<span class="text-body">${cell.getValue()}</span>`;
        }
      },
      {
        title: "คลัง", 
        field: "warehouse_name", 
        width: 120,
        formatter: function(cell) {
          return `<span class="text-body-secondary small">${cell.getValue()}</span>`;
        }
      },
      {
        title: "สถานะ", 
        field: "status", 
        hozAlign: "center",
        width: 105,
        formatter: function(cell) {
          const status = cell.getValue();
          switch (status) {
            case 'in_stock':
              return `<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle fw-normal">พร้อมใช้งาน</span>`;
            case 'sold':
              return `<span class="badge rounded-pill bg-secondary-subtle text-secondary fw-normal">ขายแล้ว</span>`;
            case 'used_in_repair':
              return `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle fw-normal">ใช้ในงานซ่อม</span>`;
            case 'in_transit':
              return `<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-normal">ระหว่างส่ง</span>`;
            case 'damaged':
              return `<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle fw-normal">ชำรุด</span>`;
            case 'lost':
              return `<span class="badge rounded-pill bg-body-secondary text-secondary fw-normal">สูญหาย</span>`;
            default:
              return `<span class="badge bg-light text-secondary border fw-normal">${status}</span>`;
          }
        }
      },
      {
        title: "วันที่บันทึก", 
        field: "created_at", 
        hozAlign: "center",
        width: 95,
        formatter: function(cell) {
          const val = cell.getValue();
          if (!val) return '-';
          return `<span class="small text-muted">${val.substring(0, 10)}</span>`;
        }
      },
      {
        title: "จัดการ", 
        hozAlign: "center", 
        headerSort: false, 
        width: 85,
        formatter: function(cell) {
          const d = cell.getData();
          return `
            <div class="d-flex justify-content-center gap-1">
              <button type="button" class="btn btn-sm text-secondary p-1 btn-timeline" data-id="${d.id}" title="ประวัติการเคลื่อนไหว">
                <i class="bi bi-clock-history"></i>
              </button>
              <button type="button" class="btn btn-sm text-secondary p-1 btn-single-print" data-id="${d.id}" title="พิมพ์สติกเกอร์">
                <i class="bi bi-printer"></i>
              </button>
              <button type="button" class="btn btn-sm text-secondary p-1 btn-change-status" data-id="${d.id}" data-sn="${d.serial_no}" data-status="${d.status}" title="ปรับสถานะ">
                <i class="bi bi-arrow-repeat"></i>
              </button>
            </div>
          `;
        }
      }
    ]
  });

  // Track Multi-selection count
  table.on("rowSelectionChanged", function(data, rows) {
    document.getElementById('selected-count').textContent = data.length;
  });

  // 2. Quick Live Search Bar
  document.getElementById('tabulator-search').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    table.setFilter(function(data) {
      if (!term) return true;
      return (data.serial_no && data.serial_no.toLowerCase().includes(term)) ||
             (data.product_name && data.product_name.toLowerCase().includes(term)) ||
             (data.product_sku && data.product_sku.toLowerCase().includes(term));
    });
  });

  // 3. Product Filter
  document.getElementById('filter-product').addEventListener('change', function(e) {
    const val = e.target.value;
    if (val) {
      table.setFilter("product_id", "=", Number(val));
    } else {
      table.clearFilter(true);
    }
  });

  // 4. Status Filter
  document.getElementById('filter-status').addEventListener('change', function(e) {
    const val = e.target.value;
    if (val) {
      table.setFilter("status", "=", val);
    } else {
      table.clearFilter(true);
    }
  });

  // 5. คัดลอก Serial Number
  document.querySelector('#serials-tabulator').addEventListener('click', function(e) {
    const copyBtn = e.target.closest('.btn-copy-sn');
    if (copyBtn) {
      const sn = copyBtn.dataset.sn;
      navigator.clipboard.writeText(sn).then(() => {
        showToast(`คัดลอก ${sn} เรียบร้อย`, true);
      });
    }
  });

  // 6. Action: Modal Auto Gen
  const selectProdGen = document.getElementById('gen-product-id');
  const inputPrefix = document.getElementById('gen-prefix');
  const prefixPreview = document.getElementById('prefix-preview');

  selectProdGen.addEventListener('change', function() {
    const opt = selectProdGen.options[selectProdGen.selectedIndex];
    if (opt && opt.dataset.sku) {
      const cleanSku = opt.dataset.sku.replace(/[^A-Za-z0-9]/g, '').substring(0, 8).toUpperCase();
      inputPrefix.placeholder = cleanSku;
      updatePrefixPreview();
    }
  });

  inputPrefix.addEventListener('input', updatePrefixPreview);

  function updatePrefixPreview() {
    const p = inputPrefix.value.trim() || inputPrefix.placeholder || 'SN';
    const ym = new Date().toISOString().slice(2,7).replace('-', '');
    prefixPreview.textContent = `รูปแบบ: ${p.toUpperCase()}-${ym}-0001`;
  }

  document.getElementById('form-auto-gen').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-gen');
    btn.disabled = true;
    btn.textContent = 'กำลังสร้าง...';

    const formData = new FormData(this);
    fetch("<?= site_url('admin/serials/generate') ?>", {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      btn.disabled = false;
      btn.textContent = 'ยืนยันสร้าง';
      if (res.success) {
        showToast(res.message, true);
        bootstrap.Modal.getInstance(document.getElementById('modalAutoGen')).hide();
        table.setData();
        
        if (res.serials && res.serials.length > 0) {
          if (confirm(`สร้าง SN สำเร็จจำนวน ${res.count} ชิ้น ต้องการเปิดหน้าพิมพ์สติกเกอร์บาร์โค้ดทันทีหรือไม่?`)) {
            const opt = selectProdGen.options[selectProdGen.selectedIndex];
            const prodName = opt ? opt.text : 'สินค้า';
            renderStickerLabels(res.serials.map(s => ({
              serial_no: s.serial_no,
              product_name: prodName
            })));
          }
        }
      } else {
        showToast(res.message, false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'ยืนยันสร้าง';
      showToast('เกิดข้อผิดพลาดในการสร้าง Serial Number', false);
    });
  });

  // 7. Action: Continuous Scanner Inbound
  let scannedItems = [];
  const scanInput = document.getElementById('scanner-live-input');
  const scanBox = document.getElementById('scanned-list-box');
  const scanCountBadge = document.getElementById('scan-counter-badge');
  const emptyPlaceholder = document.getElementById('scan-empty-placeholder');

  function renderScannedList() {
    scanCountBadge.textContent = `สแกนแล้ว ${scannedItems.length} ชิ้น`;
    if (scannedItems.length === 0) {
      scanBox.innerHTML = '';
      scanBox.appendChild(emptyPlaceholder);
      return;
    }

    scanBox.innerHTML = `
      <div class="d-flex flex-wrap gap-2">
        ${scannedItems.map((sn, idx) => `
          <span class="badge bg-body-secondary text-secondary-emphasis py-2 px-3 font-monospace d-inline-flex align-items-center">
            ${sn}
            <button type="button" class="btn-close ms-2 btn-remove-scanned" data-index="${idx}" style="font-size: 0.65rem;"></button>
          </span>
        `).join('')}
      </div>
    `;
  }

  function addScannedSerial() {
    const sn = scanInput.value.trim().toUpperCase();
    if (!sn) return;

    if (scannedItems.includes(sn)) {
      showToast(`หมายเลข ${sn} มีอยู่ในรายการแล้ว`, false);
      scanInput.value = '';
      return;
    }

    scannedItems.unshift(sn);
    renderScannedList();
    scanInput.value = '';
    scanInput.focus();
  }

  document.getElementById('btn-add-scanned-sn').addEventListener('click', addScannedSerial);
  scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addScannedSerial();
    }
  });

  scanBox.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-scanned');
    if (btn) {
      const idx = Number(btn.dataset.index);
      scannedItems.splice(idx, 1);
      renderScannedList();
    }
  });

  document.getElementById('btn-clear-scanned').addEventListener('click', function() {
    scannedItems = [];
    renderScannedList();
  });

  document.getElementById('form-scanner-inbound').addEventListener('submit', function(e) {
    e.preventDefault();
    if (scannedItems.length === 0) {
      showToast('กรุณาสแกนหรือระบุ Serial Number อย่างน้อย 1 รายการ', false);
      return;
    }

    const btn = document.getElementById('btn-submit-scanner');
    btn.disabled = true;
    btn.textContent = 'กำลังบันทึก...';

    const formData = new FormData(this);
    formData.append('serials', JSON.stringify(scannedItems));

    fetch("<?= site_url('admin/serials/inbound') ?>", {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      btn.disabled = false;
      btn.textContent = 'บันทึกรับเข้าคลัง';
      if (res.success) {
        showToast(res.message, true);
        scannedItems = [];
        renderScannedList();
        bootstrap.Modal.getInstance(document.getElementById('modalScanner')).hide();
        table.setData();
      } else {
        showToast(res.message, false);
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'บันทึกรับเข้าคลัง';
      showToast('เกิดข้อผิดพลาดในการนำเข้า Serial Number', false);
    });
  });

  // 8. Action: Timeline / Audit Trail
  document.querySelector('#serials-tabulator').addEventListener('click', function(e) {
    const btnTimeline = e.target.closest('.btn-timeline');
    if (btnTimeline) {
      const id = btnTimeline.dataset.id;
      const modalEl = document.getElementById('modalTimeline');
      const bsModal = new bootstrap.Modal(modalEl);
      bsModal.show();

      const timelineBox = document.getElementById('timeline-container');
      timelineBox.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลดข้อมูล...</div>';

      fetch(`<?= site_url('admin/serials/history') ?>/${id}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const s = res.serial;
            document.getElementById('timeline-sn-title').textContent = s.serial_no;
            document.getElementById('timeline-prod-name').textContent = s.product_name || '-';
            document.getElementById('timeline-prod-sku').textContent = s.product_sku || '-';
            document.getElementById('timeline-status-badge').innerHTML = `<span class="badge bg-body-secondary text-secondary-emphasis">${s.status}</span>`;

            if (res.timeline.length === 0) {
              timelineBox.innerHTML = '<div class="text-muted text-center py-3 small">ไม่มีประวัติการเคลื่อนไหว</div>';
              return;
            }

            let html = '<div class="timeline position-relative ps-4 ms-2">';
            res.timeline.forEach((t, i) => {
              html += `
                <div class="timeline-item mb-3 position-relative">
                  <div class="timeline-icon position-absolute rounded-circle d-flex align-items-center justify-content-center bg-body border" style="left: -30px; width: 26px; height: 26px;">
                    <i class="bi bi-circle text-muted" style="font-size: 0.6rem;"></i>
                  </div>
                  <div class="border rounded p-3 bg-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge bg-body-secondary text-secondary-emphasis fw-normal">${t.title}</span>
                      <small class="text-muted">${t.date}</small>
                    </div>
                    <div class="mb-1 text-body small">${t.notes || 'การเคลื่อนไหวสต็อก'}</div>
                    <div class="small text-muted">
                      ${t.branch_name} (${t.warehouse_name}) • ผู้ทำรายการ: ${t.user_name}
                    </div>
                  </div>
                </div>
              `;
            });
            html += '</div>';
            timelineBox.innerHTML = html;
          } else {
            timelineBox.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
          }
        })
        .catch(() => {
          timelineBox.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
    }

    // Single Print Button
    const btnSinglePrint = e.target.closest('.btn-single-print');
    if (btnSinglePrint) {
      const id = Number(btnSinglePrint.dataset.id);
      const row = table.getData().find(r => r.id === id);
      if (row) {
        renderStickerLabels([row]);
      }
    }

    // Change Status Button
    const btnChangeStatus = e.target.closest('.btn-change-status');
    if (btnChangeStatus) {
      const id = btnChangeStatus.dataset.id;
      const sn = btnChangeStatus.dataset.sn;
      const curStatus = btnChangeStatus.dataset.status;

      document.getElementById('status-serial-id').value = id;
      document.getElementById('status-sn-display').textContent = sn;
      document.getElementById('new-status-select').value = curStatus;
      document.getElementById('status-notes-input').value = '';

      new bootstrap.Modal(document.getElementById('modalChangeStatus')).show();
    }
  });

  // Change Status Form Submit
  document.getElementById('form-change-status').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('status-serial-id').value;
    const status = document.getElementById('new-status-select').value;
    const notes = document.getElementById('status-notes-input').value;

    fetch(`<?= site_url('admin/serials/status') ?>/${id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      body: new URLSearchParams({ status, notes })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, true);
        bootstrap.Modal.getInstance(document.getElementById('modalChangeStatus')).hide();
        table.setData();
      } else {
        showToast(res.message, false);
      }
    })
    .catch(() => showToast('เกิดข้อผิดพลาดในการปรับสถานะ', false));
  });

  // 9. Multi-Print Labels
  document.getElementById('btn-print-selected').addEventListener('click', function() {
    const selectedRows = table.getSelectedData();
    if (selectedRows.length === 0) {
      showToast('กรุณาเลือกรายการที่ต้องการพิมพ์', false);
      return;
    }
    renderStickerLabels(selectedRows);
  });

  function renderStickerLabels(items) {
    const area = document.getElementById('labels-printable-area');
    area.innerHTML = '';

    items.forEach((item, idx) => {
      const card = document.createElement('div');
      card.className = 'sticker-label-card shadow-sm';
      card.innerHTML = `
        <div class="sticker-title">${item.product_name || 'สินค้า'}</div>
        <svg id="barcode-svg-${idx}" class="sticker-barcode-svg"></svg>
        <div class="sticker-sn">${item.serial_no}</div>
      `;
      area.appendChild(card);

      try {
        JsBarcode(`#barcode-svg-${idx}`, item.serial_no, {
          format: "CODE128",
          width: 1.5,
          height: 38,
          displayValue: false,
          margin: 0
        });
      } catch (err) {
        console.error("Barcode generation error:", err);
      }
    });

    const modal = new bootstrap.Modal(document.getElementById('modalPrintLabels'));
    modal.show();
  }

  document.getElementById('btn-trigger-print').addEventListener('click', function() {
    window.print();
  });
});
</script>
<?= $this->endSection() ?>
