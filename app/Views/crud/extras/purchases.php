<div class="modal fade" id="detail-modal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Detail Pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="detail-body"></div></div></div></div>
<script>
const reloadT = () => window.CrudTable.ajax.reload(null, false);
$('#tbl').on('click', '[data-act="detail"]', function () {
  App.request('GET', App.url(`purchases/${$(this).data('id')}/detail`)).done(({ data: { po, items } }) => {
    $('#detail-body').html(`<div class="row mb-2"><div class="col-md-6"><b>${App.esc(po.po_number)}</b><br>${App.fmt.datetime(po.order_date)}<br>Supplier: ${App.esc(po.supplier_name)}</div><div class="col-md-6 text-md-end">${App.renderers.status(po.status)}${po.notes ? `<br><span class="small text-muted">${App.esc(po.notes)}</span>` : ''}</div></div>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th class="text-end">Dipesan</th><th class="text-end">Diterima</th><th class="text-end">Harga Beli</th><th class="text-end">Subtotal</th></tr></thead><tbody>${items.map(i =>
      `<tr><td>${App.esc(i.name)}</td><td class="text-end">${App.fmt.number(i.quantity)} ${App.esc(i.unit)}</td><td class="text-end">${App.fmt.number(i.received_quantity)}</td><td class="text-end">${App.fmt.rupiah(i.unit_cost)}</td><td class="text-end">${App.fmt.rupiah(i.subtotal)}</td></tr>`).join('')}</tbody></table></div>
      <div class="text-end fs-5 fw-bold">Total ${App.fmt.rupiah(po.total_amount)}</div>`);
    bootstrap.Modal.getOrCreateInstance('#detail-modal').show();
  });
});
$('#tbl').on('click', '[data-act="receive"]', async function () {
  const id = $(this).data('id');
  if (!await App.confirm({ title: `Terima barang ${$(this).data('label')}?`, text: 'Stok akan bertambah dan harga beli produk diperbarui.', icon: 'question', confirmText: 'Ya, terima' })) return;
  App.request('POST', App.url(`purchases/${id}/receive`)).done(r => { toastr.success(r.message); reloadT(); });
});
$('#tbl').on('click', '[data-act="cancel"]', async function () {
  const id = $(this).data('id');
  if (!await App.confirm({ title: `Batalkan ${$(this).data('label')}?`, danger: true, confirmText: 'Ya, batalkan' })) return;
  App.request('POST', App.url(`purchases/${id}/cancel`)).done(r => { toastr.success(r.message); reloadT(); });
});
</script>
