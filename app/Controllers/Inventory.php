<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

/** Stok per outlet (daftar). Pergerakan & penyesuaian ada di controller terpisah. */
class Inventory extends CrudController
{
    protected string $table    = 'products';
    protected string $title    = 'Stok Barang';
    protected string $route    = 'inventory';
    protected bool   $readOnly = true;
    protected array  $toolbar  = [['label' => 'Penyesuaian Stok', 'href' => 'inventory/adjustments', 'icon' => 'fa-solid fa-sliders']];

    protected function columns(): array
    {
        $store = current_store_id();
        $qty   = "IFNULL((SELECT i.quantity FROM inventory i WHERE i.product_id = t.id AND i.store_id = {$store}), 0)";

        return [
            'sku'        => ['label' => 'SKU', 'expr' => 't.sku', 'search' => true],
            'name'       => ['label' => 'Nama Produk', 'expr' => 't.name', 'search' => true],
            'category'   => ['label' => 'Kategori', 'expr' => "IFNULL(c.name,'-')", 'search' => true],
            'unit'       => ['label' => 'Satuan', 'expr' => 't.unit'],
            'quantity'   => ['label' => 'Stok', 'expr' => $qty, 'type' => 'number'],
            'min_stock'  => ['label' => 'Stok Min.', 'expr' => 't.min_stock', 'type' => 'number'],
            'value'      => ['label' => 'Nilai Persediaan', 'expr' => "({$qty} * t.cost_price)", 'type' => 'rupiah'],
            'stock_state' => ['label' => 'Kondisi', 'expr' => "CASE WHEN {$qty} <= 0 THEN 'HABIS' WHEN {$qty} <= t.min_stock THEN 'MENIPIS' ELSE 'AMAN' END", 'type' => 'stockstate'],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('categories c', 'c.id = t.category_id', 'left');
        $b->where('t.is_active', 1);
    }

    protected function defaultOrder(): string
    {
        return 't.name';
    }
}
