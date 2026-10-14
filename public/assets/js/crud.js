/** Halaman CRUD generik: DataTables (server-side) + modal form + Select2 + SweetAlert. */
(() => {
  const C = window.CRUD;
  const api = App.url(C.route);
  const $modal = $('#crud-modal'), $form = $('#crud-form');
  let editId = null;

  // ---- DataTable -----------------------------------------------------------
  const cols = App.columnDefs(C.columns);
  const rowActions = C.rowActions || [];
  if (!C.readOnly && rowActions.length) {
    cols.push({
      data: null, title: 'Aksi', orderable: false, className: 'text-nowrap',
      render: (_, t, row) => `<div class="btn-group">
        ${rowActions.includes('edit') ? `<button class="btn btn-sm btn-outline-primary" data-act="edit" data-id="${row.id}" title="Ubah"><i class="fa-solid fa-pen"></i></button>` : ''}
        ${rowActions.includes('delete') ? `<button class="btn btn-sm btn-outline-danger" data-act="delete" data-id="${row.id}" title="Hapus"><i class="fa-solid fa-trash"></i></button>` : ''}
      </div>`,
    });
  }
  window.CrudTable = App.dataTable('#tbl', {
    serverSide: true, order: [], columns: cols,
    ajax: { url: api + '/datatable', type: 'GET', error: xhr => { if (xhr.status === 401) location.href = App.url('login'); else toastr.error('Gagal memuat data.'); } },
  });
  const reload = (keepPage = true) => window.CrudTable.ajax.reload(null, !keepPage);

  if (C.readOnly && !C.fields.length) return;

  // ---- Form ------------------------------------------------------------------
  const fieldHtml = f => {
    const id = 'f_' + f.name, col = f.col || 12;
    let input;
    switch (f.type) {
      case 'textarea': input = `<textarea class="form-control" id="${id}" name="${f.name}" rows="3"></textarea>`; break;
      case 'select': input = `<select class="form-select" id="${id}" name="${f.name}"></select>`; break;
      case 'switch':
        return `<div class="col-md-${col}"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="${id}" name="${f.name}" checked><label class="form-check-label" for="${id}">${App.esc(f.label)}</label></div></div>`;
      case 'number': input = `<input class="form-control" type="number" step="any" min="0" id="${id}" name="${f.name}" inputmode="decimal">`; break;
      case 'date': input = `<input class="form-control" type="date" id="${id}" name="${f.name}">`; break;
      case 'password': input = `<input class="form-control" type="password" id="${id}" name="${f.name}" autocomplete="new-password">`; break;
      default: input = `<input class="form-control" type="text" id="${id}" name="${f.name}" autocomplete="off">`;
    }
    return `<div class="col-md-${col}"><label class="form-label" for="${id}">${App.esc(f.label)}</label>${input}${f.hint ? `<div class="form-text">${App.esc(f.hint)}</div>` : ''}</div>`;
  };
  $('#crud-fields').html(C.fields.map(fieldHtml).join(''));
  C.fields.filter(f => f.type === 'select').forEach(f =>
    App.select2($form.find(`[name="${f.name}"]`), { source: f.source, parent: $modal, allowClear: !!f.nullable, placeholder: 'Pilih ' + f.label.toLowerCase() }));

  function resetForm() {
    App.clearErrors($form);
    $form[0].reset();
    $form.find('select').each(function () { $(this).empty().trigger('change'); });
    C.fields.filter(f => f.type === 'switch').forEach(f => $form.find(`[name="${f.name}"]`).prop('checked', true));
    C.fields.filter(f => f.type === 'date').forEach(f => $form.find(`[name="${f.name}"]`).val(new Date().toLocaleDateString('en-CA')));
  }

  function openCreate() {
    editId = null; resetForm();
    $('#crud-modal-title').text('Tambah ' + C.title);
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
  }

  function openEdit(id) {
    App.request('GET', `${api}/${id}`).done(res => {
      editId = id; resetForm();
      const { row, labels } = res.data;
      C.fields.forEach(f => {
        const $el = $form.find(`[name="${f.name}"]`);
        if (f.type === 'switch') $el.prop('checked', Number(row[f.name]) === 1);
        else if (f.type === 'select') { const l = labels[f.name]; if (l) $el.append(new Option(l.text, l.id, true, true)).trigger('change'); }
        else if (f.type !== 'password') $el.val(row[f.name] ?? '');
      });
      $('#crud-modal-title').text('Ubah ' + C.title);
      bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });
  }

  $form.on('submit', function (e) {
    e.preventDefault();
    App.clearErrors($form);
    const data = {};
    C.fields.forEach(f => {
      const $el = $form.find(`[name="${f.name}"]`);
      data[f.name] = f.type === 'switch' ? $el.is(':checked') : $el.val();
    });
    const $btn = $form.find('[type=submit]').prop('disabled', true);
    App.request(editId ? 'PUT' : 'POST', editId ? `${api}/${editId}` : api, data)
      .done(res => { bootstrap.Modal.getInstance($modal[0]).hide(); toastr.success(res.message); reload(); })
      .fail(xhr => App.validationFeedback(xhr, $form))
      .always(() => $btn.prop('disabled', false));
  });

  $('#btn-add').on('click', openCreate);
  $('#tbl').on('click', '[data-act="edit"]', function () { openEdit($(this).data('id')); });
  $('#tbl').on('click', '[data-act="delete"]', async function () {
    const id = $(this).data('id');
    if (!await App.confirm({ title: 'Hapus data ini?', text: 'Data yang dihapus tidak dapat dikembalikan.', confirmText: 'Ya, hapus', danger: true })) return;
    App.request('DELETE', `${api}/${id}`).done(res => { toastr.success(res.message); reload(); });
  });

  if (C.autoOpen) openCreate();
})();
