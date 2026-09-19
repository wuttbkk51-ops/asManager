<!doctype html>
<html lang="th">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= isset($title) ? esc($title) . ' | AdminLTE 4' : 'สมัครสมาชิก | AdminLTE 4' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />

    <!--begin::Fonts-->
    <link rel="stylesheet" href="<?= base_url('plugins/source-sans-3/index.css') ?>" />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link rel="stylesheet" href="<?= base_url('plugins/bootstrap-icons/font/bootstrap-icons.min.css') ?>" />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="<?= base_url('adminlte/css/adminlte.min.css') ?>" />
    <!--end::Required Plugin(AdminLTE)-->
  </head>
  <!--end::Head-->

  <!--begin::Body-->
  <body class="register-page bg-body-secondary">
    <div class="register-box">
      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center py-3">
          <a href="<?= site_url('/') ?>" class="link-dark text-decoration-none">
            <h1 class="mb-0 fs-3"><b>Admin</b>LTE 4</h1>
          </a>
        </div>
        <div class="card-body register-card-body">
          <p class="register-box-msg mb-3">สมัครสมาชิกใหม่ (กรอกเพียงอีเมลและรหัสผ่าน)</p>

          <!--begin::Alerts-->
          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <?= esc(session()->getFlashdata('error')) ?>
              <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
              <ul class="mb-0 ps-3">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                  <li><?= esc($err) ?></li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          <!--end::Alerts-->

          <form action="<?= site_url('register') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Email Field -->
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="registerEmail"
                  type="email"
                  name="email"
                  class="form-control"
                  placeholder="name@example.com"
                  value="<?= old('email') ?>"
                  required
                />
                <label for="registerEmail">อีเมล (Email)</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-envelope"></span>
              </div>
            </div>

            <!-- Password Field -->
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="registerPassword"
                  type="password"
                  name="password"
                  class="form-control"
                  placeholder="Password"
                  required
                />
                <label for="registerPassword">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
              </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="registerPasswordConfirm"
                  type="password"
                  name="password_confirm"
                  class="form-control"
                  placeholder="Confirm Password"
                  required
                />
                <label for="registerPasswordConfirm">ยืนยันรหัสผ่าน</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-shield-lock-fill"></span>
              </div>
            </div>

            <!--begin::Row-->
            <div class="row align-items-center mb-3">
              <div class="col-7">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="terms" value="1" id="termsCheck" checked required />
                  <label class="form-check-label text-muted small" for="termsCheck">
                    ยอมรับเงื่อนไข
                  </label>
                </div>
              </div>
              <div class="col-5">
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
                </div>
              </div>
            </div>
            <!--end::Row-->
          </form>

          <div class="text-center pt-2 border-top">
            <p class="mb-0 small">
              มีบัญชีอยู่แล้ว? <a href="<?= site_url('login') ?>" class="text-primary text-decoration-none fw-semibold">เข้าสู่ระบบ</a>
            </p>
          </div>
        </div>
        <!-- /.register-card-body -->
      </div>
    </div>
    <!-- /.register-box -->

    <!--begin::Scripts-->
    <script src="<?= base_url('plugins/bootstrap/js/bootstrap.min.js') ?>"></script>
    <script src="<?= base_url('adminlte/js/adminlte.min.js') ?>"></script>
    <!--end::Scripts-->
  </body>
  <!--end::Body-->
</html>
