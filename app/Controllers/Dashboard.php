<?php

namespace App\Controllers;

use App\Services\ReportService as R;

class Dashboard extends BaseController
{
    public function index()
    {
        return $this->render('dashboard/index', ['title' => 'Dashboard']);
    }

    public function data()
    {
        $s     = current_store_id();
        $today = date('Y-m-d');
        $awalBulan = date('Y-m-01');
        $akhirBulan = date('Y-m-t');
        $dari14 = date('Y-m-d', strtotime('-13 days'));
        $dari30 = date('Y-m-d', strtotime('-29 days'));

        $hari  = R::salesSummary($today, $today, $s);
        $bulan = R::salesSummary($awalBulan, $akhirBulan, $s);
        $laba  = R::grossProfit($awalBulan, $akhirBulan, $s);

        // Isi hari tanpa transaksi dengan 0 agar grafik kontinu
        $harian = array_column(R::dailySales($dari14, $today, $s), 'total', 'tanggal');
        $labels = $values = [];
        for ($d = strtotime($dari14); $d <= strtotime($today); $d += 86400) {
            $k        = date('Y-m-d', $d);
            $labels[] = date('d/m', $d);
            $values[] = (float) ($harian[$k] ?? 0);
        }
        $top = R::topProducts($dari30, $today, $s, 5);
        $pay = R::paymentBreakdown($dari30, $today, $s);

        return $this->ok([
            'cards' => [
                'omzet_hari' => $hari['omzet'], 'trx_hari' => $hari['trx'], 'omzet_bulan' => $bulan['omzet'],
                'laba_kotor' => $laba['laba_kotor'], 'pengeluaran' => R::expensesTotal($awalBulan, $akhirBulan, $s),
                'stok_menipis' => R::lowStockCount($s),
            ],
            'omzet'   => ['labels' => $labels, 'values' => $values],
            'terlaris' => ['labels' => array_column($top, 'name'), 'values' => array_map('floatval', array_column($top, 'qty'))],
            'bayar'   => ['labels' => array_map(static fn ($r) => metode_bayar_label($r['metode']), $pay), 'values' => array_map('floatval', array_column($pay, 'total'))],
            'stok_menipis' => R::lowStock($s, 8),
        ]);
    }
}
