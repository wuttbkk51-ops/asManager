<!--begin::Flash Messages / Alerts-->
<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi bi-check-circle-fill fs-5 me-2"></i>
      <div><?= session()->getFlashdata('success') ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi bi-x-circle-fill fs-5 me-2"></i>
      <div><?= session()->getFlashdata('error') ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-start">
      <i class="bi bi-exclamation-octagon-fill fs-5 me-2 mt-1"></i>
      <div>
        <strong>พบข้อผิดพลาด:</strong>
        <ul class="mb-0 mt-1 ps-3">
          <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
            <li><?= esc($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
      <div><?= session()->getFlashdata('warning') ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('info')): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi bi-info-circle-fill fs-5 me-2"></i>
      <div><?= session()->getFlashdata('info') ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<!--end::Flash Messages / Alerts-->
