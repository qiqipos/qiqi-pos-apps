<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\StockService;
use CodeIgniter\Database\BaseBuilder;

/** Penyesuaian stok manual (barang rusak, hilang, stok awal, dll) + riwayatnya. */
class InventoryAdjustments extends CrudController
{
    protected string $table = 'inventory_transactions';
    protected string $title = 'Penyesuaian Stok';
    protected string $route = 'inventory/adjustments';
    protected array $rowActions = [];

    protected function columns(): array
    {
        return [
            'created_at'       => ['label' => 'Waktu', 'expr' => 't.created_at', 'type' => 'datetime'],
            'product_name'     => ['label' => 'Produk', 'expr' => "CONCAT(p.sku, ' - ', p.name)", 'search' => true],
            'transaction_type' => ['label' => 'Jenis', 'expr' => 't.transaction_type', 'type' => 'movement'],
            'quantity'         => ['label' => 'Jumlah', 'expr' => 't.quantity', 'type' => 'signed'],
            'notes'            => ['label' => 'Alasan', 'expr' => "IFNULL(t.notes,'-')", 'search' => true],
            'user_name'        => ['label' => 'Oleh', 'expr' => "IFNULL(u.full_name,'-')"],
        ];
    }

    protected function fields(): array
    {
        return [
            'product_id' => ['label' => 'Produk', 'type' => 'select', 'source' => 'products', 'col' => 12, 'rules' => 'required|is_natural_no_zero'],
            'direction'  => ['label' => 'Jenis Penyesuaian', 'type' => 'select', 'source' => 'adjustment_types', 'col' => 6, 'rules' => 'required|in_list[IN,OUT]'],
            'quantity'   => ['label' => 'Jumlah', 'type' => 'number', 'col' => 6, 'rules' => 'required|numeric|greater_than[0]'],
            'notes'      => ['label' => 'Alasan', 'type' => 'text', 'col' => 12, 'rules' => 'required|min_length[3]|max_length[255]'],
        ];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('products p', 'p.id = t.product_id')->join('users u', 'u.id = t.created_by', 'left');
    }

    protected function scope(BaseBuilder $b): void
    {
        $b->where('t.store_id', current_store_id())->whereIn('t.transaction_type', ['ADJUSTMENT_IN', 'ADJUSTMENT_OUT']);
    }

    // Penyesuaian bersifat append-only (jejak audit stok): tidak ada ubah/hapus.
    public function update(int $id)
    {
        return $this->fail('Penyesuaian stok tidak dapat diubah.', [], 405);
    }

    public function delete(int $id)
    {
        return $this->fail('Penyesuaian stok tidak dapat dihapus.', [], 405);
    }

    public function show(int $id)
    {
        return $this->fail('Tidak tersedia.', [], 405);
    }

    public function store()
    {
        $in = $this->payload();
        if (! $this->validateData($in, [
            'product_id' => ['label' => 'Produk', 'rules' => 'required|is_natural_no_zero'],
            'direction'  => ['label' => 'Jenis', 'rules' => 'required|in_list[IN,OUT]'],
            'quantity'   => ['label' => 'Jumlah', 'rules' => 'required|numeric|greater_than[0]'],
            'notes'      => ['label' => 'Alasan', 'rules' => 'required|min_length[3]|max_length[255]'],
        ])) {
            return $this->fail('Periksa kembali isian Anda.', $this->validator->getErrors());
        }
        $db = db_connect();
        if (! $db->table('products')->where('id', $in['product_id'])->countAllResults()) {
            return $this->fail('Produk tidak ditemukan.', ['product_id' => 'Produk tidak ditemukan.']);
        }
        $qty = round((float) $in['quantity'], 3);
        $db->transBegin();
        try {
            if ($in['direction'] === 'IN') {
                StockService::increase(current_store_id(), (int) $in['product_id'], $qty, 'ADJUSTMENT_IN', 'adjustment', null, trim($in['notes']));
            } else {
                StockService::decrease(current_store_id(), (int) $in['product_id'], $qty, 'ADJUSTMENT_OUT', 'adjustment', null, trim($in['notes']));
            }
            $db->transCommit();
        } catch (\DomainException $e) {
            $db->transRollback();

            return $this->fail($e->getMessage(), ['quantity' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        AuditService::log('ADJUST', 'inventory', (int) $in['product_id'], null, ['arah' => $in['direction'], 'qty' => $qty, 'alasan' => $in['notes']]);

        return $this->ok(null, 'Penyesuaian stok berhasil disimpan.', 201);
    }
}
