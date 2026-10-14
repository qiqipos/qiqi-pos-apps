<?php

namespace App\Services;

/** Logika transaksi penjualan. Semua harga dihitung ulang dari database (klien tidak dipercaya). */
class SaleService
{
    private const POINT_PER = 10000; // 1 poin tiap Rp10.000

    /** @throws \DomainException untuk kesalahan bisnis yang aman ditampilkan ke pengguna */
    public static function create(array $in, int $storeId, int $cashierId): array
    {
        $items = array_values(array_filter((array) ($in['items'] ?? []), static fn ($i) => (float) ($i['quantity'] ?? 0) > 0));
        if ($items === []) {
            throw new \DomainException('Keranjang masih kosong.');
        }
        $metode = (string) ($in['payment_method'] ?? 'CASH');
        if (! in_array($metode, ['CASH', 'CARD', 'QRIS', 'BANK_TRANSFER', 'E_WALLET'], true)) {
            throw new \DomainException('Metode pembayaran tidak valid.');
        }

        // Gabungkan baris produk yang sama
        $gabung = [];
        foreach ($items as $i) {
            $pid = (int) $i['product_id'];
            $gabung[$pid]['quantity'] = ($gabung[$pid]['quantity'] ?? 0) + round((float) $i['quantity'], 3);
            $gabung[$pid]['discount'] = ($gabung[$pid]['discount'] ?? 0) + max(0, (float) ($i['discount'] ?? 0));
        }
        ksort($gabung); // urutan konsisten -> hindari deadlock

        $db       = db_connect();
        $produk   = $db->table('products')->whereIn('id', array_keys($gabung))->where('is_active', 1)->get()->getResultArray();
        $produk   = array_column($produk, null, 'id');
        $baris    = [];
        $sumNet   = 0.0;
        foreach ($gabung as $pid => $g) {
            if (! isset($produk[$pid])) {
                throw new \DomainException('Ada produk yang tidak ditemukan atau sudah nonaktif.');
            }
            $p     = $produk[$pid];
            $gross = round((float) $p['selling_price'] * $g['quantity'], 2);
            $disc  = min($g['discount'], $gross);
            $baris[$pid] = ['p' => $p, 'qty' => $g['quantity'], 'gross' => $gross, 'disc' => $disc, 'pre' => $gross - $disc];
            $sumNet += $gross - $disc;
        }

        // Diskon level transaksi dialokasikan proporsional ke tiap baris
        $diskonOrder = min(max(0, (float) ($in['discount'] ?? 0)), $sumNet);
        $sisa        = $diskonOrder;
        $terakhir    = array_key_last($baris);
        $subtotal = $diskon = $pajak = $total = 0.0;
        foreach ($baris as $pid => &$b) {
            $alok = $pid === $terakhir ? $sisa : ($sumNet > 0 ? round($diskonOrder * $b['pre'] / $sumNet) : 0);
            $sisa -= $alok;
            $b['disc_total'] = round($b['disc'] + $alok, 2);
            $net             = $b['gross'] - $b['disc_total'];
            $b['tax']        = round($net * (float) $b['p']['tax_rate'] / 100); // pajak dibulatkan ke rupiah
            $b['total']      = round($net + $b['tax'], 2);
            $subtotal += $b['gross'];
            $diskon   += $b['disc_total'];
            $pajak    += $b['tax'];
            $total    += $b['total'];
        }
        unset($b);

        $dibayar = $metode === 'CASH' ? round((float) ($in['paid_amount'] ?? 0), 2) : $total;
        if ($dibayar < $total) {
            throw new \DomainException('Jumlah pembayaran kurang dari total belanja.');
        }
        $kembalian  = round($dibayar - $total, 2);
        $customerId = ! empty($in['customer_id']) ? (int) $in['customer_id'] : null;
        if ($customerId && ! $db->table('customers')->where(['id' => $customerId, 'is_active' => 1])->countAllResults()) {
            throw new \DomainException('Pelanggan tidak ditemukan.');
        }

        $db->transBegin();
        try {
            $invoice = DocNumberService::next('INV');
            $db->table('sales')->insert([
                'invoice_number' => $invoice, 'store_id' => $storeId, 'cashier_id' => $cashierId,
                'register_id'    => $db->table('registers')->select('id')->where(['store_id' => $storeId, 'is_active' => 1])->orderBy('id')->get()->getRow('id'),
                'customer_id'    => $customerId, 'sale_date' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal, 'discount_amount' => $diskon, 'tax_amount' => $pajak, 'total_amount' => $total,
                'paid_amount' => $dibayar, 'change_amount' => $kembalian, 'status' => 'COMPLETED',
                'notes' => mb_substr(trim((string) ($in['notes'] ?? '')), 0, 255) ?: null,
            ]);
            $saleId = (int) $db->insertID();

            foreach ($baris as $pid => $b) {
                $db->table('sale_items')->insert([
                    'sale_id' => $saleId, 'product_id' => $pid, 'quantity' => $b['qty'],
                    'unit_price' => $b['p']['selling_price'], 'cost_price' => $b['p']['cost_price'],
                    'discount_amount' => $b['disc_total'], 'tax_amount' => $b['tax'],
                    'subtotal' => $b['gross'], 'total_amount' => $b['total'],
                ]);
                StockService::decrease($storeId, $pid, $b['qty'], 'SALE', 'sales', $saleId, $invoice);
            }

            $db->table('payments')->insert([
                'sale_id' => $saleId, 'payment_method' => $metode, 'amount' => $total,
                'reference_number' => mb_substr(trim((string) ($in['reference_number'] ?? '')), 0, 100) ?: null,
            ]);

            if ($customerId && ($poin = (int) floor($total / self::POINT_PER)) > 0) {
                $db->query('UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?', [$poin, $customerId]);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        AuditService::log('CREATE', 'sales', $saleId, null, ['invoice' => $invoice, 'total' => $total]);

        return ['id' => $saleId, 'invoice_number' => $invoice, 'total' => $total, 'paid' => $dibayar, 'change' => $kembalian];
    }

    /** Batalkan transaksi: stok dikembalikan dan poin pelanggan dikurangi. */
    public static function void(int $saleId, string $alasan): void
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $sale = $db->query('SELECT * FROM sales WHERE id = ? FOR UPDATE', [$saleId])->getRowArray();
            if (! $sale) {
                throw new \DomainException('Transaksi tidak ditemukan.');
            }
            if ($sale['status'] !== 'COMPLETED') {
                throw new \DomainException('Hanya transaksi berstatus Selesai yang dapat dibatalkan.');
            }
            foreach ($db->table('sale_items')->where('sale_id', $saleId)->orderBy('product_id')->get()->getResultArray() as $it) {
                StockService::increase((int) $sale['store_id'], (int) $it['product_id'], (float) $it['quantity'], 'SALE_RETURN', 'sales', $saleId, 'Pembatalan ' . $sale['invoice_number']);
            }
            if ($sale['customer_id'] && ($poin = (int) floor($sale['total_amount'] / self::POINT_PER)) > 0) {
                $db->query('UPDATE customers SET loyalty_points = GREATEST(0, loyalty_points - ?) WHERE id = ?', [$poin, $sale['customer_id']]);
            }
            $db->table('sales')->where('id', $saleId)->update([
                'status' => 'VOID', 'notes' => mb_substr('DIBATALKAN: ' . $alasan, 0, 255),
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        AuditService::log('VOID', 'sales', $saleId, ['status' => 'COMPLETED'], ['status' => 'VOID', 'alasan' => $alasan]);
    }
}
