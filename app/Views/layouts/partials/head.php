<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= $this->renderSection('title') ?: (isset($title) ? esc($title) . ' | AdminLTE 4' : 'AdminLTE 4') ?></title>

<!--begin::CSRF Protection-->
<meta name="csrf-token" content="<?= csrf_hash() ?>" />
<meta name="csrf-header" content="<?= csrf_header() ?>" />
<!--end::CSRF Protection-->

<!--begin::Theme Init (prevents flash of incorrect theme on load, #6043)-->
<script>
  (() => {
    'use strict';
    const root = document.documentElement;

    // Applications with their own theming opt out of AdminLTE's color mode
    // entirely, here as well as in the bundle.
    if (root.getAttribute('data-lte-color-mode') === 'off') {
      return;
    }

    const STORAGE_KEY = 'lte-theme';
    let stored = null;
    try {
      stored = localStorage.getItem(STORAGE_KEY);
    } catch {
      // localStorage may be unavailable (private mode, sandboxed iframe).
    }
    // Mirror the precedence in color-mode.ts: the visitor's stored choice
    // wins, then a theme this page declared itself, then the OS preference.
    const authored = root.getAttribute('data-bs-theme');
    let resolved = 'light';
    if (stored === 'dark' || stored === 'light') {
      resolved = stored;
    } else if (authored === 'dark' || authored === 'light') {
      resolved = authored;
    } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
      resolved = 'dark';
    }
    root.setAttribute('data-bs-theme', resolved);
    root.style.colorScheme = resolved;
    // Flag values computed here, so the bundle does not mistake them for a
    // theme the page declared and stop following the OS preference.
    if (resolved !== authored) {
      root.setAttribute('data-lte-theme-resolved', '');
    }
  })();
</script>
<!--end::Theme Init-->

<!--begin::Accessibility Meta Tags-->
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
<meta name="color-scheme" content="light dark" />
<meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
<!--end::Accessibility Meta Tags-->

<!--begin::Primary Meta Tags-->
<meta name="title" content="<?= $this->renderSection('title') ?: (isset($title) ? esc($title) . ' | AdminLTE 4' : 'AdminLTE 4') ?>" />
<meta name="author" content="ColorlibHQ" />
<meta
  name="description"
  content="AdminLTE is a free Bootstrap 5 admin dashboard template with almost 50 example pages, built with vanilla JS and designed with accessibility in mind."
/>
<meta
  name="keywords"
  content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel"
/>
<!--end::Primary Meta Tags-->

<!--begin::Accessibility Features-->
<!-- Skip links will be dynamically added by accessibility.js -->
<meta name="supported-color-schemes" content="light dark" />
<link rel="preload" href="<?= base_url('adminlte/css/adminlte.min.css') ?>" as="style" />
<!--end::Accessibility Features-->

<!--begin::Fonts-->
<link
  rel="stylesheet"
  href="<?= base_url('plugins/source-sans-3/index.css') ?>"
  media="print"
  onload="this.media = 'all'"
/>
<!--end::Fonts-->

<!--begin::Third Party Plugin(OverlayScrollbars)-->
<link
  rel="stylesheet"
  href="<?= base_url('plugins/overlayscrollbars/css/overlayscrollbars.min.css') ?>"
/>
<!--end::Third Party Plugin(OverlayScrollbars)-->

<!--begin::Third Party Plugin(Bootstrap Icons)-->
<link
  rel="stylesheet"
  href="<?= base_url('plugins/bootstrap-icons/font/bootstrap-icons.min.css') ?>"
/>
<!--end::Third Party Plugin(Bootstrap Icons)-->

<!--begin::Required Plugin(AdminLTE)-->
<link rel="stylesheet" href="<?= base_url('adminlte/css/adminlte.min.css') ?>" />
<!--end::Required Plugin(AdminLTE)-->

<!--begin::Tabulator Table CSS (Bootstrap 5 Theme)-->
<link rel="stylesheet" href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator_bootstrap5.min.css" />
<style>
  /* Tabulator AdminLTE 4 Polish */
  .tabulator.tabulator-bootstrap-5 {
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.875rem;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-header,
  .tabulator.tabulator-bootstrap-5 .tabulator-header .tabulator-col {
    background-color: var(--bs-tertiary-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--bs-border-color) !important;
    font-weight: 600;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-header .tabulator-col.tabulator-sortable:hover {
    background-color: var(--bs-secondary-bg) !important;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-row {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border-bottom: 1px solid var(--bs-border-color-translucent);
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-row:hover {
    background-color: var(--bs-secondary-bg) !important;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-cell {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    vertical-align: middle !important;
    padding: 8px 12px !important;
    display: inline-flex;
    align-items: center;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-cell[data-hozalign="right"] {
    justify-content: flex-end;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-cell[data-hozalign="center"] {
    justify-content: center;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-footer {
    padding: 10px 16px !important;
    background-color: var(--bs-tertiary-bg) !important;
    color: var(--bs-body-color) !important;
    border-top: 1px solid var(--bs-border-color) !important;
    overflow: hidden;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-footer .tabulator-counter {
    float: left;
    line-height: 32px;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-footer .tabulator-paginator {
    float: right;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-footer .tabulator-page {
    background-color: var(--bs-body-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--bs-border-color) !important;
    border-radius: var(--bs-border-radius-sm);
    padding: 4px 10px;
    margin: 0 2px;
    font-size: 0.8125rem;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-footer .tabulator-page.active {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
    border-color: var(--bs-primary) !important;
  }
  .tabulator.tabulator-bootstrap-5 .tabulator-page-size {
    border-radius: var(--bs-border-radius-sm);
    padding: 3px 8px;
    font-size: 0.8125rem;
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
    margin-left: 6px;
    margin-right: 12px;
  }
</style>
<!--end::Tabulator Table CSS-->
