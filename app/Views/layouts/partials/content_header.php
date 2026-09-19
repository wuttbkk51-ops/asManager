<?php
/**
 * Hybrid Breadcrumbs Generator (Dashboard as Root):
 * 1. Root: ถือว่า 'Dashboard' คือ Root ของระบบหลังบ้าน
 * 2. Custom: หาก Controller/View ส่ง $breadcrumbs มา จะนำมาต่อท้าย Dashboard
 * 3. Automate: หากไม่มีการส่ง $breadcrumbs จะสร้างจาก URI Segments ให้อัตโนมัติ
 *    - หากอยู่ที่หน้า Dashboard เอง จะแสดง 'Dashboard' (Active)
 *    - ไม่ใช้ dictionary (พร้อมต่อยอดด้วย lang() ของ CI4 ได้โดยตรง)
 *    - หากขึ้นต้นด้วย 'admin' หรือ 'dashboard' จะไม่นำมาแสดงซ้ำซ้อน
 *    - ชิ้นสุดท้ายจะใช้ $title หากมีกำหนดไว้
 */

$uri = service('request')->getUri();
$segments = $uri->getSegments();

// ตรวจสอบว่าหน้าปัจจุบันคือหน้า Root Dashboard หรือไม่
$isDashboardRoot = empty($segments)
    || (count($segments) === 1 && in_array(strtolower($segments[0]), ['admin', 'dashboard'], true))
    || (count($segments) === 2 && strtolower($segments[0]) === 'admin' && strtolower($segments[1]) === 'dashboard');

if ($isDashboardRoot && (!isset($breadcrumbs) || empty($breadcrumbs))) {
    $breadcrumbs = [];
} elseif (!isset($breadcrumbs) || !is_array($breadcrumbs) || empty($breadcrumbs)) {
    $breadcrumbs = [];
    $pathPrefix = '';
    $startIndex = 0;

    // หาก Segment แรกเป็น 'admin' หรือ 'dashboard' ให้ใช้เป็น URL Prefix แต่ไม่ต้องแสดงป้ายซ้ำ
    if (isset($segments[0]) && strtolower($segments[0]) === 'admin') {
        $pathPrefix = '/admin';
        $startIndex = 1;
    } elseif (isset($segments[0]) && strtolower($segments[0]) === 'dashboard') {
        $pathPrefix = '/dashboard';
        $startIndex = 1;
    }

    $subSegments = array_slice($segments, $startIndex);
    $totalSub = count($subSegments);
    $accumulated = $pathPrefix;

    foreach ($subSegments as $index => $segment) {
        $accumulated .= '/' . $segment;
        $isLast = ($index === $totalSub - 1);

        // จัดรูปแบบคำพื้นฐาน (เช่น 'user-roles' -> 'User Roles', ID ตัวเลข -> '#42')
        if (is_numeric($segment)) {
            $label = '#' . $segment;
        } else {
            $label = ucwords(str_replace(['-', '_'], ' ', $segment));
        }

        // กรณีเป็นตัวสุดท้ายและมี $title ให้ใช้ $title (ยกเว้นถ้า $title เป็น 'Dashboard')
        if ($isLast && !empty($title) && strcasecmp($title, 'Dashboard') !== 0) {
            $label = $title;
        }

        if ($isLast) {
            $breadcrumbs[] = $label; // Active หน้าปัจจุบัน (ไม่มีลิงก์)
        } else {
            $breadcrumbs[$label] = site_url(ltrim($accumulated, '/')); // มีลิงก์ย้อนกลับ
        }
    }
}
?>
<!--begin::App Content Header (Single-line Compact Bar)-->
<div class="app-content-header py-2">
  <!--begin::Container-->
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <!-- Left: Single-line Breadcrumb Page Indicator -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 align-items-center fs-5">
          <?php if ($isDashboardRoot && empty($breadcrumbs)): ?>
            <!-- หน้า Dashboard เอง: แสดงเป็น Active เดี่ยวๆ -->
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          <?php else: ?>
            <!-- หน้าอื่นๆ: เริ่มต้นที่ Dashboard เป็น Root เสมอ -->
            <li class="breadcrumb-item">
              <a href="<?= site_url('dashboard') ?>">Dashboard</a>
            </li>
            <?php foreach ($breadcrumbs as $label => $url): ?>
              <?php if (is_numeric($label)): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= esc($url) ?></li>
              <?php else: ?>
                <li class="breadcrumb-item">
                  <a href="<?= esc($url) ?>"><?= esc($label) ?></a>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ol>
      </nav>

      <!-- Right: Custom Page Actions Menu -->
      <div class="d-flex align-items-center gap-2 ms-auto page-actions">
        <?= $this->renderSection('page_actions') ?>
      </div>
    </div>
  </div>
  <!--end::Container-->
</div>
<!--end::App Content Header-->
