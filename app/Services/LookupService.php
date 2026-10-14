<?php

namespace App\Services;

/** Sumber data untuk dropdown Select2 (pencarian AJAX). */
class LookupService
{
    private const SOURCES = [
        'categories'         => ['table' => 'categories', 'text' => 'name', 'search' => ['name', 'code'], 'active' => 'is_active'],
        'units'              => ['table' => 'units', 'id' => 'code', 'text' => "CONCAT(code, ' - ', name)", 'search' => ['code', 'name'], 'active' => 'is_active'],
        'brands'             => ['table' => 'brands', 'text' => 'name', 'search' => ['name'], 'active' => 'is_active'],
        'suppliers'          => ['table' => 'suppliers', 'text' => "CONCAT(code, ' - ', name)", 'search' => ['code', 'name'], 'active' => 'is_active'],
        'customers'          => ['table' => 'customers', 'text' => "CONCAT(name, IFNULL(CONCAT(' (', phone, ')'), ''))", 'search' => ['name', 'phone', 'customer_code'], 'active' => 'is_active'],
        'stores'             => ['table' => 'stores', 'text' => 'name', 'search' => ['name', 'code'], 'active' => 'is_active'],
        'roles'              => ['table' => 'roles', 'text' => 'name', 'search' => ['name', 'code']],
        'expense_categories' => ['table' => 'expense_categories', 'text' => 'name', 'search' => ['name'], 'active' => 'is_active'],
        'products'           => ['table' => 'products', 'text' => "CONCAT(sku, ' - ', name)", 'search' => ['sku', 'name', 'barcode'], 'active' => 'is_active'],
        'payment_methods'    => null, // statis, lihat staticOptions()
        'adjustment_types'   => null,
    ];

    public static function has(string $source): bool
    {
        return array_key_exists($source, self::SOURCES);
    }

    /** Opsi statis (tanpa DB). */
    public static function staticOptions(string $source): ?array
    {
        if ($source === 'payment_methods') {
            return [
                ['id' => 'CASH', 'text' => 'Tunai'], ['id' => 'QRIS', 'text' => 'QRIS'],
                ['id' => 'BANK_TRANSFER', 'text' => 'Transfer Bank'], ['id' => 'CARD', 'text' => 'Kartu'],
                ['id' => 'E_WALLET', 'text' => 'E-Wallet'],
            ];
        }

        if ($source === 'adjustment_types') {
            return [['id' => 'IN', 'text' => 'Penambahan stok (masuk)'], ['id' => 'OUT', 'text' => 'Pengurangan stok (keluar)']];
        }

        return null;
    }

    /** @return list<array{id:mixed,text:string}> */
    public static function search(string $source, string $term = '', int $page = 1, int $perPage = 20): array
    {
        if (($static = self::staticOptions($source)) !== null) {
            return array_values(array_filter($static, static fn ($o) => $term === '' || stripos($o['text'], $term) !== false));
        }
        $cfg = self::SOURCES[$source];
        $id  = $cfg['id'] ?? 'id';
        $b   = db_connect()->table($cfg['table'])->select("{$id} AS id, {$cfg['text']} AS text", false);
        if (isset($cfg['active'])) {
            $b->where($cfg['active'], 1);
        }
        if ($term !== '') {
            $b->groupStart();
            foreach ($cfg['search'] as $col) {
                $b->orLike($col, $term);
            }
            $b->groupEnd();
        }

        return $b->orderBy('text', 'ASC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
    }

    /** Label untuk satu nilai (dipakai saat mengisi ulang form edit). */
    public static function label(string $source, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (($static = self::staticOptions($source)) !== null) {
            foreach ($static as $o) {
                if ($o['id'] === $value) {
                    return $o;
                }
            }

            return null;
        }
        $cfg = self::SOURCES[$source];
        $id  = $cfg['id'] ?? 'id';

        return db_connect()->table($cfg['table'])->select("{$id} AS id, {$cfg['text']} AS text", false)
            ->where($id, $value)->get()->getRowArray();
    }
}
