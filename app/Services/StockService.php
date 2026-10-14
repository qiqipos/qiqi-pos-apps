<?php

namespace App\Services;

/**
 * Satu-satunya pintu perubahan stok: memperbarui tabel inventory dan mencatat
 * buku besar stok (inventory_transactions) secara konsisten.
 * Kuantitas pada ledger bertanda: positif = masuk, negatif = keluar.
 * Wajib dipanggil di dalam transaksi database.
 */
class StockService
{
    public static function increase(int $storeId, int $productId, float $qty, string $type, ?string $refType = null, ?int $refId = null, ?string $notes = null): void
    {
        $db = db_connect();
        $db->query(
            'INSERT INTO inventory (store_id, product_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)',
            [$storeId, $productId, $qty]
        );
        self::ledger($storeId, $productId, $type, $qty, $refType, $refId, $notes);
    }

    /** @throws \DomainException bila stok tidak cukup */
    public static function decrease(int $storeId, int $productId, float $qty, string $type, ?string $refType = null, ?int $refId = null, ?string $notes = null): void
    {
        $db = db_connect();
        // UPDATE bersyarat bersifat atomik: dua kasir tidak bisa menjual stok yang sama.
        $db->query(
            'UPDATE inventory SET quantity = quantity - ? WHERE store_id = ? AND product_id = ? AND quantity >= ?',
            [$qty, $storeId, $productId, $qty]
        );
        if ($db->affectedRows() === 0) {
            $nama = $db->table('products')->select('name')->where('id', $productId)->get()->getRow('name') ?? "#{$productId}";
            throw new \DomainException("Stok \"{$nama}\" tidak mencukupi.");
        }
        self::ledger($storeId, $productId, $type, -$qty, $refType, $refId, $notes);
    }

    private static function ledger(int $storeId, int $productId, string $type, float $signedQty, ?string $refType, ?int $refId, ?string $notes): void
    {
        db_connect()->table('inventory_transactions')->insert([
            'store_id' => $storeId, 'product_id' => $productId, 'transaction_type' => $type,
            'quantity' => $signedQty, 'reference_type' => $refType, 'reference_id' => $refId,
            'notes' => $notes, 'created_by' => current_user('id'),
        ]);
    }
}
