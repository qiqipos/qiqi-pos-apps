<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <div class="input-group mb-3">
        <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
        <input type="search" id="q" class="form-control form-control-lg" placeholder="Scan barcode / cari nama atau SKU lalu Enter" autocomplete="off" autofocus>
      </div>
      <div class="pos-products"><div class="row g-2" id="grid"></div></div>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <div class="pos-cart mb-2"><table class="table table-sm align-middle mb-0"><thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th><th></th></tr></thead><tbody id="cart"></tbody></table></div>
      <div class="mb-2"><label class="form-label mb-1 small">Pelanggan (opsional)</label><select id="customer" class="form-select"></select></div>
      <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label mb-1 small">Diskon (Rp)</label><input type="number" min="0" step="any" id="discount" class="form-control" value="0"></div>
        <div class="col-6"><label class="form-label mb-1 small">Metode bayar</label><select id="method" class="form-select"></select></div>
      </div>
      <div class="d-flex justify-content-between small"><span>Subtotal</span><span id="t-sub">Rp 0</span></div>
      <div class="d-flex justify-content-between small"><span>Diskon</span><span id="t-disc">Rp 0</span></div>
      <div class="d-flex justify-content-between small"><span>Pajak</span><span id="t-tax">Rp 0</span></div>
      <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1"><span class="fw-semibold">TOTAL</span><span class="pos-total text-primary" id="t-total">Rp 0</span></div>
      <div id="cash-box" class="mt-2">
        <label class="form-label mb-1 small">Uang diterima (Rp)</label>
        <input type="number" min="0" step="any" id="paid" class="form-control form-control-lg mb-1">
        <div class="btn-group btn-group-sm mb-1 w-100"><button type="button" class="btn btn-outline-secondary" id="b-exact">Uang pas</button><button type="button" class="btn btn-outline-secondary" data-cash="50000">50rb</button><button type="button" class="btn btn-outline-secondary" data-cash="100000">100rb</button></div>
        <div class="d-flex justify-content-between"><span>Kembalian</span><strong id="t-change">Rp 0</strong></div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-outline-danger" id="b-clear" title="Kosongkan"><i class="fa-solid fa-trash"></i></button>
        <button class="btn btn-success btn-lg flex-grow-1" id="b-pay"><i class="fa-solid fa-circle-check me-1"></i>Bayar (F9)</button>
      </div>
    </div></div>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/pos.js') ?>"></script>
<?= $this->endSection() ?>
