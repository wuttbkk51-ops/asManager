<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--begin::Page Header (Minimal & Calm)-->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-semibold mb-0">โปรไฟล์และบัญชีผู้ใช้</h4>
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

<div class="row g-4">
  <!--begin::Col Left: Profile Summary Card-->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body text-center p-4">
        <!-- User Avatar -->
        <div class="mb-3">
          <img
            src="<?= base_url('adminlte/assets/img/user2-160x160.jpg') ?>"
            class="rounded-circle border"
            alt="User Image"
            style="width: 96px; height: 96px; object-fit: cover;"
          />
        </div>

        <h5 class="fw-medium mb-1">
          <?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username']) ?>
        </h5>
        <div class="text-muted small mb-2">@<?= esc($user['username'] ?? '-') ?></div>

        <!-- Role Badge -->
        <div class="mb-3">
          <span class="badge bg-body-secondary text-secondary-emphasis px-3 py-1 fw-normal text-uppercase">
            <?= esc($user['role'] ?? 'User') ?>
          </span>
        </div>

        <hr class="my-3 opacity-25">

        <!-- Info List -->
        <div class="text-start small">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">อีเมล</span>
            <span class="fw-medium text-body"><?= esc($user['email'] ?? '-') ?></span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">เบอร์โทร</span>
            <span class="fw-medium text-body"><?= esc($user['phone'] ?? '-') ?></span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">ร้านค้า</span>
            <span class="fw-medium text-body"><?= esc(tenant('name') ?? (is_superadmin() ? 'SaaS Platform' : '-')) ?></span>
          </div>
          <?php if (!empty($user['bio'])): ?>
            <div class="mt-3 pt-2 border-top text-muted">
              <?= nl2br(esc($user['bio'])) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!--end::Col Left-->

  <!--begin::Col Right: Navigation Tabs Content-->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-bottom bg-transparent p-3">
        <ul class="nav nav-pills card-header-pills" id="profile-tabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="info-tab" data-bs-toggle="pill" href="#info" role="tab" aria-selected="true">
              ข้อมูลส่วนตัว
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="security-tab" data-bs-toggle="pill" href="#security" role="tab" aria-selected="false">
              รหัสผ่านและความปลอดภัย
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="permissions-tab" data-bs-toggle="pill" href="#permissions" role="tab" aria-selected="false">
              สิทธิ์การใช้งาน
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="preferences-tab" data-bs-toggle="pill" href="#preferences" role="tab" aria-selected="false">
              ตั้งค่าหน้าจอ
            </a>
          </li>
        </ul>
      </div>

      <div class="card-body p-4">
        <div class="tab-content" id="profileTabContent">

          <!-- ========================================================= -->
          <!-- TAB 1: ข้อมูลส่วนตัว (Personal Info)                     -->
          <!-- ========================================================= -->
          <div class="tab-pane fade show active" id="info" role="tabpanel">
            <form action="<?= site_url('profile/update') ?>" method="POST">
              <?= csrf_field() ?>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="first_name">ชื่อจริง</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" value="<?= esc($user['first_name'] ?? '') ?>" placeholder="ชื่อจริง" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="last_name">นามสกุล</label>
                  <input type="text" class="form-control" id="last_name" name="last_name" value="<?= esc($user['last_name'] ?? '') ?>" placeholder="นามสกุล" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="username">ชื่อผู้ใช้งาน (Username)</label>
                  <input type="text" class="form-control bg-body-tertiary font-monospace" id="username" value="<?= esc($user['username'] ?? '') ?>" readonly />
                  <div class="form-text">ชื่อผู้ใช้สำหรับเข้าสู่ระบบ (ไม่สามารถแก้ไขได้)</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="email">อีเมล <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" value="<?= esc($user['email'] ?? '') ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="phone">เบอร์โทรศัพท์</label>
                  <input type="text" class="form-control" id="phone" name="phone" value="<?= esc($user['phone'] ?? '') ?>" placeholder="089-123-4567" />
                </div>
                <div class="col-12">
                  <label class="form-label fw-medium" for="bio">ประวัติย่อ / หมายเหตุ</label>
                  <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="ระบุข้อมูลสั้นๆ เช่น ตำแหน่งงาน หรือหน้าที่ความรับผิดชอบ"><?= esc($user['bio'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4">
                  บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>

          <!-- ========================================================= -->
          <!-- TAB 2: รหัสผ่านและความปลอดภัย (Security & Password)       -->
          <!-- ========================================================= -->
          <div class="tab-pane fade" id="security" role="tabpanel">
            <form action="<?= site_url('profile/password') ?>" method="POST">
              <?= csrf_field() ?>
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <label class="form-label fw-medium" for="current_password">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="ป้อนรหัสผ่านปัจจุบัน" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="new_password">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="อย่างน้อย 6 ตัวอักษร" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" for="confirm_password">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="ป้อนรหัสผ่านใหม่อีกครั้ง" />
                </div>
              </div>
              <div class="pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4">
                  เปลี่ยนรหัสผ่าน
                </button>
              </div>
            </form>
          </div>

          <!-- ========================================================= -->
          <!-- TAB 3: สิทธิ์การใช้งานของฉัน (My Permissions)              -->
          <!-- ========================================================= -->
          <div class="tab-pane fade" id="permissions" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-muted small">สิทธิ์การใช้งานที่ผูกกับบทบาทของคุณในระบบ</span>
              <span class="badge bg-body-secondary text-secondary-emphasis fw-normal"><?= esc($user['role'] ?? 'User') ?></span>
            </div>

            <?php if (in_array('*', $userPermissions, true) || is_superadmin()): ?>
              <div class="alert alert-light border d-flex align-items-center p-3 mb-4">
                <div>
                  <div class="fw-medium text-body mb-1">สิทธิ์ระดับสูงสุด (Superadmin Wildcard '*')</div>
                  <div class="small text-muted">คุณมีสิทธิ์เข้าถึงทุกหน้าจอ ทุกโมดูล และทุกข้อมูลในระบบ</div>
                </div>
              </div>
            <?php endif; ?>

            <div class="row g-3">
              <?php foreach ($permissionGroups as $groupKey => $group): ?>
                <div class="col-md-6">
                  <div class="border rounded p-3 h-100 bg-body">
                    <div class="fw-medium text-body small mb-2">
                      <?= esc($group['title']) ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                      <?php foreach ($group['items'] as $itemPerm): ?>
                        <?php
                          $hasPerm = in_array('*', $userPermissions, true) 
                                     || in_array($itemPerm, $userPermissions, true)
                                     || in_array(explode('.', $itemPerm)[0] . '.*', $userPermissions, true);
                        ?>
                        <span class="badge <?= $hasPerm ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-body-secondary text-muted' ?> font-monospace fw-normal" style="font-size: 0.72rem;">
                          <?= esc($itemPerm) ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- ========================================================= -->
          <!-- TAB 4: การตั้งค่าเฉพาะบุคคล (Preferences & User Settings)  -->
          <!-- ========================================================= -->
          <div class="tab-pane fade" id="preferences" role="tabpanel">
            <form action="<?= site_url('profile/settings') ?>" method="POST">
              <?= csrf_field() ?>
              <div class="row g-4 mb-4">
                <!-- Theme Mode -->
                <div class="col-md-6">
                  <label class="form-label fw-medium">ธีมแสดงผล</label>
                  <select class="form-select" name="theme">
                    <option value="light" <?= ($userSettings['theme'] ?? '') === 'light' ? 'selected' : '' ?>>โหมดสว่าง (Light)</option>
                    <option value="dark" <?= ($userSettings['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>โหมดมืด (Dark)</option>
                    <option value="auto" <?= ($userSettings['theme'] ?? '') === 'auto' ? 'selected' : '' ?>>ตามระบบปฏิบัติการ (Auto)</option>
                  </select>
                </div>

                <!-- Language -->
                <div class="col-md-6">
                  <label class="form-label fw-medium">ภาษา</label>
                  <select class="form-select" name="language">
                    <option value="th" <?= ($userSettings['language'] ?? '') === 'th' ? 'selected' : '' ?>>ภาษาไทย (Thai)</option>
                    <option value="en" <?= ($userSettings['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                  </select>
                </div>

                <!-- Items Per Page -->
                <div class="col-md-6">
                  <label class="form-label fw-medium">จำนวนรายการต่อหน้า</label>
                  <select class="form-select" name="items_per_page">
                    <option value="10" <?= ($userSettings['items_per_page'] ?? 25) == 10 ? 'selected' : '' ?>>10 รายการ</option>
                    <option value="25" <?= ($userSettings['items_per_page'] ?? 25) == 25 ? 'selected' : '' ?>>25 รายการ</option>
                    <option value="50" <?= ($userSettings['items_per_page'] ?? 25) == 50 ? 'selected' : '' ?>>50 รายการ</option>
                    <option value="100" <?= ($userSettings['items_per_page'] ?? 25) == 100 ? 'selected' : '' ?>>100 รายการ</option>
                  </select>
                </div>

                <!-- Notification Switches -->
                <div class="col-12">
                  <div class="border rounded p-3">
                    <div class="fw-medium small mb-3">เสียงและการแจ้งเตือน</div>
                    
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                      <div>
                        <div class="fw-medium small">เสียงแจ้งเตือน</div>
                        <div class="text-muted" style="font-size: 0.8rem;">ส่งเสียงเมื่อสแกนบาร์โค้ดสำเร็จหรือมีคำสั่งซื้อใหม่</div>
                      </div>
                      <div class="form-check form-switch fs-5 ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" name="sound_enabled" value="1" <?= ($userSettings['sound_enabled'] ?? 1) == 1 ? 'checked' : '' ?>>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                      <div>
                        <div class="fw-medium small">แจ้งเตือนสถานะงานซ่อม</div>
                        <div class="text-muted" style="font-size: 0.8rem;">แสดงการแจ้งเตือนเมื่อมีงานซ่อมเข้าใหม่หรือขอเบิกอะไหล่</div>
                      </div>
                      <div class="form-check form-switch fs-5 ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" name="notify_repair" value="1" <?= ($userSettings['notify_repair'] ?? 1) == 1 ? 'checked' : '' ?>>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                      <div>
                        <div class="fw-medium small">แจ้งเตือนสต็อกใกล้หมด</div>
                        <div class="text-muted" style="font-size: 0.8rem;">แสดงการแจ้งเตือนเมื่อสินค้าในคลังต่ำกว่าเกณฑ์</div>
                      </div>
                      <div class="form-check form-switch fs-5 ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" name="notify_stock" value="1" <?= ($userSettings['notify_stock'] ?? 1) == 1 ? 'checked' : '' ?>>
                      </div>
                    </div>

                  </div>
                </div>
              </div>

              <div class="pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4">
                  บันทึกการตั้งค่า
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
  <!--end::Col Right-->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const hash = window.location.hash;
  if (hash) {
    const triggerEl = document.querySelector(`#profile-tabs a[href="${hash}"]`);
    if (triggerEl) {
      bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }
  }

  document.querySelectorAll('#profile-tabs a').forEach(el => {
    el.addEventListener('shown.bs.tab', e => {
      history.replaceState(null, null, e.target.getAttribute('href'));
    });
  });
});
</script>
<?= $this->endSection() ?>
