<?php

namespace App\Services;

/** Query agregasi untuk dashboard dan laporan. Semua nilai dihitung dari transaksi berstatus COMPLETED. */
class ReportService
{
    public static function salesSummary(string $from, string $to, int $store): array
    {
        $r = db_connect()->query(
            "SELECT COUNT(*) AS trx, IFNULL(SUM(total_amount),0) AS omzet FROM sales
             WHERE store_id = ? AND status = 'COMPLETED' AND DATE(sale_date) BETWEEN ? AND ?",
            [$store, $from, $to]
        )->getRowArray();

        return ['trx' => (int) $r['trx'], 'omzet' => (float) $r['omzet']];
    }

    /** Pendapatan bersih (tanpa pajak), HPP, dan laba kotor. */
    public static function grossProfit(string $from, string $to, int $store): array
    {
        $r = db_connect()->query(
            "SELECT IFNULL(SUM(si.subtotal - si.discount_amount),0) AS pendapatan,
                    IFNULL(SUM(si.cost_price * si.quantity),0) AS hpp
             FROM sale_items si JOIN sales s ON s.id = si.sale_id
             WHERE s.store_id = ? AND s.status = 'COMPLETED' AND DATE(s.sale_date) BETWEEN ? AND ?",
            [$store, $from, $to]
        )->getRowArray();

        return ['pendapatan' => (float) $r['pendapatan'], 'hpp' => (float) $r['hpp'], 'laba_kotor' => (float) $r['pendapatan'] - (float) $r['hpp']];
    }

    public static function expensesTotal(string $from, string $to, int $store): float
    {
        return (float) db_connect()->query(
            'SELECT IFNULL(SUM(amount),0) AS n FROM expenses WHERE store_id = ? AND expense_date BETWEEN ? AND ?',
            [$store, $from, $to]
        )->getRow('n');
    }

    public static function dailySales(string $from, string $to, int $store): array
    {
        return db_connect()->query(
            "SELECT DATE(sale_date) AS tanggal, COUNT(*) AS trx, SUM(subtotal) AS subtotal, SUM(discount_amount) AS diskon,
                    SUM(tax_amount) AS pajak, SUM(total_amount) AS total
             FROM sales WHERE store_id = ? AND status = 'COMPLETED' AND DATE(sale_date) BETWEEN ? AND ?
             GROUP BY DATE(sale_date) ORDER BY tanggal",
            [$store, $from, $to]
        )->getResultArray();
    }

    public static function topProducts(string $from, string $to, int $store, int $limit = 5): array
    {
        return db_connect()->query(
            "SELECT p.name, SUM(si.quantity) AS qty, SUM(si.total_amount) AS total
             FROM sale_items si JOIN sales s ON s.id = si.sale_id JOIN products p ON p.id = si.product_id
             WHERE s.store_id = ? AND s.status = 'COMPLETED' AND DATE(s.sale_date) BETWEEN ? AND ?
             GROUP BY p.id, p.name ORDER BY qty DESC LIMIT " . (int) $limit,
            [$store, $from, $to]
        )->getResultArray();
    }

    public static function paymentBreakdown(string $from, string $to, int $store): array
    {
        return db_connect()->query(
            "SELECT py.payment_method AS metode, SUM(py.amount) AS total
             FROM payments py JOIN sales s ON s.id = py.sale_id
             WHERE s.store_id = ? AND s.status = 'COMPLETED' AND DATE(s.sale_date) BETWEEN ? AND ?
             GROUP BY py.payment_method",
            [$store, $from, $to]
        )->getResultArray();
    }

    public static function lowStock(int $store, int $limit = 10): array
    {
        return db_connect()->query(
            'SELECT p.sku, p.name, p.unit, p.min_stock, IFNULL(i.quantity,0) AS quantity
             FROM products p LEFT JOIN inventory i ON i.product_id = p.id AND i.store_id = ?
             WHERE p.is_active = 1 AND IFNULL(i.quantity,0) <= p.min_stock
             ORDER BY (IFNULL(i.quantity,0) - p.min_stock) ASC LIMIT ' . (int) $limit,
            [$store]
        )->getResultArray();
    }

    public static function lowStockCount(int $store): int
    {
        return (int) db_connect()->query(
            'SELECT COUNT(*) AS n FROM products p LEFT JOIN inventory i ON i.product_id = p.id AND i.store_id = ?
             WHERE p.is_active = 1 AND IFNULL(i.quantity,0) <= p.min_stock',
            [$store]
        )->getRow('n');
    }
}
