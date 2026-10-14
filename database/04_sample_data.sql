-- DATA CONTOH (opsional) untuk mencoba aplikasi.
INSERT INTO brands (name) VALUES ('Umum');
INSERT INTO suppliers (code, name, contact_person, phone) VALUES ('SUP001','PT Sumber Makmur','Budi','081200000001'),('SUP002','CV Jaya Abadi','Sari','081200000002');
INSERT INTO customers (customer_code, name, phone) VALUES ('CUST001','Pelanggan Contoh','081300000001');
INSERT INTO products (sku, barcode, name, category_id, brand_id, unit, cost_price, selling_price, tax_rate, min_stock) VALUES
 ('SKU001','8991001000011','Beras Premium 5 Kg',1,1,'PAK',60000,68000,0,5),
 ('SKU002','8991001000028','Minyak Goreng 1 Liter',1,1,'LTR',14000,17000,0,10),
 ('SKU003','8991001000035','Gula Pasir 1 Kg',1,1,'KG',13000,16000,0,10),
 ('SKU004','8991001000042','Air Mineral 600 ml',2,1,'BTL',2000,3500,11,24),
 ('SKU005','8991001000059','Teh Botol 450 ml',2,1,'BTL',3500,5500,11,24),
 ('SKU006','8991001000066','Kopi Sachet',2,1,'PCS',1200,2000,11,50),
 ('SKU007','8991001000073','Keripik Kentang',3,1,'PCS',7000,10000,11,12),
 ('SKU008','8991001000080','Biskuit Cokelat',3,1,'PCS',5000,7500,11,12),
 ('SKU009','8991001000097','Sabun Mandi',4,1,'PCS',3000,4500,11,12),
 ('SKU010','8991001000103','Sampo Sachet',4,1,'PCS',500,1000,11,50),
 ('SKU011','8991001000110','Deterjen 800 gr',5,1,'PCS',16000,21000,11,6),
 ('SKU012','8991001000127','Tisu Gulung',5,1,'PCS',6000,9000,11,10);
INSERT INTO inventory (store_id, product_id, quantity) SELECT 1, id, 40 FROM products;
INSERT INTO inventory_transactions (store_id, product_id, transaction_type, quantity, reference_type, notes)
SELECT 1, id, 'STOCK_OPNAME', 40, 'SEED', 'Stok awal data contoh' FROM products;
