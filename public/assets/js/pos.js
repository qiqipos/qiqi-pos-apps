/** Kasir: pencarian produk, keranjang, perhitungan, pembayaran. Server tetap menghitung ulang semua harga. */
(() => {
  const cart = new Map(); // id -> {id,name,price,tax,stock,qty}
  const rp = App.fmt.rupiah, num = v => Number(v) || 0;
  let lastResults = [];

  App.select2($('#customer'), { source: 'customers', placeholder: 'Pelanggan umum', allowClear: true });
  App.select2($('#method'), { source: 'payment_methods', placeholder: 'Metode' });
  $('#method').append(new Option('Tunai', 'CASH', true, true)).trigger('change');

  function totals() {
    let sub = 0, net = 0; const rows = [...cart.values()];
    rows.forEach(i => { i.gross = i.price * i.qty; sub += i.gross; });
    const disc = Math.min(Math.max(num($('#discount').val()), 0), sub);
    let tax = 0;
    rows.forEach(i => { const alok = sub > 0 ? disc * i.gross / sub : 0; tax += Math.round((i.gross - alok) * i.tax / 100); });
    return { sub, disc, tax, total: sub - disc + tax };
  }

  function render() {
    const $c = $('#cart').empty();
    if (!cart.size) $c.html('<tr><td colspan="4" class="text-center text-muted py-4">Keranjang kosong</td></tr>');
    cart.forEach(i => $c.append(`<tr data-id="${i.id}"><td>${App.esc(i.name)}<div class="small text-muted">${rp(i.price)}</div></td>
      <td class="text-center"><input type="number" class="form-control form-control-sm qty-input d-inline-block" min="0.001" step="any" value="${i.qty}"></td>
      <td class="text-end">${rp(i.price * i.qty)}</td><td><button class="btn btn-sm btn-link text-danger p-0" data-rm="${i.id}"><i class="fa-solid fa-xmark"></i></button></td></tr>`));
    const t = totals();
    $('#t-sub').text(rp(t.sub)); $('#t-disc').text(rp(t.disc)); $('#t-tax').text(rp(t.tax)); $('#t-total').text(rp(t.total));
    const cash = $('#method').val() === 'CASH';
    $('#cash-box').toggle(cash);
    $('#t-change').text(rp(Math.max(0, num($('#paid').val()) - t.total)));
  }

  function add(p) {
    if (num(p.stock) <= 0) return toastr.warning(`Stok "${p.name}" habis.`);
    const cur = cart.get(p.id);
    if (cur) { if (cur.qty + 1 > num(p.stock)) return toastr.warning('Jumlah melebihi stok tersedia.'); cur.qty += 1; }
    else cart.set(p.id, { id: p.id, name: p.name, price: num(p.selling_price), tax: num(p.tax_rate), stock: num(p.stock), qty: 1 });
    render();
  }

  function loadProducts(q = '') {
    return App.request('GET', App.url('sales/products'), { q }).done(res => {
      lastResults = res.data;
      $('#grid').html(res.data.map(p => `<div class="col-6 col-md-4 col-xl-3"><div class="card pos-product ${num(p.stock) <= 0 ? 'out' : ''}" data-id="${p.id}"><div class="card-body p-2">
        <div class="fw-semibold small">${App.esc(p.name)}</div><div class="text-primary fw-bold">${rp(p.selling_price)}</div>
        <div class="small text-muted">Stok: ${App.fmt.number(p.stock)} ${App.esc(p.unit)}</div></div></div></div>`).join('') || '<div class="col-12 text-center text-muted py-4">Produk tidak ditemukan</div>');
    });
  }

  let timer;
  $('#q').on('input', function () { clearTimeout(timer); const v = this.value.trim(); timer = setTimeout(() => loadProducts(v), 300); })
    .on('keydown', async function (e) {
      if (e.key !== 'Enter') return; e.preventDefault();
      const v = this.value.trim(); if (!v) return;
      await loadProducts(v);
      const exact = lastResults.find(p => p.barcode === v || p.sku.toLowerCase() === v.toLowerCase()) || (lastResults.length === 1 ? lastResults[0] : null);
      if (exact) { add(exact); this.value = ''; loadProducts(); } else toastr.info('Pilih produk dari daftar.');
    });
  $('#grid').on('click', '.pos-product', function () { const p = lastResults.find(x => String(x.id) === String($(this).data('id'))); if (p) add(p); });
  $('#cart').on('change', '.qty-input', function () {
    const i = cart.get(String($(this).closest('tr').data('id')) ) || cart.get(Number($(this).closest('tr').data('id')));
    let v = num($(this).val()); if (v <= 0) { cart.delete(i.id); } else { if (v > i.stock) { toastr.warning('Jumlah melebihi stok, disesuaikan.'); v = i.stock; } i.qty = v; }
    render();
  }).on('click', '[data-rm]', function () { cart.delete(String($(this).data('rm'))); render(); });
  $('#discount, #paid').on('input', render);
  $('#method').on('change', render);
  $('#b-exact').on('click', () => { $('#paid').val(totals().total); render(); });
  $('[data-cash]').on('click', function () { $('#paid').val($(this).data('cash')); render(); });
  $('#b-clear').on('click', async () => { if (cart.size && await App.confirm({ title: 'Kosongkan keranjang?', danger: true, confirmText: 'Ya, kosongkan' })) { cart.clear(); render(); } });

  async function pay() {
    if (!cart.size) return toastr.warning('Keranjang masih kosong.');
    const t = totals(), cash = $('#method').val() === 'CASH';
    if (cash && num($('#paid').val()) < t.total) return toastr.warning('Uang diterima kurang dari total.');
    const $b = $('#b-pay').prop('disabled', true);
    App.request('POST', App.url('sales'), {
      items: [...cart.values()].map(i => ({ product_id: i.id, quantity: i.qty })),
      customer_id: $('#customer').val() || null, discount: num($('#discount').val()),
      payment_method: $('#method').val(), paid_amount: num($('#paid').val()),
    }).done(async res => {
      const d = res.data;
      const r = await Swal.fire({ icon: 'success', title: 'Transaksi berhasil', html: `<div>${App.esc(d.invoice_number)}</div><div class="fs-4 fw-bold mt-2">Total ${rp(d.total)}</div><div>Kembalian <b>${rp(d.change)}</b></div>`,
        showCancelButton: true, confirmButtonText: '<i class="fa-solid fa-print"></i> Cetak struk', cancelButtonText: 'Transaksi baru' });
      if (r.isConfirmed) window.open(d.receipt_url, '_blank');
      cart.clear(); $('#paid,#discount').val(''); $('#customer').val(null).trigger('change'); render(); loadProducts(); $('#q').trigger('focus');
    }).fail(xhr => { if (xhr.status === 422) Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: xhr.responseJSON?.message }); loadProducts(); })
      .always(() => $b.prop('disabled', false));
  }
  $('#b-pay').on('click', pay);
  $(document).on('keydown', e => { if (e.key === 'F9') { e.preventDefault(); pay(); } });

  render(); loadProducts();
})();
