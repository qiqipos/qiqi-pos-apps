<?php

namespace App\Controllers;

use App\Services\ReportService as R;

class Reports extends BaseController
{
    private const NAMES = ['sales', 'purchases', 'inventory', 'profit-loss', 'expenses', 'customers'];

    public function page(string $name)
    {
        if (! in_array($name, self::NAMES, true)) {
            return $this->response->setStatusCode(404)->setBody('Laporan tidak ditemukan.');
        }

        return $this->render('reports/index', ['title' => $this->def($name)['title'], 'config' => $this->def($name) + ['name' => $name]]);
    }

    public function data(string $name)
    {
        if (! in_array($name, self::NAMES, true)) {
            return $this->fail('Laporan tidak ditemukan.', [], 404);
        }
        $dari = $this->tanggal($this->request->getGet('from'), date('Y-m-01'));
        $sampai = $this->tanggal($this->request->getGet('to'), date('Y-m-d'));
        if ($dari > $sampai) {
            return $this->fail('Tanggal awal tidak boleh melebihi tanggal akhir.');
        }

        return $this->ok($this->{'build' . str_replace('-', '', ucwords($name, '-'))}($dari, $sampai, current_store_id()));
    }

    private function def(string $name): array
    {
        $c = static fn (string $d, string $t, string $type = 'text') => ['data' => $d, 'title' => $t, 'type' => $type];

        return match ($name) {
            'sales' => ['title' => 'Laporan Penjualan', 'dates' => true, 'chart' => ['type' => 'line', 'label' => 'Omzet per hari (Rp)'],
                'columns' => [$c('tanggal', 'Tanggal', 'date'), $c('trx', 'Transaksi', 'number'), $c('subtotal', 'Subtotal', 'rupiah'), $c('diskon', 'Diskon', 'rupiah'), $c('pajak', 'Pajak', 'rupiah'), $c('total', 'Total', 'rupiah')]],
            'purchases' => ['title' => 'Laporan Pembelian', 'dates' => true, 'chart' => ['type' => 'bar', 'label' => 'Pembelian per supplier (Rp)'],
                'columns' => [$c('po_number', 'No. Pembelian'), $c('order_date', 'Tanggal', 'date'), $c('supplier', 'Supplier'), $c('status', 'Status', 'status'), $c('total', 'Total', 'rupiah')]],
            'inventory' => ['title' => 'Laporan Persediaan', 'dates' => false, 'chart' => ['type' => 'bar', 'label' => 'Nilai persediaan per kategori (Rp)'],
                'columns' => [$c('sku', 'SKU'), $c('name', 'Produk'), $c('category', 'Kategori'), $c('quantity', 'Stok', 'number'), $c('cost_price', 'Harga Beli', 'rupiah'), $c('value', 'Nilai', 'rupiah')]],
            'profit-loss' => ['title' => 'Laporan Laba Rugi', 'dates' => true, 'chart' => ['type' => 'bar', 'label' => 'Ringkasan laba rugi (Rp)'],
                'columns' => [$c('uraian', 'Uraian'), $c('jumlah', 'Jumlah', 'rupiah')]],
            'expenses' => ['title' => 'Laporan Pengeluaran', 'dates' => true, 'chart' => ['type' => 'doughnut', 'label' => 'Pengeluaran per kategori'],
                'columns' => [$c('kategori', 'Kategori'), $c('jumlah', 'Jumlah Transaksi', 'number'), $c('total', 'Total', 'rupiah')]],
            'customers' => ['title' => 'Laporan Pelanggan', 'dates' => true, 'chart' => ['type' => 'bar', 'label' => 'Belanja pelanggan teratas (Rp)'],
                'columns' => [$c('name', 'Pelanggan'), $c('trx', 'Transaksi', 'number'), $c('total', 'Total Belanja', 'rupiah'), $c('terakhir', 'Terakhir Belanja', 'date')]],
        };
    }

    private function tanggal(?string $v, string $default): string
    {
        return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : $default;
    }

    private function card(string $label, float|int $value, string $type = 'rupiah'): array
    {
        return ['label' => $label, 'value' => $value, 'type' => $type];
    }

    private function buildSales(string $f, string $t, int $s): array
    {
        $rows  = R::dailySales($f, $t, $s);
        $omzet = array_sum(array_column($rows, 'total'));
        $trx   = array_sum(array_column($rows, 'trx'));

        return ['summary' => [$this->card('Total Omzet', $omzet), $this->card('Jumlah Transaksi', $trx, 'number'), $this->card('Rata-rata / Transaksi', $trx ? $omzet / $trx : 0)],
            'chart' => ['labels' => array_column($rows, 'tanggal'), 'values' => array_map('floatval', array_column($rows, 'total'))], 'rows' => $rows];
    }

    private function buildPurchases(string $f, string $t, int $s): array
    {
        $rows = db_connect()->query(
            "SELECT po.po_number, DATE(po.order_date) AS order_date, sp.name AS supplier, po.status, po.total_amount AS total
             FROM purchase_orders po JOIN suppliers sp ON sp.id = po.supplier_id
             WHERE po.store_id = ? AND po.status <> 'CANCELLED' AND DATE(po.order_date) BETWEEN ? AND ? ORDER BY po.order_date DESC",
            [$s, $f, $t]
        )->getResultArray();
        $per = [];
        foreach ($rows as $r) {
            $per[$r['supplier']] = ($per[$r['supplier']] ?? 0) + (float) $r['total'];
        }

        return ['summary' => [$this->card('Total Pembelian', array_sum(array_column($rows, 'total'))), $this->card('Jumlah Pembelian', count($rows), 'number')],
            'chart' => ['labels' => array_keys($per), 'values' => array_values($per)], 'rows' => $rows];
    }

    private function buildInventory(string $f, string $t, int $s): array
    {
        $rows = db_connect()->query(
            "SELECT p.sku, p.name, IFNULL(c.name,'-') AS category, IFNULL(i.quantity,0) AS quantity, p.cost_price, IFNULL(i.quantity,0) * p.cost_price AS value
             FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN inventory i ON i.product_id = p.id AND i.store_id = ?
             WHERE p.is_active = 1 ORDER BY p.name",
            [$s]
        )->getResultArray();
        $per = [];
        foreach ($rows as $r) {
            $per[$r['category']] = ($per[$r['category']] ?? 0) + (float) $r['value'];
        }

        return ['summary' => [$this->card('Nilai Persediaan', array_sum(array_column($rows, 'value'))), $this->card('Jumlah Produk', count($rows), 'number'), $this->card('Stok Menipis / Habis', R::lowStockCount($s), 'number')],
            'chart' => ['labels' => array_keys($per), 'values' => array_values($per)], 'rows' => $rows];
    }

    private function buildProfitloss(string $f, string $t, int $s): array
    {
        $g    = R::grossProfit($f, $t, $s);
        $beban = db_connect()->query(
            'SELECT c.name, SUM(e.amount) AS total FROM expenses e JOIN expense_categories c ON c.id = e.category_id
             WHERE e.store_id = ? AND e.expense_date BETWEEN ? AND ? GROUP BY c.id, c.name ORDER BY total DESC',
            [$s, $f, $t]
        )->getResultArray();
        $totalBeban = array_sum(array_column($beban, 'total'));
        $bersih     = $g['laba_kotor'] - $totalBeban;

        $rows = [['uraian' => 'Pendapatan Bersih (setelah diskon, sebelum pajak)', 'jumlah' => $g['pendapatan']], ['uraian' => 'Harga Pokok Penjualan (HPP)', 'jumlah' => -$g['hpp']], ['uraian' => 'LABA KOTOR', 'jumlah' => $g['laba_kotor']]];
        foreach ($beban as $b) {
            $rows[] = ['uraian' => 'Biaya: ' . $b['name'], 'jumlah' => -(float) $b['total']];
        }
        $rows[] = ['uraian' => 'Total Pengeluaran Operasional', 'jumlah' => -$totalBeban];
        $rows[] = ['uraian' => 'LABA BERSIH', 'jumlah' => $bersih];

        return ['summary' => [$this->card('Laba Kotor', $g['laba_kotor']), $this->card('Pengeluaran', $totalBeban), $this->card('Laba Bersih', $bersih)],
            'chart' => ['labels' => ['Pendapatan', 'HPP', 'Laba Kotor', 'Pengeluaran', 'Laba Bersih'], 'values' => [$g['pendapatan'], $g['hpp'], $g['laba_kotor'], $totalBeban, $bersih]], 'rows' => $rows];
    }

    private function buildExpenses(string $f, string $t, int $s): array
    {
        $rows = db_connect()->query(
            'SELECT c.name AS kategori, COUNT(*) AS jumlah, SUM(e.amount) AS total FROM expenses e JOIN expense_categories c ON c.id = e.category_id
             WHERE e.store_id = ? AND e.expense_date BETWEEN ? AND ? GROUP BY c.id, c.name ORDER BY total DESC',
            [$s, $f, $t]
        )->getResultArray();

        return ['summary' => [$this->card('Total Pengeluaran', array_sum(array_column($rows, 'total'))), $this->card('Jumlah Transaksi', array_sum(array_column($rows, 'jumlah')), 'number')],
            'chart' => ['labels' => array_column($rows, 'kategori'), 'values' => array_map('floatval', array_column($rows, 'total'))], 'rows' => $rows];
    }

    private function buildCustomers(string $f, string $t, int $s): array
    {
        $rows = db_connect()->query(
            "SELECT c.name, COUNT(*) AS trx, SUM(s.total_amount) AS total, DATE(MAX(s.sale_date)) AS terakhir
             FROM sales s JOIN customers c ON c.id = s.customer_id
             WHERE s.store_id = ? AND s.status = 'COMPLETED' AND DATE(s.sale_date) BETWEEN ? AND ?
             GROUP BY c.id, c.name ORDER BY total DESC",
            [$s, $f, $t]
        )->getResultArray();
        $top = array_slice($rows, 0, 10);

        return ['summary' => [$this->card('Pelanggan Aktif', count($rows), 'number'), $this->card('Total Belanja', array_sum(array_column($rows, 'total')))],
            'chart' => ['labels' => array_column($top, 'name'), 'values' => array_map('floatval', array_column($top, 'total'))], 'rows' => $rows];
    }
}
