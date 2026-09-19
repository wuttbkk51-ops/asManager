<!doctype html>
<html lang="th">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= isset($title) ? esc($title) . ' | AdminLTE 4' : 'เข้าสู่ระบบ | AdminLTE 4' ?></title>
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
  <body class="login-page bg-body-secondary">
    <div class="login-box">
      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center py-3">
          <a href="<?= site_url('/') ?>" class="link-dark text-decoration-none">
            <h1 class="mb-0 fs-3"><b>Admin</b>LTE 4</h1>
          </a>
        </div>
        <div class="card-body login-card-body">
          <p class="login-box-msg mb-3">เข้าสู่ระบบเพื่อเริ่มใช้งาน</p>

          <!--begin::Alerts-->
          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <?= esc(session()->getFlashdata('error')) ?>
              <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
              <i class="bi bi-check-circle-fill me-1"></i>
              <?= esc(session()->getFlashdata('success')) ?>
              <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          <!--end::Alerts-->

          <form action="<?= site_url('login') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Email Field -->
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="loginEmail"
                  type="email"
                  name="email"
                  class="form-control"
                  placeholder="name@example.com"
                  value="<?= old('email', 'admin@example.com') ?>"
                  required
                />
                <label for="loginEmail">อีเมล (Email)</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-envelope"></span>
              </div>
            </div>

            <!-- Password Field -->
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="loginPassword"
                  type="password"
                  name="password"
                  class="form-control"
                  placeholder="Password"
                  value="123456"
                  required
                />
                <label for="loginPassword">รหัสผ่าน (Password)</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
              </div>
            </div>

            <!--begin::Row-->
            <div class="row align-items-center mb-3">
              <div class="col-7">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe" />
                  <label class="form-check-label text-muted small" for="rememberMe">จดจำฉัน</label>
                </div>
              </div>
              <div class="col-5">
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                </div>
              </div>
            </div>
            <!--end::Row-->
          </form>

          <div class="text-center pt-2 border-top">
            <p class="mb-2 small">
              ยังไม่มีบัญชี? <a href="<?= site_url('register') ?>" class="text-primary text-decoration-none fw-semibold">สมัครสมาชิกใหม่</a>
            </p>
            <p class="mb-0 text-secondary small">
              <i class="bi bi-info-circle me-1"></i> ผู้ใช้ทดสอบ: <code>admin@example.com</code> / <code>123456</code>
            </p>
          </div>
        </div>
        <!-- /.login-card-body -->
      </div>
    </div>
    <!-- /.login-box -->

    <!--begin::Scripts-->
    <script src="<?= base_url('plugins/bootstrap/js/bootstrap.min.js') ?>"></script>
    <script src="<?= base_url('adminlte/js/adminlte.min.js') ?>"></script>
    <!--end::Scripts-->
  </body>
  <!--end::Body-->
</html>
