START TRANSACTION;

SET @now = NOW();


-- =========================================================
-- DASHBOARD
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Dashboard', 'fa-solid fa-gauge-high', '/dashboard', 1, 0, 1, @now, @now);

SET @dashboard_id = LAST_INSERT_ID();


-- =========================================================
-- PENJUALAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Penjualan', 'fa-solid fa-cart-shopping', NULL, 2, 0, 1, @now, @now);

SET @sales_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@sales_id, 'Kasir', 'fa-solid fa-cash-register', '/sales/create', 1, 1, 1, @now, @now),
(@sales_id, 'Daftar Penjualan', 'fa-solid fa-receipt', '/sales', 2, 1, 1, @now, @now),
(@sales_id, 'Retur Penjualan', 'fa-solid fa-arrow-rotate-left', '/sales/returns', 3, 1, 1, @now, @now);


-- =========================================================
-- PEMBELIAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Pembelian', 'fa-solid fa-bag-shopping', NULL, 3, 0, 1, @now, @now);

SET @purchases_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@purchases_id, 'Pembelian Baru', 'fa-solid fa-plus', '/purchases/create', 1, 1, 1, @now, @now),
(@purchases_id, 'Daftar Pembelian', 'fa-solid fa-file-invoice', '/purchases', 2, 1, 1, @now, @now),
(@purchases_id, 'Retur Pembelian', 'fa-solid fa-arrow-rotate-left', '/purchases/returns', 3, 1, 1, @now, @now);


-- =========================================================
-- PRODUK & JASA
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Produk & Jasa', 'fa-solid fa-box-open', NULL, 4, 0, 1, @now, @now);

SET @products_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@products_id, 'Produk', 'fa-solid fa-box', '/products', 1, 1, 1, @now, @now),
(@products_id, 'Tambah Produk', 'fa-solid fa-square-plus', '/products/create', 2, 1, 1, @now, @now),
(@products_id, 'Kategori', 'fa-solid fa-tags', '/products/categories', 3, 1, 1, @now, @now),
(@products_id, 'Satuan', 'fa-solid fa-ruler', '/products/units', 4, 1, 1, @now, @now);


-- =========================================================
-- PERSEDIAAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Persediaan', 'fa-solid fa-warehouse', NULL, 5, 0, 1, @now, @now);

SET @inventory_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@inventory_id, 'Stok', 'fa-solid fa-boxes-stacked', '/inventory', 1, 1, 1, @now, @now),
(@inventory_id, 'Pergerakan Stok', 'fa-solid fa-arrow-right-arrow-left', '/inventory/movements', 2, 1, 1, @now, @now),
(@inventory_id, 'Penyesuaian Stok', 'fa-solid fa-sliders', '/inventory/adjustments', 3, 1, 1, @now, @now),
(@inventory_id, 'Stock Opname', 'fa-solid fa-clipboard-check', '/inventory/opname', 4, 1, 1, @now, @now),
(@inventory_id, 'Transfer Stok', 'fa-solid fa-right-left', '/inventory/transfers', 5, 1, 1, @now, @now);


-- =========================================================
-- PELANGGAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Pelanggan', 'fa-solid fa-users', NULL, 6, 0, 1, @now, @now);

SET @customers_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@customers_id, 'Daftar Pelanggan', 'fa-solid fa-user-group', '/customers', 1, 1, 1, @now, @now),
(@customers_id, 'Tambah Pelanggan', 'fa-solid fa-user-plus', '/customers/create', 2, 1, 1, @now, @now),
(@customers_id, 'Membership', 'fa-solid fa-id-card', '/customers/memberships', 3, 1, 1, @now, @now),
(@customers_id, 'Poin Pelanggan', 'fa-solid fa-star', '/customers/points', 4, 1, 1, @now, @now);


-- =========================================================
-- SUPPLIER
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Supplier', 'fa-solid fa-truck', NULL, 7, 0, 1, @now, @now);

SET @suppliers_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@suppliers_id, 'Daftar Supplier', 'fa-solid fa-address-book', '/suppliers', 1, 1, 1, @now, @now),
(@suppliers_id, 'Tambah Supplier', 'fa-solid fa-user-plus', '/suppliers/create', 2, 1, 1, @now, @now);


-- =========================================================
-- LAYANAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Layanan', 'fa-solid fa-hand-holding-heart', NULL, 8, 0, 1, @now, @now);

SET @services_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@services_id, 'Appointment', 'fa-solid fa-calendar-days', '/appointments', 1, 1, 1, @now, @now),
(@services_id, 'Service Order', 'fa-solid fa-clipboard-list', '/service-orders', 2, 1, 1, @now, @now),
(@services_id, 'Membership', 'fa-solid fa-id-badge', '/memberships', 3, 1, 1, @now, @now);


-- =========================================================
-- PENGELUARAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Pengeluaran', 'fa-solid fa-money-bill-transfer', NULL, 9, 0, 1, @now, @now);

SET @expenses_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@expenses_id, 'Daftar Pengeluaran', 'fa-solid fa-receipt', '/expenses', 1, 1, 1, @now, @now),
(@expenses_id, 'Pengeluaran Baru', 'fa-solid fa-circle-plus', '/expenses/create', 2, 1, 1, @now, @now),
(@expenses_id, 'Kategori Pengeluaran', 'fa-solid fa-tags', '/expenses/categories', 3, 1, 1, @now, @now);


-- =========================================================
-- LAPORAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Laporan', 'fa-solid fa-chart-column', NULL, 10, 0, 1, @now, @now);

SET @reports_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@reports_id, 'Laporan Penjualan', 'fa-solid fa-chart-line', '/reports/sales', 1, 1, 1, @now, @now),
(@reports_id, 'Laporan Pembelian', 'fa-solid fa-cart-flatbed', '/reports/purchases', 2, 1, 1, @now, @now),
(@reports_id, 'Laporan Persediaan', 'fa-solid fa-boxes-stacked', '/reports/inventory', 3, 1, 1, @now, @now),
(@reports_id, 'Laba Rugi', 'fa-solid fa-chart-pie', '/reports/profit-loss', 4, 1, 1, @now, @now),
(@reports_id, 'Laporan Pengeluaran', 'fa-solid fa-money-bill-wave', '/reports/expenses', 5, 1, 1, @now, @now),
(@reports_id, 'Laporan Pelanggan', 'fa-solid fa-users', '/reports/customers', 6, 1, 1, @now, @now);


-- =========================================================
-- KEUANGAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Keuangan', 'fa-solid fa-landmark', NULL, 11, 0, 1, @now, @now);

SET @finance_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@finance_id, 'Kas & Bank', 'fa-solid fa-wallet', '/finance/cash', 1, 1, 1, @now, @now),
(@finance_id, 'Piutang', 'fa-solid fa-hand-holding-dollar', '/finance/receivables', 2, 1, 1, @now, @now),
(@finance_id, 'Hutang', 'fa-solid fa-money-check-dollar', '/finance/payables', 3, 1, 1, @now, @now),
(@finance_id, 'Jurnal', 'fa-solid fa-book-open', '/finance/journal', 4, 1, 1, @now, @now);


-- =========================================================
-- KARYAWAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Karyawan', 'fa-solid fa-user-tie', NULL, 12, 0, 1, @now, @now);

SET @employees_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@employees_id, 'Daftar Karyawan', 'fa-solid fa-users', '/employees', 1, 1, 1, @now, @now),
(@employees_id, 'Role & Hak Akses', 'fa-solid fa-user-shield', '/employees/roles', 2, 1, 1, @now, @now),
(@employees_id, 'Komisi', 'fa-solid fa-money-bill-1-wave', '/employees/commission', 3, 1, 1, @now, @now);


-- =========================================================
-- OUTLET
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Outlet', 'fa-solid fa-store', NULL, 13, 0, 1, @now, @now);

SET @outlets_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@outlets_id, 'Daftar Outlet', 'fa-solid fa-store', '/outlets', 1, 1, 1, @now, @now),
(@outlets_id, 'Tambah Outlet', 'fa-solid fa-square-plus', '/outlets/create', 2, 1, 1, @now, @now);


-- =========================================================
-- PENGATURAN
-- =========================================================

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(NULL, 'Pengaturan', 'fa-solid fa-gear', NULL, 14, 0, 1, @now, @now);

SET @settings_id = LAST_INSERT_ID();

INSERT INTO auth_modules
(parent_id, name, icon, path, sort, level, status, created_at, updated_at)
VALUES
(@settings_id, 'Profil Bisnis', 'fa-solid fa-building', '/settings/business', 1, 1, 1, @now, @now),
(@settings_id, 'Pengaturan Outlet', 'fa-solid fa-store', '/settings/outlet', 2, 1, 1, @now, @now),
(@settings_id, 'Metode Pembayaran', 'fa-solid fa-credit-card', '/settings/payment', 3, 1, 1, @now, @now),
(@settings_id, 'Pajak', 'fa-solid fa-percent', '/settings/tax', 4, 1, 1, @now, @now),
(@settings_id, 'Penomoran Dokumen', 'fa-solid fa-hashtag', '/settings/numbering', 5, 1, 1, @now, @now),
(@settings_id, 'Audit Log', 'fa-solid fa-clock-rotate-left', '/settings/audit-log', 6, 1, 1, @now, @now);


COMMIT;
