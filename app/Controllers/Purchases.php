<?php

namespace App\Controllers;

use App\Services\PurchaseService;
use CodeIgniter\Database\BaseBuilder;

class Purchases extends CrudController
{
    protected string $table    = 'purchase_orders';
    protected string $title    = 'Daftar Pembelian';
    protected string $route    = 'purchases';
    protected bool   $readOnly = true;
    protected ?string $extraView = 'crud/extras/purchases';
    protected array  $toolbar  = [['label' => 'Pembelian Baru', 'href' => 'purchases/create', 'icon' => 'fa-solid fa-plus']];

    protected function columns(): array
    {
        return [
            'po_number'     => ['label' => 'No. Pembelian', 'expr' => 't.po_number', 'search' => true],
            'order_date'    => ['label' => 'Tanggal', 'expr' => 't.order_date', 'type' => 'datetime'],
            'supplier_name' => ['label' => 'Supplier', 'expr' => 's.name', 'search' => true],
            'total_amount'  => ['label' => 'Total', 'expr' => 't.total_amount', 'type' => 'rupiah'],
            'status'        => ['label' => 'Status', 'expr' => 't.status', 'type' => 'status'],
            'actions'       => ['label' => 'Aksi', 'expr' => 't.id', 'type' => 'html', 'order' => false],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('suppliers s', 's.id = t.supplier_id');
    }

    protected function scope(BaseBuilder $b): void
    {
        $b->where('t.store_id', current_store_id());
    }

    protected function decorateRow(array $r): array
    {
        $id  = (int) $r['id'];
        $lbl = esc($r['po_number']);
        $btn = "<button class=\"btn btn-sm btn-outline-primary\" data-act=\"detail\" data-id=\"{$id}\" title=\"Detail\"><i class=\"fa-solid fa-eye\"></i></button>";
        if ($r['status'] === 'ORDERED') {
            $btn .= "<button class=\"btn btn-sm btn-outline-success\" data-act=\"receive\" data-id=\"{$id}\" data-label=\"{$lbl}\" title=\"Terima barang\"><i class=\"fa-solid fa-box-open\"></i></button>"
                  . "<button class=\"btn btn-sm btn-outline-danger\" data-act=\"cancel\" data-id=\"{$id}\" data-label=\"{$lbl}\" title=\"Batalkan\"><i class=\"fa-solid fa-ban\"></i></button>";
        }
        $r['actions'] = "<div class=\"btn-group\">{$btn}</div>";

        return $r;
    }

    public function form()
    {
        return $this->render('purchases/form', ['title' => 'Pembelian Baru']);
    }

    public function store()
    {
        try {
            $hasil = PurchaseService::create($this->payload(), current_store_id(), (int) current_user('id'));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok($hasil, 'Pembelian ' . $hasil['po_number'] . ' berhasil dibuat.', 201);
    }

    public function detail(int $id)
    {
        $db = db_connect();
        $po = $db->table('purchase_orders t')->select('t.*, s.name AS supplier_name')->join('suppliers s', 's.id = t.supplier_id')
            ->where('t.id', $id)->where('t.store_id', current_store_id())->get()->getRowArray();
        if (! $po) {
            return $this->fail('Pembelian tidak ditemukan.', [], 404);
        }
        $items = $db->table('purchase_order_items i')->select('p.sku, p.name, p.unit, i.quantity, i.unit_cost, i.received_quantity, i.subtotal')
            ->join('products p', 'p.id = i.product_id')->where('i.purchase_order_id', $id)->get()->getResultArray();

        return $this->ok(['po' => $po, 'items' => $items]);
    }

    public function receive(int $id)
    {
        try {
            PurchaseService::receive($id);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), [], 409);
        }

        return $this->ok(null, 'Barang diterima dan stok telah diperbarui.');
    }

    public function cancel(int $id)
    {
        try {
            PurchaseService::cancel($id);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), [], 409);
        }

        return $this->ok(null, 'Pembelian dibatalkan.');
    }
}
