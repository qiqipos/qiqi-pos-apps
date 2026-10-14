<?php

namespace App\Services;

/** Logika pembelian dari supplier: buat PO, terima barang, batalkan. */
class PurchaseService
{
    public static function create(array $in, int $storeId, int $userId): array
    {
        $db = db_connect();
        if (! $db->table('suppliers')->where(['id' => (int) ($in['supplier_id'] ?? 0), 'is_active' => 1])->countAllResults()) {
            throw new \DomainException('Pilih supplier yang valid.');
        }
        $gabung = [];
        foreach ((array) ($in['items'] ?? []) as $i) {
            $qty = round((float) ($i['quantity'] ?? 0), 3);
            if ($qty > 0) {
                $pid = (int) $i['product_id'];
                $gabung[$pid] = ['qty' => ($gabung[$pid]['qty'] ?? 0) + $qty, 'cost' => max(0, round((float) ($i['unit_cost'] ?? 0), 2))];
            }
        }
        if ($gabung === []) {
            throw new \DomainException('Tambahkan minimal satu produk.');
        }
        $ada = $db->table('products')->select('id')->whereIn('id', array_keys($gabung))->get()->getResultArray();
        if (count($ada) !== count($gabung)) {
            throw new \DomainException('Ada produk yang tidak ditemukan.');
        }

        $total = 0.0;
        foreach ($gabung as $g) {
            $total += round($g['qty'] * $g['cost'], 2);
        }

        $db->transBegin();
        try {
            $po = DocNumberService::next('PO');
            $db->table('purchase_orders')->insert([
                'po_number' => $po, 'store_id' => $storeId, 'supplier_id' => (int) $in['supplier_id'],
                'order_date' => date('Y-m-d H:i:s'), 'status' => 'ORDERED',
                'subtotal' => $total, 'tax_amount' => 0, 'total_amount' => $total,
                'notes' => mb_substr(trim((string) ($in['notes'] ?? '')), 0, 255) ?: null, 'created_by' => $userId,
            ]);
            $poId = (int) $db->insertID();
            foreach ($gabung as $pid => $g) {
                $db->table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId, 'product_id' => $pid, 'quantity' => $g['qty'],
                    'unit_cost' => $g['cost'], 'subtotal' => round($g['qty'] * $g['cost'], 2),
                ]);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        AuditService::log('CREATE', 'purchase_orders', $poId, null, ['po' => $po, 'total' => $total]);

        return ['id' => $poId, 'po_number' => $po, 'total' => $total];
    }

    /** Terima seluruh sisa barang pada PO: stok masuk dan harga beli produk diperbarui. */
    public static function receive(int $poId): void
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $po = $db->query('SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE', [$poId])->getRowArray();
            if (! $po) {
                throw new \DomainException('Pembelian tidak ditemukan.');
            }
            if (! in_array($po['status'], ['ORDERED', 'PARTIAL'], true)) {
                throw new \DomainException('Pembelian ini tidak dapat diterima (status: ' . status_label($po['status']) . ').');
            }
            foreach ($db->table('purchase_order_items')->where('purchase_order_id', $poId)->orderBy('product_id')->get()->getResultArray() as $it) {
                $sisa = (float) $it['quantity'] - (float) $it['received_quantity'];
                if ($sisa > 0) {
                    StockService::increase((int) $po['store_id'], (int) $it['product_id'], $sisa, 'PURCHASE', 'purchase_orders', $poId, $po['po_number']);
                    $db->table('purchase_order_items')->where('id', $it['id'])->update(['received_quantity' => $it['quantity']]);
                    $db->table('products')->where('id', $it['product_id'])->update(['cost_price' => $it['unit_cost']]);
                }
            }
            $db->table('purchase_orders')->where('id', $poId)->update(['status' => 'RECEIVED']);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        AuditService::log('RECEIVE', 'purchase_orders', $poId, ['status' => $po['status']], ['status' => 'RECEIVED']);
    }

    public static function cancel(int $poId): void
    {
        $db = db_connect();
        $po = $db->table('purchase_orders')->where('id', $poId)->get()->getRowArray();
        if (! $po) {
            throw new \DomainException('Pembelian tidak ditemukan.');
        }
        if ($po['status'] !== 'ORDERED') {
            throw new \DomainException('Hanya pembelian berstatus Dipesan yang dapat dibatalkan.');
        }
        $db->table('purchase_orders')->where('id', $poId)->where('status', 'ORDERED')->update(['status' => 'CANCELLED']);
        AuditService::log('CANCEL', 'purchase_orders', $poId, ['status' => 'ORDERED'], ['status' => 'CANCELLED']);
    }
}
