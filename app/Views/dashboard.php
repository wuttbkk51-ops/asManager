<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Dashboard Header Toolbar-->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-2 border-bottom">
  <div>
    <h4 class="fw-bold mb-1">
      <i class="bi bi-speedometer2 text-primary me-2"></i>แดชบอร์ดสรุปภาพรวม (Customizable Dashboard)
    </h4>
    <p class="text-muted small mb-0">
      บทบาทของคุณ: <span class="badge text-bg-primary text-uppercase"><?= esc($userRole ?? 'owner') ?></span>
      | สิทธิ์การมองเห็นถูกควบคุมตามนโยบายความปลอดภัยของระบบ
    </p>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2">
    <!-- ปุ่มสลับโหมดปรับแต่ง -->
    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-toggle-edit-mode">
      <i class="bi bi-gear-fill me-1"></i> ปรับแต่งแดชบอร์ด (Customize)
    </button>
    
    <!-- ปุ่มควบคุมในโหมดปรับแต่ง (ซ่อนไว้เริ่มต้น) -->
    <div id="edit-mode-controls" class="d-none gap-2">
      <button type="button" class="btn btn-success btn-sm" data-bs-toggle="offcanvas" data-bs-target="#widgetCatalogOffcanvas">
        <i class="bi bi-plus-circle-fill me-1"></i> เพิ่มโมดูล (+ Add Widget)
      </button>
      <button type="button" class="btn btn-primary btn-sm" id="btn-save-layout">
        <i class="bi bi-floppy-fill me-1"></i> บันทึกเลย์เอาต์
      </button>
      <button type="button" class="btn btn-outline-danger btn-sm" id="btn-reset-layout">
        <i class="bi bi-arrow-counterclockwise me-1"></i> คืนค่าเริ่มต้น
      </button>
    </div>
  </div>
</div>
<!--end::Dashboard Header Toolbar-->

<!--begin::Status Alert (แสดงเมื่ออยู่ในโหมดปรับแต่ง)-->
<div id="edit-mode-banner" class="alert alert-info alert-dismissible fade show d-none shadow-sm py-2 px-3 mb-3" role="alert">
  <div class="d-flex align-items-center">
    <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
    <div class="small">
      <strong>โหมดออกแบบแดชบอร์ด:</strong> ท่านสามารถจับลาก (Drag & Drop) ที่ปุ่มจับย้ายเพื่อสลับตำแหน่ง, ปรับขนาดความกว้าง (1/4, 1/3, 1/2, เต็ม) หรือลบการ์ดที่ไม่ต้องการออก เมื่อจัดเรียงเสร็จแล้วให้กด <strong>"บันทึกเลย์เอาต์"</strong>
    </div>
  </div>
</div>
<!--end::Status Alert-->

<!--begin::Grid Container แสดงโมดูลและวิดเจ็ตทั้งหมด-->
<div class="row g-3" id="dashboard-grid">
  <?php if (!empty($renderedWidgets)): ?>
    <?php foreach ($renderedWidgets as $widget): ?>
      <div class="widget-col <?= esc($widget['col']) ?>" 
           data-widget-id="<?= esc($widget['widget_id']) ?>" 
           data-col="<?= esc($widget['col']) ?>">
        <div class="widget-wrapper h-100 position-relative">
          
          <!-- แถบควบคุมวิดเจ็ตในโหมดปรับแต่ง -->
          <div class="widget-controls d-none mb-2 p-1 bg-body-tertiary border rounded d-flex justify-content-between align-items-center">
            <span class="badge bg-secondary drag-handle cursor-move py-1 px-2" draggable="true" title="คลิกค้างแล้วลากเพื่อย้ายตำแหน่ง">
              <i class="bi bi-arrows-move me-1"></i> ย้าย
            </span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-3 col-sm-6" title="ขนาด 1/4 (3 คอลัมน์)">1/4</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-4 col-sm-6" title="ขนาด 1/3 (4 คอลัมน์)">1/3</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-6" title="ขนาด 1/2 (6 คอลัมน์)">1/2</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-8" title="ขนาด 2/3 (8 คอลัมน์)">2/3</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-12" title="เต็มความกว้าง (12 คอลัมน์)">เต็ม</button>
              <button type="button" class="btn btn-outline-danger btn-remove px-2 py-0" title="นำโมดูลนี้ออกจากแดชบอร์ด">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- เนื้อหาของวิดเจ็ต -->
          <div class="widget-content h-100">
            <?= $widget['html'] ?>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12 py-5 text-center text-muted">
      <i class="bi bi-grid fs-1 d-block mb-2 text-secondary"></i>
      <h5>ยังไม่มีโมดูลแสดงบนแดชบอร์ด</h5>
      <p class="small">กดปุ่ม "ปรับแต่งแดชบอร์ด" ด้านบนเพื่อเลือกเพิ่มโมดูลตามสิทธิ์ที่ท่านต้องการ</p>
    </div>
  <?php endif; ?>
</div>
<!--end::Grid Container-->

<!--begin::Offcanvas Drawer เลือกเพิ่มวิดเจ็ต (Permission-Enforced Catalog)-->
<div class="offcanvas offcanvas-end shadow" tabindex="-1" id="widgetCatalogOffcanvas" aria-labelledby="widgetCatalogLabel" style="width: 420px;">
  <div class="offcanvas-header border-bottom py-3 bg-body-tertiary">
    <h5 class="offcanvas-title fw-bold" id="widgetCatalogLabel">
      <i class="bi bi-boxes text-primary me-2"></i>คลังโมดูลแดชบอร์ด (Widget Catalog)
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <div class="offcanvas-body p-3">
    <div class="alert alert-secondary small py-2 px-3 mb-3 border-0 bg-secondary-subtle">
      <i class="bi bi-shield-check me-1 text-success"></i> แสดงเฉพาะโมดูลที่ท่านได้รับสิทธิ์การใช้งานเท่านั้น
    </div>

    <!-- รายการ Widget ตามสิทธิ์ -->
    <div class="d-flex flex-column gap-3" id="available-widget-list">
      <?php if (!empty($availableWidgets)): ?>
        <?php foreach ($availableWidgets as $wId => $widgetObj): ?>
          <div class="card border shadow-sm p-3 widget-catalog-card" data-widget-id="<?= esc($wId) ?>" data-default-col="<?= esc($widgetObj->getDefaultCol()) ?>">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="fs-4 text-primary"><i class="<?= esc($widgetObj->getIcon()) ?>"></i></span>
                <div>
                  <h6 class="fw-bold mb-0 text-dark"><?= esc($widgetObj->getTitle()) ?></h6>
                  <span class="badge bg-light text-secondary border font-monospace small"><?= esc($widgetObj->getCategory()) ?></span>
                </div>
              </div>
            </div>
            <p class="small text-muted mb-3"><?= esc($widgetObj->getDescription()) ?></p>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <small class="text-muted"><i class="bi bi-key-fill me-1"></i>สิทธิ์: <code><?= esc($widgetObj->getRequiredPermission()) ?></code></small>
              <button type="button" class="btn btn-sm btn-outline-primary btn-add-widget" data-widget-id="<?= esc($wId) ?>">
                <i class="bi bi-plus-lg me-1"></i> เพิ่มลงหน้าจอ
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-muted py-4">ไม่พบโมดูลที่ท่านมีสิทธิ์ใช้งานเพิ่มเติม</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<!--end::Offcanvas Drawer-->

<!--begin::Toast Notification แจ้งผลการบันทึก-->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
  <div id="layoutToast" class="toast align-items-center text-bg-primary border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage">
        บันทึกข้อมูลเรียบร้อยแล้ว
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<!--end::Toast Notification-->

<!--begin::Native JavaScript Drag & Drop and Controls-->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const grid = document.getElementById('dashboard-grid');
  const btnToggleEdit = document.getElementById('btn-toggle-edit-mode');
  const editControls = document.getElementById('edit-mode-controls');
  const editBanner = document.getElementById('edit-mode-banner');
  const btnSave = document.getElementById('btn-save-layout');
  const btnReset = document.getElementById('btn-reset-layout');
  const toastEl = document.getElementById('layoutToast');
  const toastMsg = document.getElementById('toastMessage');
  const bsToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3000 }) : null;

  let isEditMode = false;
  let draggedCol = null;

  function showToast(message, isSuccess = true) {
    if (!toastEl) return;
    toastEl.className = 'toast align-items-center border-0 shadow ' + (isSuccess ? 'text-bg-success' : 'text-bg-danger');
    toastMsg.textContent = message;
    if (bsToast) bsToast.show();
  }

  // 1. สลับโหมดปรับแต่งแดชบอร์ด
  btnToggleEdit.addEventListener('click', function() {
    isEditMode = !isEditMode;
    if (isEditMode) {
      btnToggleEdit.classList.remove('btn-outline-primary');
      btnToggleEdit.classList.add('btn-secondary');
      btnToggleEdit.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> ปิดโหมดปรับแต่ง';
      editControls.classList.remove('d-none');
      editControls.classList.add('d-flex');
      editBanner.classList.remove('d-none');
      document.querySelectorAll('.widget-controls').forEach(el => el.classList.remove('d-none'));
      enableDragAndDrop();
    } else {
      btnToggleEdit.classList.remove('btn-secondary');
      btnToggleEdit.classList.add('btn-outline-primary');
      btnToggleEdit.innerHTML = '<i class="bi bi-gear-fill me-1"></i> ปรับแต่งแดชบอร์ด (Customize)';
      editControls.classList.add('d-none');
      editControls.classList.remove('d-flex');
      editBanner.classList.add('d-none');
      document.querySelectorAll('.widget-controls').forEach(el => el.classList.add('d-none'));
      disableDragAndDrop();
    }
  });

  // 2. Native HTML5 Drag and Drop API Setup
  function enableDragAndDrop() {
    const items = grid.querySelectorAll('.widget-col');
    items.forEach(item => {
      const handle = item.querySelector('.drag-handle');
      if (handle) {
        handle.setAttribute('draggable', 'true');
        
        handle.onmousedown = () => {
          item.setAttribute('draggable', 'true');
        };
        handle.onmouseup = () => {
          item.removeAttribute('draggable');
        };

        item.ondragstart = (e) => {
          draggedCol = item;
          item.classList.add('opacity-50', 'border', 'border-primary', 'border-2', 'rounded');
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', item.dataset.widgetId);
        };

        item.ondragend = () => {
          item.classList.remove('opacity-50', 'border', 'border-primary', 'border-2', 'rounded');
          item.removeAttribute('draggable');
          draggedCol = null;
        };

        item.ondragover = (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          if (draggedCol && draggedCol !== item) {
            const rect = item.getBoundingClientRect();
            const midX = rect.left + rect.width / 2;
            const midY = rect.top + rect.height / 2;
            const isAfter = (e.clientY > midY) || (e.clientX > midX && e.clientY >= rect.top && e.clientY <= rect.bottom);
            
            if (isAfter) {
              item.after(draggedCol);
            } else {
              item.before(draggedCol);
            }
          }
        };
      }
    });
  }

  function disableDragAndDrop() {
    const items = grid.querySelectorAll('.widget-col');
    items.forEach(item => {
      item.removeAttribute('draggable');
      item.ondragstart = null;
      item.ondragend = null;
      item.ondragover = null;
    });
  }

  // 3. ปรับขนาดคอลัมน์ (Resize Buttons)
  grid.addEventListener('click', function(e) {
    const resizeBtn = e.target.closest('.btn-resize');
    if (resizeBtn) {
      const colEl = resizeBtn.closest('.widget-col');
      const newSize = resizeBtn.dataset.size;
      
      // เอา class col-* เดิมออกทั้งหมด แล้วใส่ class ใหม่
      colEl.className = colEl.className.replace(/col(-[a-z]+)*-[0-9]+/g, '').trim();
      colEl.className = (colEl.className + ' ' + newSize).trim();
      colEl.dataset.col = newSize;

      // ไฮไลต์ปุ่มที่เลือก
      resizeBtn.parentElement.querySelectorAll('.btn-resize').forEach(b => b.classList.remove('active', 'btn-secondary'));
      resizeBtn.classList.add('active');
    }

    // ลบการ์ด (Remove Widget)
    const removeBtn = e.target.closest('.btn-remove');
    if (removeBtn) {
      const colEl = removeBtn.closest('.widget-col');
      if (confirm('คุณต้องการนำโมดูลนี้ออกจากหน้าจอแดชบอร์ดใช่หรือไม่?')) {
        colEl.remove();
        showToast('นำโมดูลออกชั่วคราวแล้ว (กรุณากดบันทึกเลย์เอาต์เพื่อยืนยัน)', true);
      }
    }
  });

  // 4. เพิ่ม Widget จาก Drawer Offcanvas
  document.querySelectorAll('.btn-add-widget').forEach(btn => {
    btn.addEventListener('click', function() {
      const widgetId = this.dataset.widgetId;
      const existing = grid.querySelector(`[data-widget-id="${widgetId}"]`);
      if (existing) {
        alert('โมดูลนี้มีอยู่บนแดชบอร์ดแล้ว');
        return;
      }

      // โหลด HTML หรือเพิ่ม placeholder ลง grid แล้ว reload
      const card = this.closest('.widget-catalog-card');
      const defaultCol = card.dataset.defaultCol || 'col-lg-3 col-sm-6';
      
      // สร้าง element จำลองใน DOM
      const newCol = document.createElement('div');
      newCol.className = `widget-col ${defaultCol}`;
      newCol.dataset.widgetId = widgetId;
      newCol.dataset.col = defaultCol;
      newCol.innerHTML = `
        <div class="widget-wrapper h-100 position-relative">
          <div class="widget-controls mb-2 p-1 bg-body-tertiary border rounded d-flex justify-content-between align-items-center">
            <span class="badge bg-secondary drag-handle cursor-move py-1 px-2" draggable="true">
              <i class="bi bi-arrows-move me-1"></i> ย้าย
            </span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-3 col-sm-6">1/4</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-4 col-sm-6">1/3</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-6">1/2</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-8">2/3</button>
              <button type="button" class="btn btn-outline-secondary btn-resize px-2 py-0" data-size="col-lg-12">เต็ม</button>
              <button type="button" class="btn btn-outline-danger btn-remove px-2 py-0"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <div class="card card-outline card-primary shadow-sm p-4 text-center">
            <div class="spinner-border spinner-border-sm text-primary mb-2 mx-auto" role="status"></div>
            <p class="small text-muted mb-0">โมดูลใหม่ถูกเพิ่มแล้ว (กำลังรอการบันทึก)</p>
          </div>
        </div>
      `;
      grid.appendChild(newCol);
      enableDragAndDrop();
      showToast('เพิ่มโมดูลแล้ว กรุณากดปุ่ม "บันทึกเลย์เอาต์"', true);

      // ปิด Offcanvas
      const offcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('widgetCatalogOffcanvas'));
      if (offcanvasInstance) offcanvasInstance.hide();
    });
  });

  // 5. บันทึก Layout ผ่าน AJAX
  btnSave.addEventListener('click', function() {
    const layout = [];
    grid.querySelectorAll('.widget-col').forEach(col => {
      const wId = col.dataset.widgetId;
      const cSize = col.dataset.col;
      if (wId) {
        layout.push({ widget_id: wId, col: cSize });
      }
    });

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

    fetch('<?= site_url('dashboard/save-layout') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      },
      body: JSON.stringify({ layout: layout })
    })
    .then(res => res.json())
    .then(data => {
      btnSave.disabled = false;
      btnSave.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> บันทึกเลย์เอาต์';
      if (data.success) {
        showToast(data.message || 'บันทึกการจัดวางสำเร็จ', true);
        setTimeout(() => window.location.reload(), 800);
      } else {
        showToast(data.message || 'เกิดข้อผิดพลาดในการบันทึก', false);
      }
    })
    .catch(err => {
      btnSave.disabled = false;
      btnSave.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> บันทึกเลย์เอาต์';
      showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย', false);
    });
  });

  // 6. รีเซ็ตกลับเป็น Default Layout
  btnReset.addEventListener('click', function() {
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการคืนค่าการจัดวางแดชบอร์ดกลับเป็นค่าเริ่มต้นตามสิทธิ์ของบทบาทคุณ?')) {
      return;
    }

    btnReset.disabled = true;
    btnReset.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังคืนค่า...';

    fetch('<?= site_url('dashboard/reset-layout') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
      }
    })
    .then(res => res.json())
    .then(data => {
      btnReset.disabled = false;
      btnReset.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> คืนค่าเริ่มต้น';
      if (data.success) {
        showToast(data.message || 'คืนค่าเริ่มต้นสำเร็จ', true);
        setTimeout(() => window.location.reload(), 600);
      } else {
        showToast(data.message || 'เกิดข้อผิดพลาด', false);
      }
    })
    .catch(err => {
      btnReset.disabled = false;
      btnReset.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> คืนค่าเริ่มต้น';
      showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', false);
    });
  });
});
</script>

<style>
.cursor-move {
  cursor: grab !important;
}
.cursor-move:active {
  cursor: grabbing !important;
}
.widget-catalog-card {
  transition: all 0.2s ease-in-out;
}
.widget-catalog-card:hover {
  border-color: var(--bs-primary) !important;
  transform: translateY(-2px);
}
</style>
<?= $this->endSection() ?>
