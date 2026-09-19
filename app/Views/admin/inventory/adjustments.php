<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row">
  <div class="col-12">
    <!--begin::Card Stock Adjustments-->
    <div class="card card-primary card-outline shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div>
          <h5 class="card-title fw-bold mb-0">
            <i class="bi bi-clipboard-check me-2 text-primary"></i>รายการตรวจนับและปรับปรุงสต็อก (Stock Adjustments)
          </h5>
          <small class="text-muted">บันทึกตรวจนับสินค้าจริงสิ้นงวด, สินค้าชำรุด, สูญหาย หรือพบของเกิน</small>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAdjustmentModal">
          <i class="bi bi-plus-circle me-1"></i> สร้างใบตรวจนับสต็อกใหม่
        </button>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>เลขที่เอกสาร</th>
                <th>ประเภท</th>
                <th>สาขา / คลัง</th>
                <th>มูลค่าสต็อกขาด</th>
                <th>มูลค่าสต็อกเกิน</th>
                <th>สถานะ</th>
                <th>วันที่สร้าง</th>
                <th class="text-end">การกระทำ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($adjustments)): ?>
                <?php foreach ($adjustments as $adj): ?>
                  <tr>
                    <td class="fw-bold">
                      <code><?= esc($adj['adjustment_no']) ?></code>
                    </td>
                    <td>
                      <?php
                        $typeBadge = match($adj['type']) {
                          'cycle_count' => '<span class="badge text-bg-primary">ตรวจนับสิ้นงวด</span>',
                          'damage'      => '<span class="badge text-bg-warning">สินค้าชำรุด</span>',
                          'loss'        => '<span class="badge text-bg-danger">สินค้าสูญหาย</span>',
                          'found'       => '<span class="badge text-bg-success">พบสินค้าเกิน</span>',
                          default       => '<span class="badge text-bg-secondary">' . esc($adj['type']) . '</span>'
                        };
                        echo $typeBadge;
                      ?>
                    </td>
                    <td>
                      <div>สาขา: <?= esc($adj['branch_name'] ?? 'สำนักงานใหญ่') ?></div>
                      <small class="text-muted">คลัง: <?= esc($adj['warehouse_name'] ?? 'คลังหลัก') ?></small>
                    </td>
                    <td class="text-danger fw-semibold">
                      <?= $adj['total_loss_value'] > 0 ? '-฿' . number_format((float)$adj['total_loss_value'], 2) : '-' ?>
                    </td>
                    <td class="text-success fw-semibold">
                      <?= $adj['total_gain_value'] > 0 ? '+฿' . number_format((float)$adj['total_gain_value'], 2) : '-' ?>
                    </td>
                    <td>
                      <?php if ($adj['status'] === 'approved'): ?>
                        <span class="badge text-bg-success"><i class="bi bi-check-all me-1"></i> อนุมัติแล้ว</span>
                      <?php elseif ($adj['status'] === 'draft'): ?>
                        <span class="badge text-bg-warning text-dark"><i class="bi bi-pencil me-1"></i> ฉบับร่าง</span>
                      <?php else: ?>
                        <span class="badge text-bg-secondary"><?= esc($adj['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($adj['created_at'])) ?></small></td>
                    <td class="text-end">
                      <a href="#" class="btn btn-outline-secondary btn-sm" title="ดูรายละเอียด">
                        <i class="bi bi-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-boxes fs-1 d-block mb-2"></i>ยังไม่มีประวัติการปรับปรุงสต็อกในระบบ
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!--end::Card-->
  </div>
</div>
<?= $this->endSection() ?>
