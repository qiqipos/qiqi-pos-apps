<?php

namespace App\Services;

/** Penomoran dokumen atomik per hari: INV-20261014-0001. Harus dipanggil di dalam transaksi DB. */
class DocNumberService
{
    public static function next(string $prefix): string
    {
        $db     = db_connect();
        $period = date('Ymd');

        $db->query(
            'INSERT INTO doc_sequences (doc_type, period, last_number) VALUES (?, ?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$prefix, $period]
        );
        $n = (int) $db->query('SELECT LAST_INSERT_ID() AS n')->getRow()->n;

        return sprintf('%s-%s-%04d', $prefix, $period, $n);
    }
}
