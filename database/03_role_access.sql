-- Hak akses awal per role. Jalankan SETELAH 02_menu_app.sql.
-- SUPER_ADMIN & ADMIN otomatis melihat semua menu (ditangani di aplikasi).
-- Hak akses dapat diubah dari menu Karyawan > Role & Hak Akses.
INSERT IGNORE INTO auth_role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN auth_modules m ON m.status = 1
WHERE r.code = 'STORE_MANAGER' AND m.name NOT IN ('Pengaturan') AND (m.path IS NULL OR m.path NOT LIKE '/settings%');

INSERT IGNORE INTO auth_role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN auth_modules m ON m.status = 1
WHERE r.code = 'CASHIER' AND (m.path IN ('/dashboard','/sales/create','/sales','/customers','/customers/create')
   OR m.name IN ('Penjualan','Pelanggan'));

INSERT IGNORE INTO auth_role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN auth_modules m ON m.status = 1
WHERE r.code = 'INVENTORY' AND (m.path IN ('/dashboard') OR m.path LIKE '/products%' OR m.path LIKE '/inventory%'
   OR m.name IN ('Produk & Jasa','Persediaan'));

INSERT IGNORE INTO auth_role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN auth_modules m ON m.status = 1
WHERE r.code = 'PURCHASING' AND (m.path IN ('/dashboard') OR m.path LIKE '/purchases%' OR m.path LIKE '/suppliers%'
   OR m.name IN ('Pembelian','Supplier'));
