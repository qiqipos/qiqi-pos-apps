<?php

/**
 * Helper aplikasi Qiqi POS.
 * Dimuat otomatis lewat BaseController::$helpers.
 */

if (! function_exists('rupiah')) {
    /** Format angka menjadi "Rp 1.250.000". */
    function rupiah(float|int|string|null $nilai, bool $denganSimbol = true): string
    {
        $angka = number_format((float) $nilai, 0, ',', '.');

        return $denganSimbol ? 'Rp ' . $angka : $angka;
    }
}

if (! function_exists('tanggal_indo')) {
    /** Format tanggal Indonesia: "14 Okt 2026 10:30". */
    function tanggal_indo(?string $tanggal, bool $denganJam = false): string
    {
        if (empty($tanggal)) {
            return '-';
        }
        $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t     = strtotime($tanggal);
        $hasil = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);

        return $denganJam ? $hasil . ' ' . date('H:i', $t) : $hasil;
    }
}

if (! function_exists('current_user')) {
    /** Data user yang sedang login (dari session). */
    function current_user(?string $kunci = null): mixed
    {
        $user = session('user') ?? [];

        return $kunci === null ? $user : ($user[$kunci] ?? null);
    }
}

if (! function_exists('current_store_id')) {
    function current_store_id(): int
    {
        return (int) (session('store_id') ?? 1);
    }
}

if (! function_exists('status_label')) {
    /** Terjemahan status transaksi ke Bahasa Indonesia. */
    function status_label(string $status): string
    {
        return [
            'DRAFT' => 'Draf', 'COMPLETED' => 'Selesai', 'VOID' => 'Dibatalkan',
            'RETURNED' => 'Diretur', 'PARTIALLY_RETURNED' => 'Retur Sebagian',
            'ORDERED' => 'Dipesan', 'PARTIAL' => 'Diterima Sebagian',
            'RECEIVED' => 'Diterima', 'CANCELLED' => 'Dibatalkan',
        ][$status] ?? $status;
    }
}

if (! function_exists('metode_bayar_label')) {
    function metode_bayar_label(string $metode): string
    {
        return [
            'CASH' => 'Tunai', 'CARD' => 'Kartu', 'QRIS' => 'QRIS',
            'BANK_TRANSFER' => 'Transfer Bank', 'E_WALLET' => 'E-Wallet',
        ][$metode] ?? $metode;
    }
}
