<div class="modal fade" id="access-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="access-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="access-body"></div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="access-save"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button></div></div></div></div>
<script>
let roleId = null;
$('#tbl').on('click', '[data-act="access"]', function () {
  roleId = $(this).data('id'); $('#access-title').text('Hak Akses: ' + $(this).data('label'));
  App.request('GET', App.url(`employees/roles/${roleId}/access`)).done(({ data: d }) => {
    const sel = new Set(d.selected), parents = d.modules.filter(m => !m.parent_id);
    $('#access-body').html(parents.map(p => `<div class="mb-3"><div class="form-check fw-bold"><input class="form-check-input chk-parent" type="checkbox" id="m${p.id}" value="${p.id}" ${sel.has(Number(p.id)) ? 'checked' : ''}><label class="form-check-label" for="m${p.id}">${App.esc(p.name)}</label></div>
      <div class="ms-4 row">${d.modules.filter(c => c.parent_id === p.id).map(c => `<div class="col-md-6"><div class="form-check"><input class="form-check-input chk-child" data-parent="${p.id}" type="checkbox" id="m${c.id}" value="${c.id}" ${sel.has(Number(c.id)) ? 'checked' : ''}><label class="form-check-label" for="m${c.id}">${App.esc(c.name)}</label></div></div>`).join('')}</div></div>`).join(''));
    bootstrap.Modal.getOrCreateInstance('#access-modal').show();
  });
});
$('#access-body').on('change', '.chk-parent', function () { $(`.chk-child[data-parent="${this.value}"]`).prop('checked', this.checked); })
  .on('change', '.chk-child', function () { if (this.checked) $(`#m${$(this).data('parent')}`).prop('checked', true); });
$('#access-save').on('click', function () {
  const ids = $('#access-body input:checked').map((_, e) => Number(e.value)).get();
  App.request('PUT', App.url(`employees/roles/${roleId}/access`), { module_ids: ids }).done(r => { toastr.success(r.message); bootstrap.Modal.getInstance('#access-modal').hide(); window.CrudTable.ajax.reload(null, false); });
});
</script>
