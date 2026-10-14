<div class="modal fade" id="detail-modal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Detail Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="detail-body"></div></div></div></div>
<script>
$('#tbl').on('click', '[data-act="detail"]', function () {
  App.request('GET', App.url(`sales/${$(this).data('id')}/detail`)).done(({ data: { sale: s, items } }) => {
    $('#detail-body').html(`<div class="row mb-2"><div class="col-md-6"><b>${App.esc(s.invoice_number)}</b><br>${App.fmt.datetime(s.sale_date)}<br>Kasir: ${App.esc(s.cashier_name)}<br>Pelanggan: ${App.esc(s.customer_name)}</div>
      <div class="col-md-6 text-md-end">${App.renderers.status(s.status)}<br>Pembayaran: ${App.esc(App.LABELS.payment[s.payment_method] || s.payment_method)}${s.notes ? `<br><span class="text-muted small">${App.esc(s.notes)}</span>` : ''}</div></div>
      <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Total</th></tr></thead><tbody>${items.map(i =>
        `<tr><td>${App.esc(i.name)}</td><td class="text-end">${App.fmt.number(i.quantity)} ${App.esc(i.unit)}</td><td class="text-end">${App.fmt.rupiah(i.unit_price)}</td><td class="text-end">${App.fmt.rupiah(i.discount_amount)}</td><td class="text-end">${App.fmt.rupiah(i.total_amount)}</td></tr>`).join('')}</tbody></table></div>
      <div class="text-end">Subtotal ${App.fmt.rupiah(s.subtotal)} · Diskon ${App.fmt.rupiah(s.discount_amount)} · Pajak ${App.fmt.rupiah(s.tax_amount)}<div class="fs-5 fw-bold">Total ${App.fmt.rupiah(s.total_amount)}</div>Bayar ${App.fmt.rupiah(s.paid_amount)} · Kembali ${App.fmt.rupiah(s.change_amount)}</div>`);
    bootstrap.Modal.getOrCreateInstance('#detail-modal').show();
  });
});
$('#tbl').on('click', '[data-act="void"]', async function () {
  const id = $(this).data('id');
  const r = await Swal.fire({ title: `Batalkan ${$(this).data('label')}?`, text: 'Stok akan dikembalikan. Tindakan ini tidak dapat dibatalkan.', icon: 'warning', input: 'text',
    inputPlaceholder: 'Alasan pembatalan', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Kembali', confirmButtonColor: '#dc3545',
    inputValidator: v => (!v || v.trim().length < 3) ? 'Alasan wajib diisi (min. 3 karakter).' : null });
  if (!r.isConfirmed) return;
  App.request('POST', App.url(`sales/${id}/void`), { reason: r.value }).done(res => { toastr.success(res.message); window.CrudTable.ajax.reload(null, false); })
    .fail(xhr => { if (xhr.status === 422) toastr.error(xhr.responseJSON.message); });
});
</script>
