-- ============================================================
-- Qiqi POS - MYSQL 8 / MARIADB 10.5+ (dikonversi dari db_schema.sql PostgreSQL)
-- Urutan import: 01_schema.sql -> 02_menu_app.sql -> 03_role_access.sql -> 04_sample_data.sql (opsional)
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS stores (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL UNIQUE,
    name        VARCHAR(150) NOT NULL,
    address     TEXT NULL,
    phone       VARCHAR(30) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id   BIGINT UNSIGNED NULL,
    code        VARCHAR(30) NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brands (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TAMBAHAN: master satuan (menu Produk & Jasa > Satuan)
CREATE TABLE IF NOT EXISTS units (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sku           VARCHAR(50)  NOT NULL UNIQUE,
    barcode       VARCHAR(100) NULL UNIQUE,
    name          VARCHAR(200) NOT NULL,
    category_id   BIGINT UNSIGNED NULL,
    brand_id      BIGINT UNSIGNED NULL,
    unit          VARCHAR(20) NOT NULL DEFAULT 'PCS',
    cost_price    DECIMAL(14,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_rate      DECIMAL(5,2)  NOT NULL DEFAULT 0,
    min_stock     DECIMAL(14,3) NOT NULL DEFAULT 0,
    max_stock     DECIMAL(14,3) NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
    CONSTRAINT chk_product_cost  CHECK (cost_price >= 0),
    CONSTRAINT chk_product_price CHECK (selling_price >= 0),
    CONSTRAINT chk_product_tax   CHECK (tax_rate >= 0 AND tax_rate <= 100),
    INDEX idx_products_name (name),
    INDEX idx_products_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    store_id          BIGINT UNSIGNED NOT NULL,
    product_id        BIGINT UNSIGNED NOT NULL,
    quantity          DECIMAL(14,3) NOT NULL DEFAULT 0,
    reserved_quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory (store_id, product_id),
    CONSTRAINT fk_inventory_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_inventory_quantity CHECK (quantity >= 0),
    CONSTRAINT chk_reserved_quantity  CHECK (reserved_quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    store_id         BIGINT UNSIGNED NOT NULL,
    product_id       BIGINT UNSIGNED NOT NULL,
    transaction_type VARCHAR(30) NOT NULL,
    quantity         DECIMAL(14,3) NOT NULL,
    reference_type   VARCHAR(50) NULL,
    reference_id     BIGINT UNSIGNED NULL,
    notes            TEXT NULL,
    created_by       BIGINT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invtx_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_invtx_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_inventory_transaction_type CHECK (transaction_type IN
        ('PURCHASE','SALE','SALE_RETURN','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT_IN','ADJUSTMENT_OUT','STOCK_OPNAME')),
    INDEX idx_invtx_store (store_id), INDEX idx_invtx_product (product_id), INDEX idx_invtx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_code  VARCHAR(30) NULL UNIQUE,
    name           VARCHAR(150) NOT NULL,
    phone          VARCHAR(30) NULL,
    email          VARCHAR(150) NULL,
    loyalty_points INT NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(30) NOT NULL UNIQUE,
    name           VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone          VARCHAR(30) NULL,
    email          VARCHAR(150) NULL,
    address        TEXT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code  VARCHAR(50)  NOT NULL UNIQUE,
    name  VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    role_id       BIGINT UNSIGNED NOT NULL,
    store_id      BIGINT UNSIGNED NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registers (
    id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    store_id  BIGINT UNSIGNED NOT NULL,
    code      VARCHAR(30) NOT NULL,
    name      VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_register (store_id, code),
    CONSTRAINT fk_registers_store FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS register_sessions (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    register_id  BIGINT UNSIGNED NOT NULL,
    cashier_id   BIGINT UNSIGNED NOT NULL,
    opened_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at    DATETIME NULL,
    opening_cash DECIMAL(14,2) NOT NULL DEFAULT 0,
    closing_cash DECIMAL(14,2) NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'OPEN',
    CONSTRAINT fk_regsess_register FOREIGN KEY (register_id) REFERENCES registers(id),
    CONSTRAINT fk_regsess_cashier FOREIGN KEY (cashier_id) REFERENCES users(id),
    CONSTRAINT chk_register_session_status CHECK (status IN ('OPEN','CLOSED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(50) NOT NULL UNIQUE,
    store_id        BIGINT UNSIGNED NOT NULL,
    register_id     BIGINT UNSIGNED NULL,
    cashier_id      BIGINT UNSIGNED NULL,
    customer_id     BIGINT UNSIGNED NULL,
    sale_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal        DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount      DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount    DECIMAL(14,2) NOT NULL DEFAULT 0,
    paid_amount     DECIMAL(14,2) NOT NULL DEFAULT 0,   -- TAMBAHAN
    change_amount   DECIMAL(14,2) NOT NULL DEFAULT 0,   -- TAMBAHAN
    status          VARCHAR(30) NOT NULL DEFAULT 'COMPLETED',
    notes           VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_sales_register FOREIGN KEY (register_id) REFERENCES registers(id),
    CONSTRAINT fk_sales_cashier FOREIGN KEY (cashier_id) REFERENCES users(id),
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT chk_sale_status CHECK (status IN ('DRAFT','COMPLETED','VOID','RETURNED','PARTIALLY_RETURNED')),
    INDEX idx_sales_store_date (store_id, sale_date), INDEX idx_sales_cashier (cashier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_items (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sale_id         BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    quantity        DECIMAL(14,3) NOT NULL,
    unit_price      DECIMAL(14,2) NOT NULL,
    cost_price      DECIMAL(14,2) NOT NULL DEFAULT 0,   -- TAMBAHAN: snapshot HPP untuk laba rugi
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount      DECIMAL(14,2) NOT NULL DEFAULT 0,
    subtotal        DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount    DECIMAL(14,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_sale_item_quantity CHECK (quantity > 0),
    CONSTRAINT chk_sale_item_price CHECK (unit_price >= 0),
    INDEX idx_sale_items_sale (sale_id), INDEX idx_sale_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sale_id          BIGINT UNSIGNED NOT NULL,
    payment_method   VARCHAR(30) NOT NULL,
    amount           DECIMAL(14,2) NOT NULL,
    reference_number VARCHAR(100) NULL,
    paid_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT chk_payment_method CHECK (payment_method IN ('CASH','CARD','QRIS','BANK_TRANSFER','E_WALLET')),
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    INDEX idx_payments_sale (sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    po_number    VARCHAR(50) NOT NULL UNIQUE,
    store_id     BIGINT UNSIGNED NOT NULL,
    supplier_id  BIGINT UNSIGNED NOT NULL,
    order_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status       VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    subtotal     DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount   DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    notes        VARCHAR(255) NULL,
    created_by   BIGINT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_po_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT chk_po_status CHECK (status IN ('DRAFT','ORDERED','PARTIAL','RECEIVED','CANCELLED')),
    INDEX idx_po_store (store_id), INDEX idx_po_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id        BIGINT UNSIGNED NOT NULL,
    quantity          DECIMAL(14,3) NOT NULL,
    unit_cost         DECIMAL(14,2) NOT NULL,
    received_quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
    subtotal          DECIMAL(14,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_po_item_quantity CHECK (quantity > 0),
    CONSTRAINT chk_po_item_cost CHECK (unit_cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfers (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(50) NOT NULL UNIQUE,
    from_store_id   BIGINT UNSIGNED NOT NULL,
    to_store_id     BIGINT UNSIGNED NOT NULL,
    transfer_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    notes           TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_st_from FOREIGN KEY (from_store_id) REFERENCES stores(id),
    CONSTRAINT fk_st_to FOREIGN KEY (to_store_id) REFERENCES stores(id),
    CONSTRAINT chk_transfer_store CHECK (from_store_id <> to_store_id),
    CONSTRAINT chk_transfer_status CHECK (status IN ('DRAFT','SENT','RECEIVED','CANCELLED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transfer_items (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    transfer_id       BIGINT UNSIGNED NOT NULL,
    product_id        BIGINT UNSIGNED NOT NULL,
    quantity          DECIMAL(14,3) NOT NULL,
    received_quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
    CONSTRAINT fk_sti_transfer FOREIGN KEY (transfer_id) REFERENCES stock_transfers(id) ON DELETE CASCADE,
    CONSTRAINT fk_sti_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_transfer_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotions (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50)  NOT NULL UNIQUE,
    name             VARCHAR(150) NOT NULL,
    promotion_type   VARCHAR(30)  NOT NULL,
    start_at         DATETIME NOT NULL,
    end_at           DATETIME NOT NULL,
    discount_value   DECIMAL(14,2) NOT NULL DEFAULT 0,
    minimum_purchase DECIMAL(14,2) NOT NULL DEFAULT 0,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_promotion_type CHECK (promotion_type IN ('PERCENTAGE','FIXED','BUY_X_GET_Y')),
    CONSTRAINT chk_promotion_date CHECK (end_at > start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotion_products (
    promotion_id BIGINT UNSIGNED NOT NULL,
    product_id   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (promotion_id, product_id),
    CONSTRAINT fk_pp_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NULL,
    action     VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id  BIGINT UNSIGNED NULL,
    old_data   JSON NULL,
    new_data   JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_audit_user (user_id), INDEX idx_audit_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TAMBAHAN UNTUK APLIKASI
-- ============================================================

-- Tabel menu (dipakai oleh menu_app.sql)
CREATE TABLE IF NOT EXISTS auth_modules (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id  BIGINT UNSIGNED NULL,
    name       VARCHAR(100) NOT NULL,
    icon       VARCHAR(100) NULL,
    path       VARCHAR(150) NULL,
    sort       INT NOT NULL DEFAULT 0,
    level      TINYINT NOT NULL DEFAULT 0,
    status     TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_modules_parent FOREIGN KEY (parent_id) REFERENCES auth_modules(id) ON DELETE CASCADE,
    INDEX idx_modules_parent (parent_id, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hak akses role -> modul
CREATE TABLE IF NOT EXISTS auth_role_modules (
    role_id   BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, module_id),
    CONSTRAINT fk_arm_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_arm_module FOREIGN KEY (module_id) REFERENCES auth_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penomoran dokumen atomik (INV-20261014-0001)
CREATE TABLE IF NOT EXISTS doc_sequences (
    doc_type    VARCHAR(20) NOT NULL,
    period      VARCHAR(8)  NOT NULL,
    last_number INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (doc_type, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expense_categories (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenses (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    store_id       BIGINT UNSIGNED NOT NULL,
    category_id    BIGINT UNSIGNED NOT NULL,
    expense_date   DATE NOT NULL,
    description    VARCHAR(255) NOT NULL,
    amount         DECIMAL(14,2) NOT NULL,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'CASH',
    reference_number VARCHAR(100) NULL,
    created_by     BIGINT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_exp_store FOREIGN KEY (store_id) REFERENCES stores(id),
    CONSTRAINT fk_exp_category FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    CONSTRAINT chk_expense_amount CHECK (amount > 0),
    INDEX idx_expenses_date (store_id, expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL
-- ============================================================
INSERT INTO roles (code, name) VALUES
 ('SUPER_ADMIN','Super Administrator'),('ADMIN','Administrator'),('STORE_MANAGER','Manajer Toko'),
 ('CASHIER','Kasir'),('INVENTORY','Staf Gudang'),('PURCHASING','Staf Pembelian');

INSERT INTO stores (code, name, address, phone) VALUES ('STORE001','Toko Utama','Alamat Toko Utama','0000000000');
INSERT INTO registers (store_id, code, name) VALUES (1,'KASIR01','Kasir 1');

INSERT INTO categories (code, name) VALUES
 ('FOOD','Makanan'),('DRINK','Minuman'),('SNACK','Camilan'),('PERSONAL','Perawatan Diri'),
 ('HOUSEHOLD','Rumah Tangga'),('TOBACCO','Rokok'),('OTHER','Lainnya');

INSERT INTO units (code, name) VALUES
 ('PCS','Pieces'),('BOX','Boks'),('PAK','Pak'),('KG','Kilogram'),('GR','Gram'),('LTR','Liter'),('BTL','Botol'),('LSN','Lusin');

INSERT INTO expense_categories (name) VALUES
 ('Listrik'),('Internet'),('Sewa'),('Transportasi'),('ATK'),('Gaji'),('Perawatan'),('Operasional Lainnya');

-- Akun awal: username admin / password admin123 (SEGERA GANTI setelah login pertama)
INSERT INTO users (username, password_hash, full_name, role_id, store_id)
VALUES ('admin', '$2y$10$.KZtDclnGtmYwYqF2XJEaOWoMczFkonX25Tcdbwo4vAjra8nn.5nu', 'Administrator', 1, 1);
