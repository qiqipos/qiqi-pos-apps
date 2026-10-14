<?php

namespace App\Controllers;

use App\Services\SaleService;
use CodeIgniter\Database\BaseBuilder;

class Sales extends CrudController
{
    protected string $table    = 'sales';
    protected string $title    = 'Daftar Penjualan';
    protected string $route    = 'sales';
    protected bool   $readOnly = true;
    protected ?string $extraView = 'crud/extras/sales';
    protected array  $toolbar  = [['label' => 'Transaksi Baru', 'href' => 'sales/create', 'icon' => 'fa-solid fa-cash-register']];

    protected function columns(): array
    {
        return [
            'invoice_number' => ['label' => 'No. Invoice', 'expr' => 't.invoice_number', 'search' => true],
            'sale_date'      => ['label' => 'Tanggal', 'expr' => 't.sale_date', 'type' => 'datetime'],
            'customer_name'  => ['label' => 'Pelanggan', 'expr' => "IFNULL(c.name, 'Umum')", 'search' => true],
            'cashier_name'   => ['label' => 'Kasir', 'expr' => "IFNULL(u.full_name, '-')", 'search' => true],
            'payment_method' => ['label' => 'Pembayaran', 'expr' => "IFNULL((SELECT py.payment_method FROM payments py WHERE py.sale_id = t.id LIMIT 1), '-')", 'type' => 'payment'],
            'total_amount'   => ['label' => 'Total', 'expr' => 't.total_amount', 'type' => 'rupiah'],
            'status'         => ['label' => 'Status', 'expr' => 't.status', 'type' => 'status'],
            'actions'        => ['label' => 'Aksi', 'expr' => 't.id', 'type' => 'html', 'order' => false],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('customers c', 'c.id = t.customer_id', 'left')->join('users u', 'u.id = t.cashier_id', 'left');
    }

    protected function scope(BaseBuilder $b): void
    {
        $b->where('t.store_id', current_store_id());
    }

    protected function defaultOrder(): string
    {
        return 't.id';
    }

    protected function decorateRow(array $r): array
    {
        $id   = (int) $r['id'];
        $void = $r['status'] === 'COMPLETED'
            ? "<button class=\"btn btn-sm btn-outline-danger\" data-act=\"void\" data-id=\"{$id}\" data-label=\"" . esc($r['invoice_number']) . "\" title=\"Batalkan\"><i class=\"fa-solid fa-ban\"></i></button>" : '';
        $r['actions'] = "<div class=\"btn-group\"><button class=\"btn btn-sm btn-outline-primary\" data-act=\"detail\" data-id=\"{$id}\" title=\"Detail\"><i class=\"fa-solid fa-eye\"></i></button>"
            . "<a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"" . site_url("sales/{$id}/receipt") . "\" title=\"Cetak struk\"><i class=\"fa-solid fa-print\"></i></a>{$void}</div>";

        return $r;
    }

    // ------------------------------------------------------------ Kasir (POS)

    public function pos()
    {
        return $this->render('sales/pos', ['title' => 'Kasir']);
    }

    /** Grid/pencarian produk untuk kasir: stok outlet saat ini ikut ditampilkan. */
    public function products()
    {
        $q = trim((string) $this->request->getGet('q'));
        $b = db_connect()->table('products p')
            ->select('p.id, p.sku, p.barcode, p.name, p.unit, p.selling_price, p.tax_rate, IFNULL(i.quantity,0) AS stock', false)
            ->join('inventory i', 'i.product_id = p.id AND i.store_id = ' . current_store_id(), 'left')
            ->where('p.is_active', 1);
        if ($q !== '') {
            $b->groupStart()->like('p.name', $q)->orLike('p.sku', $q)->orLike('p.barcode', $q)->groupEnd();
        }

        return $this->ok($b->orderBy('p.name')->limit(60)->get()->getResultArray());
    }

    public function store()
    {
        try {
            $hasil = SaleService::create($this->payload(), current_store_id(), (int) current_user('id'));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok($hasil + ['receipt_url' => site_url("sales/{$hasil['id']}/receipt")], 'Transaksi berhasil disimpan.', 201);
    }

    public function detail(int $id)
    {
        $db   = db_connect();
        $sale = $this->saleHeader($id);
        if (! $sale) {
            return $this->fail('Transaksi tidak ditemukan.', [], 404);
        }
        $items = $db->table('sale_items si')->select('p.sku, p.name, p.unit, si.quantity, si.unit_price, si.discount_amount, si.total_amount')
            ->join('products p', 'p.id = si.product_id')->where('si.sale_id', $id)->get()->getResultArray();

        return $this->ok(['sale' => $sale, 'items' => $items]);
    }

    public function void(int $id)
    {
        $alasan = trim((string) ($this->payload()['reason'] ?? ''));
        if (mb_strlen($alasan) < 3) {
            return $this->fail('Alasan pembatalan wajib diisi (minimal 3 karakter).', ['reason' => 'Wajib diisi.']);
        }
        try {
            SaleService::void($id, $alasan);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), [], 409);
        }

        return $this->ok(null, 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }

    public function receipt(int $id)
    {
        $sale = $this->saleHeader($id);
        if (! $sale) {
            return $this->response->setStatusCode(404)->setBody('Transaksi tidak ditemukan.');
        }
        $items = db_connect()->table('sale_items si')->select('p.name, si.quantity, si.unit_price, si.discount_amount, si.total_amount')
            ->join('products p', 'p.id = si.product_id')->where('si.sale_id', $id)->get()->getResultArray();
        $store = db_connect()->table('stores')->where('id', $sale['store_id'])->get()->getRowArray();

        return view('sales/receipt', ['sale' => $sale, 'items' => $items, 'store' => $store]);
    }

    private function saleHeader(int $id): ?array
    {
        return db_connect()->table('sales s')
            ->select("s.*, IFNULL(c.name,'Umum') AS customer_name, IFNULL(u.full_name,'-') AS cashier_name,
                      IFNULL((SELECT py.payment_method FROM payments py WHERE py.sale_id = s.id LIMIT 1),'-') AS payment_method", false)
            ->join('customers c', 'c.id = s.customer_id', 'left')->join('users u', 'u.id = s.cashier_id', 'left')
            ->where('s.id', $id)->where('s.store_id', current_store_id())->get()->getRowArray();
    }
}
