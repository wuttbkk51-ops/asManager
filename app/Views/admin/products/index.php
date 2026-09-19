<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Page Header (Minimal & Airy)-->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-semibold mb-0"><?= lang('Products.title') ?></h4>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-export-csv">
      <i class="bi bi-download me-1"></i> <?= lang('Products.btn_export') ?>
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="btn-open-create-drawer">
      <i class="bi bi-plus-lg me-1"></i> <?= lang('Products.btn_new') ?>
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
            placeholder="<?= lang('Products.search_placeholder') ?>" 
            autocomplete="off"
          />
        </div>
      </div>

      <!-- Category Filter -->
      <div class="col-md-4">
        <select class="form-select form-select-sm" id="filter-category">
          <option value="">-- <?= lang('Products.col_category') ?>: <?= lang('App.all') ?> --</option>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= esc($cat['name']) ?>"><?= esc($cat['name']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <!-- Type Filter -->
      <div class="col-md-3">
        <select class="form-select form-select-sm" id="filter-type">
          <option value="">-- <?= lang('Products.col_type') ?>: <?= lang('App.all') ?> --</option>
          <option value="normal"><?= lang('Products.type_product') ?></option>
          <option value="serial"><?= lang('Products.col_serial') ?></option>
          <option value="service"><?= lang('Products.type_service') ?></option>
        </select>
      </div>
    </div>
  </div>
</div>
<!--end::Card Filter-->

<!--begin::Card Tabulator Table Container-->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-0">
    <div id="products-tabulator" class="tabulator-bootstrap-5"></div>
  </div>
</div>
<!--end::Card Tabulator Table-->

<!-- =================================================================== -->
<!-- REUSABLE OFFCANVAS DRAWER: เพิ่ม / แก้ไขสินค้า                     -->
<!-- =================================================================== -->
<div class="offcanvas offcanvas-end border-start shadow-sm" tabindex="-1" id="productDrawer" aria-labelledby="productDrawerLabel" style="width: 540px;">
  <div class="offcanvas-header border-bottom py-3">
    <h5 class="offcanvas-title fw-semibold fs-6" id="productDrawerLabel">
      <span id="drawer-title"><?= lang('Products.drawer_title_new') ?></span>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <div class="offcanvas-body p-4">
    <!-- Include Reusable Form Component -->
    <?= $this->include('admin/products/_form') ?>
  </div>
</div>
<!--end::Reusable Offcanvas Drawer-->

<!--begin::Toast Notification-->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
  <div id="crudToast" class="toast align-items-center border-0 shadow-sm" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="crudToastMsg">สำเร็จ</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<!--end::Toast Notification-->

<!-- Tabulator & Luxon Dependencies -->
<link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
<script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>

<style>
/* Clean & Flat Tabulator Table Style */
.tabulator-bootstrap-5 {
  border: none !important;
  font-size: 0.875rem;
}
.tabulator-bootstrap-5 .tabulator-header {
  border-bottom: 1px solid var(--bs-border-color) !important;
  background-color: var(--bs-tertiary-bg) !important;
  color: var(--bs-body-color) !important;
}
.tabulator-bootstrap-5 .tabulator-col {
  background-color: transparent !important;
  border-right: none !important;
  padding: 8px 12px;
}
.tabulator-bootstrap-5 .tabulator-col-content {
  padding: 0 !important;
}
.tabulator-bootstrap-5 .tabulator-col-title {
  font-weight: 600;
  font-size: 0.8rem;
  color: var(--bs-secondary-color);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.tabulator-bootstrap-5 .tabulator-row {
  border-bottom: 1px solid var(--bs-border-color-translucent) !important;
  background-color: transparent !important;
}
.tabulator-bootstrap-5 .tabulator-row:hover {
  background-color: var(--bs-secondary-bg-subtle) !important;
}
.tabulator-bootstrap-5 .tabulator-cell {
  padding: 10px 12px;
  border-right: none !important;
  display: inline-flex;
  align-items: center;
}
.tabulator-footer {
  border-top: 1px solid var(--bs-border-color) !important;
  background-color: var(--bs-body-bg) !important;
  padding: 10px 16px !important;
}
.tabulator-page-counter {
  color: var(--bs-secondary-color);
  font-size: 0.825rem;
}
.tabulator-page-size {
  border-radius: 4px;
  border: 1px solid var(--bs-border-color);
  padding: 3px 6px;
  background: var(--bs-body-bg);
  color: var(--bs-body-color);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const i18n = {
    colSku: "<?= lang('Products.col_sku') ?>",
    colBarcode: "<?= lang('Products.col_barcode') ?>",
    colName: "<?= lang('Products.col_name') ?>",
    colCategory: "<?= lang('Products.col_category') ?>",
    colType: "<?= lang('Products.col_type') ?>",
    colPrice: "<?= lang('Products.col_price') ?>",
    colCost: "<?= lang('Products.col_cost') ?>",
    colStock: "<?= lang('Products.col_stock') ?>",
    colActions: "<?= lang('Products.col_actions') ?>",
    outOfStock: "<?= service('request')->getLocale() === 'th' ? 'หมด' : 'Out of stock' ?>",
    service: "<?= lang('Products.type_service') ?>",
    product: "<?= lang('Products.type_product') ?>",
    showing: "<?= service('request')->getLocale() === 'th' ? 'แสดง' : 'Showing' ?>",
    from: "<?= service('request')->getLocale() === 'th' ? 'จาก' : 'of' ?>",
    items: "<?= service('request')->getLocale() === 'th' ? 'รายการ' : 'items' ?>",
    noData: "<?= lang('App.no_data_found') ?>",
    titleNew: "<?= lang('Products.drawer_title_new') ?>",
    titleEdit: "<?= lang('Products.drawer_title_edit') ?>",
    save: "<?= lang('Products.btn_save') ?>",
    saving: "<?= service('request')->getLocale() === 'th' ? 'กำลังบันทึก...' : 'Saving...' ?>",
    confirmDelete: "<?= lang('Products.confirm_delete') ?>"
  };

  const drawerEl = document.getElementById('productDrawer');
  const bsDrawer = drawerEl ? new bootstrap.Offcanvas(drawerEl) : null;
  const drawerTitle = document.getElementById('drawer-title');
  const form = document.getElementById('product-form');
  const btnSubmit = document.getElementById('btn-submit-form');
  const initialStockGroup = document.getElementById('initial-stock-group');
  const isActiveGroup = document.getElementById('is-active-group');

  const toastEl = document.getElementById('crudToast');
  const toastMsg = document.getElementById('crudToastMsg');
  const bsToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3500 }) : null;

  function showToast(msg, isSuccess = true) {
    if (!toastEl) return;
    toastEl.className = 'toast align-items-center border-0 shadow-sm ' + (isSuccess ? 'text-bg-dark' : 'text-bg-danger');
    toastMsg.textContent = msg;
    if (bsToast) bsToast.show();
  }

  // 1. กำหนดค่า Tabulator Table แบบแถวเดียว (Single-line row) พร้อม Pagination
  const table = new Tabulator("#products-tabulator", {
    ajaxURL: "<?= site_url('admin/products/data') ?><?= !empty($_GET['preview']) ? '?preview=1' : '' ?>",
    layout: "fitColumns",
    pagination: true,
    paginationMode: "local",
    paginationSize: 15,
    paginationSizeSelector: [10, 15, 25, 50, 100],
    paginationCounter: function(pageSize, currentRow, currentPage, totalRows, totalPages){
      const end = Math.min(currentRow + pageSize - 1, totalRows);
      return `${i18n.showing} ${currentRow}-${end} ${i18n.from} ${totalRows} ${i18n.items} &nbsp;|&nbsp; `;
    },
    placeholder: `<div class='py-4 text-center text-muted'><i class='bi bi-inbox fs-4 d-block mb-1'></i>${i18n.noData}</div>`,
    columns: [
      {
        title: i18n.colSku, 
        field: "sku", 
        width: 140,
        formatter: function(cell) {
          return `<span class="font-monospace fw-medium">${cell.getValue()}</span>`;
        }
      },
      {
        title: i18n.colBarcode, 
        field: "barcode", 
        width: 130,
        formatter: function(cell) {
          const v = cell.getValue();
          return v ? `<span class="font-monospace text-muted small">${v}</span>` : `<span class="text-muted small">—</span>`;
        }
      },
      {
        title: i18n.colName, 
        field: "name", 
        minWidth: 200,
        formatter: function(cell) {
          return `<span class="fw-medium text-body">${cell.getValue()}</span>`;
        }
      },
      {
        title: i18n.colCategory, 
        field: "category_name", 
        width: 130,
        formatter: function(cell) {
          const v = cell.getValue();
          return v ? `<span class="text-body-secondary small">${v}</span>` : `<span class="text-muted small">—</span>`;
        }
      },
      {
        title: i18n.colType, 
        field: "has_serial", 
        hozAlign: "center",
        width: 90,
        formatter: function(cell) {
          const d = cell.getData();
          if (d.has_serial) {
            return `<span class="badge bg-body-secondary text-secondary-emphasis fw-normal" style="font-size: 0.72rem;">SN</span>`;
          }
          if (!d.track_stock) {
            return `<span class="badge bg-body-secondary text-secondary-emphasis fw-normal" style="font-size: 0.72rem;">${i18n.service}</span>`;
          }
          return `<span class="text-muted small">${i18n.product}</span>`;
        }
      },
      {
        title: i18n.colPrice, 
        field: "sell_price", 
        hozAlign: "right",
        width: 110,
        formatter: function(cell) {
          return `<span class="fw-medium text-body">฿${Number(cell.getValue()).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
        }
      },
      {
        title: i18n.colCost, 
        field: "cost_price", 
        hozAlign: "right",
        width: 100,
        formatter: function(cell) {
          const val = Number(cell.getValue());
          return `<span class="text-muted small">฿${val.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
        }
      },
      {
        title: i18n.colStock, 
        field: "stock_qty", 
        hozAlign: "right",
        width: 110,
        formatter: function(cell) {
          const data = cell.getData();
          if (!data.track_stock) {
            return `<span class="text-muted small">—</span>`;
          }
          const qty = Number(cell.getValue());
          if (qty <= 0) {
            return `<span class="text-danger fw-medium small">${i18n.outOfStock}</span>`;
          }
          return `<span class="text-body">${qty.toLocaleString()} <span class="text-muted small">${data.unit || 'ชิ้น'}</span></span>`;
        }
      },
      {
        title: i18n.colActions, 
        hozAlign: "center", 
        headerSort: false, 
        width: 90,
        formatter: function(cell) {
          const data = cell.getData();
          let serialBtn = data.has_serial ? `
            <a href="<?= site_url('admin/serials') ?>?product_id=${data.id}" class="btn btn-sm text-secondary p-1" title="Serial Number">
              <i class="bi bi-qr-code"></i>
            </a>
          ` : '';
          return `
            <div class="d-flex justify-content-center gap-1">
              ${serialBtn}
              <button type="button" class="btn btn-sm text-secondary p-1 btn-edit" data-id="${data.id}" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm text-secondary p-1 btn-delete" data-id="${data.id}" data-name="${data.name}" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          `;
        }
      }
    ]
  });

  // 2. Quick Live Search Bar
  document.getElementById('tabulator-search').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    table.setFilter(function(data) {
      if (!term) return true;
      return (data.name && data.name.toLowerCase().includes(term)) ||
             (data.sku && data.sku.toLowerCase().includes(term)) ||
             (data.barcode && data.barcode.toLowerCase().includes(term));
    });
  });

  // 3. Category Filter
  document.getElementById('filter-category').addEventListener('change', function(e) {
    const cat = e.target.value;
    if (cat) {
      table.setFilter("category_name", "=", cat);
    } else {
      table.clearFilter(true);
    }
  });

  // 4. Type Filter
  document.getElementById('filter-type').addEventListener('change', function(e) {
    const type = e.target.value;
    if (type === 'serial') {
      table.setFilter("has_serial", "=", 1);
    } else if (type === 'service') {
      table.setFilter("track_stock", "=", 0);
    } else if (type === 'normal') {
      table.setFilter([
        {field: "has_serial", type: "=", value: 0},
        {field: "track_stock", type: "=", value: 1}
      ]);
    } else {
      table.clearFilter(true);
    }
  });

  // 5. Export CSV Button
  document.getElementById('btn-export-csv').addEventListener('click', function() {
    table.download("csv", "products_export_" + new Date().toISOString().slice(0,10) + ".csv");
  });

  // 6. ปุ่มเปิด Drawer สำหรับ "เพิ่มสินค้าใหม่" (Create Mode)
  document.getElementById('btn-open-create-drawer').addEventListener('click', function() {
    form.reset();
    document.getElementById('prod-id').value = '';
    drawerTitle.textContent = i18n.titleNew;
    btnSubmit.textContent = i18n.save;
    if (initialStockGroup) initialStockGroup.style.display = 'block';
    if (isActiveGroup) isActiveGroup.style.display = 'none';

    // ปิด Accordion ตัวเลือกขั้นสูงให้เป็นค่าเริ่มต้น
    const collapseEl = document.getElementById('collapseAdvanced');
    if (collapseEl && collapseEl.classList.contains('show')) {
      new bootstrap.Collapse(collapseEl, { toggle: true });
    }

    if (bsDrawer) bsDrawer.show();
  });

  // Deep Link: เปิด Drawer อัตโนมัติเมื่อมี #new ใน URL
  if (window.location.hash === '#new') {
    setTimeout(() => {
      document.getElementById('btn-open-create-drawer').click();
    }, 400);
  }

  // 7. ปุ่มสร้าง SKU อัตโนมัติในฟอร์ม
  document.getElementById('btn-gen-sku').addEventListener('click', function() {
    const rand = Math.floor(1000 + Math.random() * 9000);
    const dateStr = new Date().toISOString().slice(2,7).replace('-', '');
    document.getElementById('prod-sku').value = 'PRD-' + dateStr + '-' + rand;
  });

  // 8. คลิกปุ่ม Edit หรือ Delete บนตาราง Tabulator
  document.querySelector('#products-tabulator').addEventListener('click', function(e) {
    // Edit Action
    const editBtn = e.target.closest('.btn-edit');
    if (editBtn) {
      const id = editBtn.dataset.id;
      fetch(`<?= site_url('admin/products/get') ?>/${id}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const d = res.data;
            document.getElementById('prod-id').value = d.id;
            document.getElementById('prod-name').value = d.name || '';
            document.getElementById('prod-sell-price').value = d.sell_price || 0;
            document.getElementById('prod-cost-price').value = d.cost_price || 0;
            document.getElementById('prod-category').value = d.category_id || '';
            document.getElementById('prod-sku').value = d.sku || '';
            document.getElementById('prod-barcode').value = d.barcode || '';
            document.getElementById('prod-unit').value = d.unit || 'ชิ้น';
            document.getElementById('prod-costing').value = d.costing_method || '';
            document.getElementById('prod-has-serial').checked = Number(d.has_serial) === 1;
            document.getElementById('prod-track-stock').checked = Number(d.track_stock) === 1;
            document.getElementById('prod-is-active').checked = Number(d.is_active) === 1;

            drawerTitle.textContent = `${i18n.titleEdit} #${d.id}`;
            btnSubmit.textContent = i18n.save;
            if (initialStockGroup) initialStockGroup.style.display = 'none';
            if (isActiveGroup) isActiveGroup.style.display = 'block';

            if (bsDrawer) bsDrawer.show();
          } else {
            showToast(i18n.noData, false);
          }
        })
        .catch(() => showToast('Error loading data', false));
    }

    // Delete Action
    const delBtn = e.target.closest('.btn-delete');
    if (delBtn) {
      const id = delBtn.dataset.id;
      const name = delBtn.dataset.name;
      if (confirm(`${i18n.confirmDelete} "${name}"?`)) {
        fetch(`<?= site_url('admin/products/delete') ?>/${id}`, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
          }
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            showToast(res.message, true);
            table.setData(); // reload tabulator
          } else {
            showToast(res.message, false);
          }
        })
        .catch(() => showToast('Error deleting item', false));
      }
    }
  });

  // 9. Submit Reusable Form (Create & Update)
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    const id = document.getElementById('prod-id').value;
    const url = id ? `<?= site_url('admin/products/update') ?>/${id}` : `<?= site_url('admin/products/store') ?>`;
    const formData = new FormData(form);

    btnSubmit.disabled = true;
    btnSubmit.textContent = i18n.saving;

    fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData
    })
    .then(res => res.json())
    .then(res => {
      btnSubmit.disabled = false;
      btnSubmit.textContent = i18n.save;

      if (res.success) {
        showToast(res.message, true);
        if (bsDrawer) bsDrawer.hide();
        form.reset();
        form.classList.remove('was-validated');
        table.setData(); // Refresh Tabulator Table
      } else {
        showToast(res.message || 'Error saving data', false);
      }
    })
    .catch(() => {
      btnSubmit.disabled = false;
      btnSubmit.textContent = i18n.save;
      showToast('Network error', false);
    });
  });
});
</script>
<?= $this->endSection() ?>
