# Qiqi POS (CodeIgniter 4 + MySQL + AdminLTE 4)

Aplikasi Point of Sales untuk UMK. Antarmuka Bahasa Indonesia.
Pustaka frontend: jQuery DataTables (server-side AJAX/REST), Select2, Toastr, SweetAlert2, Chart.js.

## Instalasi
```bash
composer install                      # PHP >= 8.2, ext intl, mbstring, mysqli
cp .env.example .env                  # lalu isi kredensial database & app.baseURL
mysql -e "CREATE DATABASE pos_umk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cd database
mysql pos_umk < 01_schema.sql         # skema (konversi PostgreSQL -> MySQL) + data awal
mysql pos_umk < 02_menu_app.sql       # menu (menu_app.sql asli, tidak diubah)
mysql pos_umk < 03_role_access.sql    # hak akses awal tiap role
mysql pos_umk < 04_sample_data.sql    # (opsional) 12 produk contoh + stok
cd .. && php spark serve              # http://localhost:8080
```
Login awal: **admin / admin123** — segera ganti dari menu Karyawan.
Untuk produksi: set `CI_ENVIRONMENT = production`, arahkan web server ke folder `public/`.

## Catatan penting tentang berkas yang Anda unggah
- `db_schema.sql` berformat **PostgreSQL**, sehingga dikonversi ke MySQL (`01_schema.sql`).
  Tambahan: `auth_modules`, `auth_role_modules` (dibutuhkan `menu_app.sql`), `units`, `expenses`,
  `expense_categories`, `doc_sequences`, serta kolom `paid_amount`, `change_amount`, `cost_price` (snapshot HPP) dan `created_by`.

## Modul yang sudah berfungsi
Login/logout (throttle 5x/menit, session regenerate), Dashboard (kartu + 3 grafik), Kasir/POS (barcode, diskon, pajak,
tunai/QRIS/transfer/kartu/e-wallet, kembalian, struk cetak), Daftar Penjualan (detail, pembatalan + stok kembali),
Pembelian (buat, terima barang, batalkan), Produk/Kategori/Satuan, Pelanggan (+poin), Supplier, Persediaan (stok,
pergerakan, penyesuaian), Pengeluaran + kategori, Laporan (penjualan, pembelian, persediaan, laba rugi, pengeluaran,
pelanggan), Karyawan, Role & Hak Akses (editor menu per role), Outlet, Audit Log.

## Belum dibangun (menu tampil sebagai halaman "sedang dalam pengembangan")
Retur penjualan/pembelian, stock opname, transfer stok, membership & poin (UI), Layanan (appointment/service order),
Keuangan (kas, piutang, hutang, jurnal), komisi, dan halaman Pengaturan selain Audit Log.
Fitur outlet-ganda: data per outlet mengikuti outlet milik user yang login.

## Arsitektur singkat
- `Controllers/CrudController` — basis CRUD + endpoint REST DataTables; master data cukup mendefinisikan kolom & field.
- `Services/` — logika bisnis transaksional (`SaleService`, `PurchaseService`, `StockService`, `DocNumberService`, `ReportService`).
- `Libraries/DataTable` — pemroses server-side DataTables dengan whitelist kolom (anti SQL injection pada sort/search).
- Harga, pajak, dan total **selalu dihitung ulang di server**; stok dikurangi dengan `UPDATE ... WHERE quantity >= n` (atomik).
- Filter `auth` + `permission` (hak akses role -> modul), CSRF aktif (header `X-CSRF-TOKEN` otomatis dari `app.js`).
