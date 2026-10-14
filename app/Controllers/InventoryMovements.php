<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

class InventoryMovements extends CrudController
{
    protected string $table    = 'inventory_transactions';
    protected string $title    = 'Pergerakan Stok';
    protected string $route    = 'inventory/movements';
    protected bool   $readOnly = true;

    protected function columns(): array
    {
        return [
            'created_at'       => ['label' => 'Waktu', 'expr' => 't.created_at', 'type' => 'datetime'],
            'sku'              => ['label' => 'SKU', 'expr' => 'p.sku', 'search' => true],
            'product_name'     => ['label' => 'Produk', 'expr' => 'p.name', 'search' => true],
            'transaction_type' => ['label' => 'Jenis', 'expr' => 't.transaction_type', 'type' => 'movement'],
            'quantity'         => ['label' => 'Jumlah', 'expr' => 't.quantity', 'type' => 'signed'],
            'notes'            => ['label' => 'Keterangan', 'expr' => "IFNULL(t.notes,'-')", 'search' => true],
            'user_name'        => ['label' => 'Oleh', 'expr' => "IFNULL(u.full_name,'-')"],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('products p', 'p.id = t.product_id')->join('users u', 'u.id = t.created_by', 'left');
    }

    protected function scope(BaseBuilder $b): void
    {
        $b->where('t.store_id', current_store_id());
    }
}
