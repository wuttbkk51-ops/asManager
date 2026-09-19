<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =========================================================================
// 1. Public Pages (Controllers/*)
// =========================================================================
$routes->get('/', 'Home::index');
$routes->get('starter', 'Home::starter');
$routes->get('example', 'Home::example');

// Language Switcher Routes
$routes->get('lang/(:segment)', 'LanguageController::switch/$1');
$routes->get('language/switch/(:segment)', 'LanguageController::switch/$1');

// Authentication Routes
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->get('logout', 'Auth::logout');
$routes->get('impersonate/(:num)', 'Auth::impersonate/$1');
$routes->get('stop-impersonate', 'Auth::stopImpersonating');

// =========================================================================
// 2. Private Pages - Dashboard / Admin Area (Controllers/Admin/*)
// =========================================================================
// Dashboard Group: /dashboard/settings
$routes->group('dashboard', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->post('save-layout', 'Dashboard::saveLayout');
    $routes->post('reset-layout', 'Dashboard::resetLayout');

    // Settings Routes
    $routes->get('settings', 'Settings::index');
    $routes->get('setting', 'Settings::index'); // alias backward-compatibility
    $routes->post('settings/general', 'Settings::updateGeneral');
    $routes->post('settings/repair', 'Settings::updateRepair');
    $routes->post('settings/sales', 'Settings::updateSales');
    $routes->post('settings/stock', 'Settings::updateStock');
    $routes->post('settings/shop', 'Settings::updateShop');
    $routes->post('settings/workflow', 'Settings::updateWorkflow');
    $routes->post('settings/costing', 'Settings::updateCosting');
    $routes->post('settings/print', 'Settings::updatePrint');
});

// Profile Routes
$routes->group('profile', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->post('update', 'ProfileController::updateProfile');
    $routes->post('password', 'ProfileController::updatePassword');
    $routes->post('settings', 'ProfileController::updateSettings');
});

// Admin Alias Group (รองรับทั้ง /admin และ /admin/settings)
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');
    $routes->post('dashboard/save-layout', 'Dashboard::saveLayout');
    $routes->post('dashboard/reset-layout', 'Dashboard::resetLayout');
    $routes->get('settings', 'Settings::index');
    $routes->get('setting', 'Settings::index');
    $routes->post('settings/general', 'Settings::updateGeneral');
    $routes->post('settings/repair', 'Settings::updateRepair');
    $routes->post('settings/sales', 'Settings::updateSales');
    $routes->post('settings/stock', 'Settings::updateStock');
    $routes->post('settings/shop', 'Settings::updateShop');
    $routes->post('settings/workflow', 'Settings::updateWorkflow');
    $routes->post('settings/costing', 'Settings::updateCosting');
    $routes->post('settings/print', 'Settings::updatePrint');
    $routes->get('settings/wizard', 'SettingsController::wizard');
    $routes->post('settings/wizard', 'SettingsController::saveWizard');
    $routes->get('inventory/adjustments', 'InventoryController::adjustments');
    $routes->get('profile', 'ProfileController::index');
    $routes->post('profile/update', 'ProfileController::updateProfile');
    $routes->post('profile/password', 'ProfileController::updatePassword');
    $routes->post('profile/settings', 'ProfileController::updateSettings');

    // Products & Inventory CRUD (Tabulator Table + Reusable UI)
    $routes->get('products', 'ProductController::index');
    $routes->get('products/data', 'ProductController::listData');
    $routes->get('products/get/(:num)', 'ProductController::getItem/$1');
    $routes->post('products/store', 'ProductController::store');
    $routes->post('products/update/(:num)', 'ProductController::update/$1');
    $routes->post('products/delete/(:num)', 'ProductController::delete/$1');

    // Serial Number Management Hub (Batch Gen, Scanner Inbound, Timeline, Print)
    $routes->get('serials', 'SerialController::index');
    $routes->get('serials/data', 'SerialController::listData');
    $routes->post('serials/generate', 'SerialController::generate');
    $routes->post('serials/inbound', 'SerialController::inbound');
    $routes->get('serials/history/(:num)', 'SerialController::history/$1');
    $routes->post('serials/status/(:num)', 'SerialController::changeStatus/$1');
});

// Top-level aliases
$routes->get('products', 'Admin\ProductController::index');
$routes->get('products/data', 'Admin\ProductController::listData');
$routes->get('serials', 'Admin\SerialController::index');
