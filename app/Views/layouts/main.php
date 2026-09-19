<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <?= $this->include('layouts/partials/head') ?>

    <!--begin::Custom Page Styles-->
    <?= $this->renderSection('styles') ?>
    <!--end::Custom Page Styles-->
  </head>
  <!--end::Head-->

  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <?= $this->include('layouts/partials/navbar') ?>

      <?= $this->include('layouts/partials/sidebar') ?>

      <!--begin::App Main-->
      <main class="app-main">
        <?= $this->renderSection('content_header') ?: $this->include('layouts/partials/content_header') ?>

        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <?= $this->include('layouts/partials/alerts') ?>

            <?= $this->renderSection('content') ?>
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->

      <?= $this->include('layouts/partials/footer') ?>
    </div>
    <!--end::App Wrapper-->

    <?= $this->include('layouts/partials/scripts') ?>

    <!--begin::Custom Page Scripts-->
    <?= $this->renderSection('scripts') ?>
    <!--end::Custom Page Scripts-->
  </body>
  <!--end::Body-->
</html>
