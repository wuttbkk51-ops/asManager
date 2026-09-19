      <!--begin::Header-->
      <?php if (is_impersonating()): ?>
        <div class="bg-warning text-dark py-1 px-3 d-flex align-items-center justify-content-between small w-100 border-bottom">
          <div>
            <i class="bi bi-person-fill-exclamation me-1"></i>
            <strong><?= lang('App.impersonate_mode') ?>:</strong> คุณกำลังเข้าใช้งานในฐานะร้าน <strong><?= esc(tenant('name')) ?></strong> (บทบาท: <?= esc(auth_user('role')) ?>)
          </div>
          <a href="<?= site_url('stop-impersonate') ?>" class="btn btn-sm btn-dark py-0 px-2 fw-semibold">
            <i class="bi bi-arrow-return-left me-1"></i><?= lang('App.switch_back_admin') ?>
          </a>
        </div>
      <?php endif; ?>
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a
                class="nav-link"
                data-lte-toggle="sidebar"
                href="#"
                role="button"
                aria-label="Toggle sidebar"
              >
                <i class="bi bi-list"></i>
              </a>
            </li>

            <li class="nav-item d-none d-md-block">
              <a href="<?= base_url() ?>" class="nav-link">
                <i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i>
                <?= lang('App.home') ?>
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="https://adminlte.io/docs/4.0/" target="_blank" class="nav-link">
                <i class="bi bi-book me-1" aria-hidden="true"></i>
                <?= lang('App.docs') ?>
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::Navbar Search-->
          <form
            class="navbar-search d-none d-md-block ms-3"
            role="search"
            action="#"
          >
            <label for="navbar-search-input" class="visually-hidden">Search</label>
            <div class="navbar-search-field">
              <input
                type="search"
                id="navbar-search-input"
                name="q"
                class="form-control"
                placeholder="Search…"
                autocomplete="off"
              />
              <button class="navbar-search-submit" type="submit" aria-label="Submit search">
                <i class="bi bi-search" aria-hidden="true"></i>
              </button>
            </div>
          </form>
          <!--end::Navbar Search-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Search (small screens: the field above is hidden, so link to the search page)-->
            <li class="nav-item d-md-none">
              <a class="nav-link" href="#" aria-label="Search">
                <i class="bi bi-search" aria-hidden="true"></i>
              </a>
            </li>
            <!--end::Search-->
            <!--begin::Messages Dropdown Menu-->
            <li class="nav-item dropdown">
              <a
                class="nav-link"
                data-bs-toggle="dropdown"
                href="#"
                aria-label="Messages: 3 unread"
              >
                <i class="bi bi-chat-text"></i>
                <span class="navbar-badge badge text-bg-danger">3</span>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img
                        src="<?= base_url('adminlte/assets/img/user1-128x128.jpg') ?>"
                        alt=""
                        class="img-size-50 rounded-circle me-3"
                      />
                    </div>
                    <div class="flex-grow-1">
                      <p class="dropdown-item-title">
                        Brad Diesel
                        <span class="float-end fs-7 text-danger"
                          ><i class="bi bi-star-fill"></i
                        ></span>
                      </p>
                      <p class="fs-7">Call me whenever you can...</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img
                        src="<?= base_url('adminlte/assets/img/user8-128x128.jpg') ?>"
                        alt=""
                        class="img-size-50 rounded-circle me-3"
                      />
                    </div>
                    <div class="flex-grow-1">
                      <p class="dropdown-item-title">
                        John Pierce
                        <span class="float-end fs-7 text-secondary">
                          <i class="bi bi-star-fill"></i>
                        </span>
                      </p>
                      <p class="fs-7">I got your message bro</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                  <!--begin::Message-->
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <img
                        src="<?= base_url('adminlte/assets/img/user3-128x128.jpg') ?>"
                        alt=""
                        class="img-size-50 rounded-circle me-3"
                      />
                    </div>
                    <div class="flex-grow-1">
                      <p class="dropdown-item-title">
                        Nora Silvester
                        <span class="float-end fs-7 text-warning">
                          <i class="bi bi-star-fill"></i>
                        </span>
                      </p>
                      <p class="fs-7">The subject goes here</p>
                      <p class="fs-7 text-secondary">
                        <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                      </p>
                    </div>
                  </div>
                  <!--end::Message-->
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
              </div>
            </li>
            <!--end::Messages Dropdown Menu-->

            <!--begin::Notifications Dropdown Menu (ViewCell)-->
            <?= view_cell('NotificationCell') ?>
            <!--end::Notifications Dropdown Menu-->

            <!--begin::Language Menu-->
            <?php $activeLocale = service('request')->getLocale(); ?>
            <li class="nav-item dropdown">
              <a
                class="nav-link d-flex align-items-center gap-1"
                href="#"
                id="language-menu"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="Change language"
              >
                <i class="bi bi-translate" aria-hidden="true"></i>
                <span class="badge bg-secondary-subtle text-body border fw-semibold ms-1 font-monospace" style="font-size: 0.72rem;">
                  <?= strtoupper($activeLocale) ?>
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="language-menu" style="min-width: 140px;">
                <li>
                  <a class="dropdown-item d-flex justify-content-between align-items-center <?= $activeLocale === 'th' ? 'active' : '' ?>" href="<?= site_url('lang/th') ?>">
                    <span>ภาษาไทย</span>
                    <?php if ($activeLocale === 'th'): ?>
                      <i class="bi bi-check-lg ms-2" aria-hidden="true"></i>
                    <?php endif; ?>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item d-flex justify-content-between align-items-center <?= $activeLocale === 'en' ? 'active' : '' ?>" href="<?= site_url('lang/en') ?>">
                    <span>English</span>
                    <?php if ($activeLocale === 'en'): ?>
                      <i class="bi bi-check-lg ms-2" aria-hidden="true"></i>
                    <?php endif; ?>
                  </a>
                </li>
              </ul>
            </li>
            <!--end::Language Menu-->

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a
                class="nav-link"
                href="#"
                data-lte-toggle="fullscreen"
                aria-label="Toggle fullscreen"
              >
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->

            <!--begin::Color Mode Toggle (#6010)-->
            <li class="nav-item dropdown">
              <a
                class="nav-link"
                href="#"
                id="bd-theme"
                aria-label="Toggle color scheme"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
              </a>
              <ul
                class="dropdown-menu dropdown-menu-end"
                aria-labelledby="bd-theme"
                style="--bs-dropdown-min-width: 8rem"
              >
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="light"
                    aria-pressed="false"
                  >
                    <i class="bi bi-sun-fill me-2"></i>
                    Light
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="dark"
                    aria-pressed="false"
                  >
                    <i class="bi bi-moon-fill me-2"></i>
                    Dark
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center active"
                    data-bs-theme-value="auto"
                    aria-pressed="true"
                  >
                    <i class="bi bi-circle-half me-2"></i>
                    Auto
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
              </ul>
            </li>
            <!--end::Color Mode Toggle-->

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="<?= base_url('adminlte/assets/img/user2-160x160.jpg') ?>"
                  class="user-image rounded-circle shadow"
                  alt="<?= esc(auth_user('name') ?? 'User') ?>"
                />
                <span class="d-none d-md-inline"><?= esc(auth_user('name') ?? 'Admin User') ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <!--begin::User Image-->
                <li class="user-header text-bg-primary">
                  <img
                    src="<?= base_url('adminlte/assets/img/user2-160x160.jpg') ?>"
                    class="rounded-circle shadow"
                    alt="<?= esc(auth_user('name') ?? 'User') ?>"
                  />
                  <p>
                    <?= esc(auth_user('name') ?? 'Admin User') ?>
                    <small class="d-block mt-1">
                      <?php if (is_superadmin() || empty(tenant('id'))): ?>
                        <i class="bi bi-shield-lock-fill me-1"></i>Super Admin
                        <span class="badge text-bg-warning text-dark ms-1">Platform Admin</span>
                      <?php else: ?>
                        <i class="bi bi-shop me-1"></i><?= esc(tenant('name')) ?>
                        <span class="badge text-bg-light text-dark ms-1"><?= esc(auth_user('role') ?? 'owner') ?></span>
                        <span class="badge text-bg-info text-dark ms-1"><?= esc(tenant('plan') ?? 'freePlan') ?></span>
                      <?php endif; ?>
                    </small>
                  </p>
                </li>
                <!--end::User Image-->
                <!--begin::Menu Body-->
                <li class="user-body">
                  <div class="text-center small text-muted">
                    <div>อีเมล: <?= esc(auth_user('email') ?? '-') ?></div>
                    <?php if (tenant('slug')): ?>
                      <div class="mt-1">
                        <span class="badge text-bg-secondary-subtle text-secondary border">Slug: <?= esc(tenant('slug')) ?></span>
                        <span class="badge text-bg-success-subtle text-success border ms-1"><?= esc(tenant('plan')) ?></span>
                      </div>
                    <?php endif; ?>
                  </div>
                </li>
                <!--end::Menu Body-->
                <!--begin::Menu Footer-->
                <li class="user-footer d-flex justify-content-between align-items-center">
                  <a href="<?= site_url('profile') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person me-1"></i><?= lang('App.profile') ?>
                  </a>
                  <a href="<?= site_url('dashboard/settings') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-gear me-1"></i><?= lang('App.settings') ?>
                  </a>
                  <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i><?= lang('App.sign_out') ?>
                  </a>
                </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
