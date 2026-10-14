<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card"><div class="card-body">
  <div class="row g-3 mb-3">
    <div class="col-md-5"><label class="form-label">Supplier</label><select id="supplier" class="form-select"></select></div>
    <div class="col-md-7"><label class="form-label">Catatan</label><input id="notes" class="form-control" maxlength="255"></div>
    <div class="col-12"><label class="form-label">Tambah produk</label><select id="product" class="form-select"></select></div>
  </div>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Produk</th><th style="width:140px">Qty</th><th style="width:180px">Harga Beli (Rp)</th><th class="text-end" style="width:170px">Subtotal</th><th></th></tr></thead><tbody id="rows"></tbody>
  <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end" id="total">Rp 0</th><th></th></tr></tfoot></table></div>
  <div class="text-end"><a href="<?= site_url('purchases') ?>" class="btn btn-secondary me-2">Batal</a><button id="save" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Pembelian</button></div>
</div></div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(() => {
  const items = new Map(); const num = v => Number(v) || 0;
  App.select2($('#supplier'), { source: 'suppliers', placeholder: 'Pilih supplier' });
  App.select2($('#product'), { source: 'products', placeholder: 'Cari produk (SKU / nama / barcode)…' });
  const render = () => {
    let total = 0;
    $('#rows').html(items.size ? [...items.values()].map(i => { total += i.qty * i.cost; return `<tr data-id="${i.id}"><td>${App.esc(i.text)}</td>
      <td><input type="number" class="form-control form-control-sm f-qty" min="0.001" step="any" value="${i.qty}"></td>
      <td><input type="number" class="form-control form-control-sm f-cost" min="0" step="any" value="${i.cost}"></td>
      <td class="text-end">${App.fmt.rupiah(i.qty * i.cost)}</td><td><button class="btn btn-sm btn-link text-danger" data-rm="${i.id}"><i class="fa-solid fa-xmark"></i></button></td></tr>`; }).join('')
      : '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada produk</td></tr>');
    $('#total').text(App.fmt.rupiah(total));
  };
  $('#product').on('select2:select', e => {
    const id = Number(e.params.data.id);
    if (!items.has(id)) items.set(id, { id, text: e.params.data.text, qty: 1, cost: 0 });
    $('#product').val(null).trigger('change'); render();
  });
  $('#rows').on('input', 'input', function () {
    const i = items.get(Number($(this).closest('tr').data('id')));
    i.qty = num($('.f-qty', $(this).closest('tr')).val()); i.cost = num($('.f-cost', $(this).closest('tr')).val());
    $(this).closest('tr').find('td:eq(3)').text(App.fmt.rupiah(i.qty * i.cost));
    $('#total').text(App.fmt.rupiah([...items.values()].reduce((s, x) => s + x.qty * x.cost, 0)));
  }).on('click', '[data-rm]', function () { items.delete(Number($(this).data('rm'))); render(); });
  $('#save').on('click', function () {
    if (!$('#supplier').val()) return toastr.warning('Pilih supplier terlebih dahulu.');
    if (!items.size) return toastr.warning('Tambahkan minimal satu produk.');
    const $b = $(this).prop('disabled', true);
    App.request('POST', App.url('purchases'), { supplier_id: $('#supplier').val(), notes: $('#notes').val(),
      items: [...items.values()].map(i => ({ product_id: i.id, quantity: i.qty, unit_cost: i.cost })) })
      .done(r => { toastr.success(r.message); setTimeout(() => location.href = App.url('purchases'), 700); })
      .fail(() => $b.prop('disabled', false));
  });
  render();
})();
</script>
<?= $this->endSection() ?>
