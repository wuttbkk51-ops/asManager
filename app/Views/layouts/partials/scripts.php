    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script src="<?= base_url('plugins/overlayscrollbars/js/overlayscrollbars.browser.es6.min.js') ?>"></script>
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script src="<?= base_url('plugins/popper/popper.min.js') ?>"></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)-->
    <!--begin::Required Plugin(Bootstrap 5)-->
    <script src="<?= base_url('plugins/bootstrap/js/bootstrap.min.js') ?>"></script>
    <!--end::Required Plugin(Bootstrap 5)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <script src="<?= base_url('adminlte/js/adminlte.min.js') ?>"></script>
    <!--end::Required Plugin(AdminLTE)-->
    <!--begin::Tabulator Table JS-->
    <script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>
    <!--end::Tabulator Table JS-->
    <!--begin::JsBarcode JS (For Barcode & Label Generation)-->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <!--end::JsBarcode JS-->

    <!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

        // Disable OverlayScrollbars on mobile devices to prevent touch interference
        const isMobile = window.innerWidth <= 992;

        if (
          sidebarWrapper &&
          OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
          !isMobile
        ) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <!--end::OverlayScrollbars Configure-->

    <!--begin::Charts follow the colour mode-->
    <script>
      // ApexCharts draws light-theme tooltips and axis text unless told otherwise,
      // which is unreadable in dark mode (#6105). Give it the page's colour mode as
      // a global default before any chart is created — this runs before the chart
      // pages' own scripts — and keep every chart that has a `chart.id` in step
      // when the mode changes (ColorMode, the OS in auto mode, or your own code).
      (() => {
        'use strict';
        const mode = () =>
          document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        // `Apex` is ApexCharts' global-options object; it must exist before the library loads.
        // theme.mode also sets a dark chart background — keep the card's instead.
        // eslint-disable-next-line unicorn/no-global-object-property-assignment
        globalThis.Apex ||= {};
        const apex = globalThis.Apex;
        apex.theme = { mode: mode() };
        apex.chart = Object.assign(apex.chart || {}, { background: 'transparent' });
        new MutationObserver(() => {
          const next = mode();
          apex.theme = { mode: next };
          const instances = apex._chartInstances || [];
          for (const { chart } of instances) {
            chart.updateOptions({ theme: { mode: next } }, false, false);
          }
        }).observe(document.documentElement, {
          attributes: true,
          attributeFilter: ['data-bs-theme'],
        });
      })();
    </script>
    <!--end::Charts follow the colour mode-->

    <!--end::Script-->
