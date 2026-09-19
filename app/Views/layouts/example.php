<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>หน้าตัวอย่างพิเศษ | AdminLTE 4<?= $this->endSection() ?>

<!-- custom styles -->
<?= $this->section('styles') ?>
<style>
  // Custom CSS can be placed here
</style>
<?= $this->endSection() ?>

<!-- Custom Page Actions (ปุ่มการทำงานชิดขวา Header) -->
<?= $this->section('page_actions') ?>
<button type="button" class="btn btn-sm btn-outline-secondary">
  <i class="bi bi-download me-1"></i> ส่งออกข้อมูล
</button>
<a href="#" class="btn btn-sm btn-primary">
  <i class="bi bi-plus-lg me-1"></i> เพิ่มรายการใหม่
</a>
<?= $this->endSection() ?>

<!-- Main Content -->
<?= $this->section('content') ?>
<!-- content goes here -->
<?= $this->endSection() ?>

<!-- page-specific scripts -->
<?= $this->section('scripts') ?>
<script>
  // Page-specific scripts can be placed here
</script>
<?= $this->endSection() ?>