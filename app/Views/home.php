<!doctype html>
<html lang="th">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'ยินดีต้อนรับสู่ระบบบริหารจัดการ') ?></title>

    <!-- Google Font: Source Sans 3 -->
    <link rel="stylesheet" href="<?= base_url('plugins/source-sans-3/index.css') ?>" />

    <!-- Bootstrap 5 CSS (via AdminLTE Core CSS) -->
    <link rel="stylesheet" href="<?= base_url('adminlte/css/adminlte.min.css') ?>" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= base_url('plugins/bootstrap-icons/font/bootstrap-icons.min.css') ?>" />

    <style>
      body {
        font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f8f9fa;
      }
      .hero-section {
        padding: 5rem 0 4rem;
        background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
      }
      .feature-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: 1px solid rgba(0, 0, 0, 0.08);
      }
      .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      }
      .feature-icon {
        width: 3.5rem;
        height: 3.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        font-size: 1.75rem;
      }
    </style>
  </head>
  <body class="d-flex flex-column min-vh-100">

    <!--begin::Navbar-->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="<?= base_url() ?>">
          <i class="bi bi-box-seam-fill me-2 fs-4"></i>
          <span>assManager</span>
        </a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarContent"
          aria-controls="navbarContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="<?= base_url() ?>">หน้าแรก</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= site_url('example') ?>">ตัวอย่าง Layout</a>
            </li>
          </ul>
          <div class="d-flex align-items-center gap-2">
            <!-- Link Go to Dashboard -->
            <a href="<?= site_url('dashboard') ?>" class="btn btn-primary d-inline-flex align-items-center shadow-sm">
              <i class="bi bi-speedometer2 me-2"></i> ไปที่ Dashboard
            </a>
          </div>
        </div>
      </div>
    </nav>
    <!--end::Navbar-->

    <!--begin::Hero Section-->
    <header class="hero-section text-center">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">
              <i class="bi bi-stars me-1"></i> CodeIgniter 4 + AdminLTE 4
            </span>
            <h1 class="display-4 fw-bold text-dark mb-3">
              ระบบบริหารจัดการสินทรัพย์ & ฐานข้อมูล
            </h1>
            <p class="lead text-secondary mb-4">
              ยินดีต้อนรับสู่หน้า Landing Page (Public Area) ออกแบบโครงสร้างแยกเป็นสัดส่วน
              พร้อมเชื่อมต่อแผงควบคุมระบบหลังบ้านได้อย่างราบรื่นและปลอดภัย
            </p>
            <div class="d-flex justify-content-center gap-3">
              <!-- Call To Action Button -->
              <a href="<?= site_url('dashboard') ?>" class="btn btn-primary btn-lg px-4 shadow">
                <i class="bi bi-arrow-right-circle me-2"></i> เข้าสู่ระบบหลังบ้าน (Dashboard)
              </a>
              <a href="<?= site_url('example') ?>" class="btn btn-outline-secondary btn-lg px-4">
                <i class="bi bi-file-earmark-code me-2"></i> ดูหน้าตัวอย่าง
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>
    <!--end::Hero Section-->

    <!--begin::Features Section-->
    <main class="container my-5 flex-grow-1">
      <div class="text-center mb-5">
        <h2 class="fw-bold">คุณสมบัติและความสามารถเด่น</h2>
        <p class="text-secondary">โครงสร้างระบบที่พร้อมรองรับการพัฒนาต่อยอดในระดับ Enterprise</p>
      </div>

      <div class="row g-4">
        <!-- Feature 1 -->
        <div class="col-md-4">
          <div class="card h-100 feature-card shadow-sm p-4 text-center">
            <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
              <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h5 class="fw-bold">รวดเร็ว & ทันสมัย</h5>
            <p class="text-secondary mb-0">
              ขับเคลื่อนด้วย CodeIgniter 4.7.4 บนสถาปัตยกรรม PHP 8.3 ประสิทธิภาพสูง โหลดไวและเบาเครื่อง
            </p>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-4">
          <div class="card h-100 feature-card shadow-sm p-4 text-center">
            <div class="feature-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
              <i class="bi bi-layout-text-window-reverse"></i>
            </div>
            <h5 class="fw-bold">AdminLTE 4 + Auto Breadcrumbs</h5>
            <p class="text-secondary mb-0">
              ส่วนติดต่อผู้ใช้แผงควบคุมหลังบ้านที่สวยงาม พร้อมระบบ Breadcrumbs และ ViewCell อัตโนมัติ
            </p>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-4">
          <div class="card h-100 feature-card shadow-sm p-4 text-center">
            <div class="feature-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
              <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h5 class="fw-bold">แยก Public & Admin ชัดเจน</h5>
            <p class="text-secondary mb-0">
              จัดแบ่ง Controllers แยกเป็น Public และ Private Admin เพื่อให้พร้อมผูกกับ Auth Middleware
            </p>
          </div>
        </div>
      </div>
    </main>
    <!--end::Features Section-->

    <!--begin::Footer-->
    <footer class="bg-white border-top py-4 mt-auto">
      <div class="container text-center text-secondary">
        <p class="mb-1">&copy; <?= date('Y') ?> <strong>assManager</strong>. All rights reserved.</p>
        <small>
          <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i>เข้าสู่ Dashboard
          </a>
        </small>
      </div>
    </footer>
    <!--end::Footer-->

    <!-- Popper JS & Bootstrap 5 JS -->
    <script src="<?= base_url('plugins/popper/popper.min.js') ?>"></script>
    <script src="<?= base_url('plugins/bootstrap/js/bootstrap.min.js') ?>"></script>
  </body>
</html>
