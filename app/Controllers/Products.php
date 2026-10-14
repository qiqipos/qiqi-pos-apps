<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

class Products extends CrudController
{
    protected string $table = 'products';
    protected string $title = 'Produk';
    protected string $route = 'products';

    protected function columns(): array
    {
        $store = current_store_id();

        return [
            'sku'           => ['label' => 'SKU', 'expr' => 't.sku', 'search' => true],
            'barcode'       => ['label' => 'Barcode', 'expr' => 'IFNULL(t.barcode, \'-\')', 'search' => true],
            'name'          => ['label' => 'Nama Produk', 'expr' => 't.name', 'search' => true],
            'category_name' => ['label' => 'Kategori', 'expr' => 'IFNULL(c.name, \'-\')', 'search' => true],
            'unit'          => ['label' => 'Satuan', 'expr' => 't.unit'],
            'cost_price'    => ['label' => 'Harga Beli', 'expr' => 't.cost_price', 'type' => 'rupiah'],
            'selling_price' => ['label' => 'Harga Jual', 'expr' => 't.selling_price', 'type' => 'rupiah'],
            'stock'         => ['label' => 'Stok', 'type' => 'number',
                'expr' => "IFNULL((SELECT i.quantity FROM inventory i WHERE i.product_id = t.id AND i.store_id = {$store}), 0)"],
            'is_active'     => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'sku'           => ['label' => 'SKU', 'type' => 'text', 'col' => 4, 'rules' => 'required|max_length[50]|is_unique[products.sku,id,{id}]'],
            'barcode'       => ['label' => 'Barcode', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[100]|is_unique[products.barcode,id,{id}]'],
            'unit'          => ['label' => 'Satuan', 'type' => 'select', 'source' => 'units', 'col' => 4, 'rules' => 'required'],
            'name'          => ['label' => 'Nama Produk', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[200]'],
            'category_id'   => ['label' => 'Kategori', 'type' => 'select', 'source' => 'categories', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|is_natural_no_zero'],
            'cost_price'    => ['label' => 'Harga Beli (Rp)', 'type' => 'number', 'col' => 4, 'rules' => 'required|numeric|greater_than_equal_to[0]'],
            'selling_price' => ['label' => 'Harga Jual (Rp)', 'type' => 'number', 'col' => 4, 'rules' => 'required|numeric|greater_than_equal_to[0]'],
            'tax_rate'      => ['label' => 'Pajak (%)', 'type' => 'number', 'col' => 4, 'rules' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]'],
            'min_stock'     => ['label' => 'Stok Minimum', 'type' => 'number', 'col' => 4, 'rules' => 'required|numeric|greater_than_equal_to[0]'],
            'max_stock'     => ['label' => 'Stok Maksimum', 'type' => 'number', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|numeric|greater_than_equal_to[0]'],
            'is_active'     => ['label' => 'Produk aktif', 'type' => 'switch', 'col' => 12],
        ];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('categories c', 'c.id = t.category_id', 'left');
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        $dipakai = db_connect()->table('sale_items')->where('product_id', $row['id'])->countAllResults();

        return $dipakai > 0 ? 'Produk sudah pernah terjual dan tidak dapat dihapus. Nonaktifkan saja.' : null;
    }
}
