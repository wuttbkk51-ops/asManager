<!--begin::Notifications Dropdown Menu (ViewCell: NotificationCell)-->
<li class="nav-item dropdown">
  <a
    class="nav-link"
    data-bs-toggle="dropdown"
    href="#"
    aria-label="Notifications: <?= esc($unreadCount) ?> unread"
  >
    <i class="bi bi-bell-fill"></i>
    <?php if ($unreadCount > 0): ?>
      <span class="navbar-badge badge text-bg-warning"><?= esc($unreadCount) ?></span>
    <?php endif; ?>
  </a>
  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
    <span class="dropdown-item dropdown-header"><?= esc($unreadCount) ?> Notifications</span>
    <?php foreach ($notifications as $item): ?>
      <div class="dropdown-divider"></div>
      <a href="#" class="dropdown-item">
        <i class="bi <?= esc($item['icon']) ?> me-2"></i> <?= esc($item['title']) ?>
        <span class="float-end text-secondary fs-7"><?= esc($item['time']) ?></span>
      </a>
    <?php endforeach; ?>
    <div class="dropdown-divider"></div>
    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
  </div>
</li>
<!--end::Notifications Dropdown Menu-->
