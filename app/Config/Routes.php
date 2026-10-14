<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --- Autentikasi & dasar -------------------------------------------------
$routes->get('/', static fn () => redirect()->to('/dashboard'));
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::attempt');
$routes->post('logout', 'Login::logout');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/data', 'Dashboard::data');
$routes->get('lookup/(:segment)', 'Lookup::search/$1');

// --- Helper pendaftar route -----------------------------------------------
/** CRUD penuh: daftar, datatable (REST), detail, tambah, ubah, hapus. */
$crud = static function (RouteCollection $r, string $path, string $ctl): void {
    $r->get($path, "{$ctl}::index");
    $r->get("{$path}/create", "{$ctl}::create");
    $r->get("{$path}/datatable", "{$ctl}::datatable");
    $r->get("{$path}/(:num)", "{$ctl}::show/$1");
    $r->post($path, "{$ctl}::store");
    $r->put("{$path}/(:num)", "{$ctl}::update/$1");
    $r->delete("{$path}/(:num)", "{$ctl}::delete/$1");
};
/** Daftar baca-saja: halaman + endpoint datatable. */
$list = static function (RouteCollection $r, string $path, string $ctl): void {
    $r->get($path, "{$ctl}::index");
    $r->get("{$path}/datatable", "{$ctl}::datatable");
};

// --- Master data -----------------------------------------------------------
$crud($routes, 'products/categories', 'Categories');
$crud($routes, 'products/units', 'Units');
$crud($routes, 'products', 'Products');
$crud($routes, 'customers', 'Customers');
$crud($routes, 'suppliers', 'Suppliers');
$crud($routes, 'outlets', 'Outlets');
$crud($routes, 'expenses/categories', 'ExpenseCategories');
$crud($routes, 'expenses', 'Expenses');

// --- Karyawan & hak akses --------------------------------------------------
$list($routes, 'employees/roles', 'Roles');
$routes->get('employees/roles/(:num)/access', 'Roles::access/$1');
$routes->put('employees/roles/(:num)/access', 'Roles::saveAccess/$1');
$crud($routes, 'employees', 'Employees');

// --- Penjualan -------------------------------------------------------------
$list($routes, 'sales', 'Sales');
$routes->get('sales/create', 'Sales::pos');
$routes->get('sales/products', 'Sales::products');
$routes->post('sales', 'Sales::store');
$routes->get('sales/(:num)/detail', 'Sales::detail/$1');
$routes->get('sales/(:num)/receipt', 'Sales::receipt/$1');
$routes->post('sales/(:num)/void', 'Sales::void/$1');

// --- Pembelian -------------------------------------------------------------
$list($routes, 'purchases', 'Purchases');
$routes->get('purchases/create', 'Purchases::form');
$routes->post('purchases', 'Purchases::store');
$routes->get('purchases/(:num)/detail', 'Purchases::detail/$1');
$routes->post('purchases/(:num)/receive', 'Purchases::receive/$1');
$routes->post('purchases/(:num)/cancel', 'Purchases::cancel/$1');

// --- Persediaan ------------------------------------------------------------
$list($routes, 'inventory/movements', 'InventoryMovements');
$routes->get('inventory/adjustments', 'InventoryAdjustments::index');
$routes->get('inventory/adjustments/create', 'InventoryAdjustments::create');
$routes->get('inventory/adjustments/datatable', 'InventoryAdjustments::datatable');
$routes->post('inventory/adjustments', 'InventoryAdjustments::store');
$list($routes, 'inventory', 'Inventory');

// --- Laporan ---------------------------------------------------------------
$routes->get('reports/(:segment)/data', 'Reports::data/$1');
$routes->get('reports/(:segment)', 'Reports::page/$1');

// --- Pengaturan ------------------------------------------------------------
$list($routes, 'settings/audit-log', 'AuditLogs');

// --- Menu yang modulnya belum dibangun (lihat README) ---------------------------
foreach ([
    'sales/returns', 'purchases/returns', 'inventory/opname', 'inventory/transfers',
    'customers/memberships', 'customers/points', 'appointments', 'service-orders', 'memberships',
    'finance/cash', 'finance/receivables', 'finance/payables', 'finance/journal',
    'employees/commission', 'settings/business', 'settings/outlet', 'settings/payment',
    'settings/tax', 'settings/numbering',
] as $path) {
    $routes->get($path, 'Placeholder::index');
}
