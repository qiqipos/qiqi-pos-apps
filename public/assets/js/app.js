/**
 * Utilitas bersama: AJAX (dengan CSRF), format Indonesia, Toastr, SweetAlert, Select2, DataTables.
 */
const App = (() => {
  const baseUrl = ($('meta[name="base-url"]').attr('content') || '/').replace(/\/?$/, '/');
  const csrf = () => $('meta[name="csrf-token"]').attr('content');

  const nf = new Intl.NumberFormat('id-ID');
  const nf3 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 });

  const LABELS = {
    status: {
      COMPLETED: ['Selesai', 'success'], VOID: ['Dibatalkan', 'danger'], DRAFT: ['Draf', 'secondary'],
      RETURNED: ['Diretur', 'info'], PARTIALLY_RETURNED: ['Retur Sebagian', 'info'],
      ORDERED: ['Dipesan', 'warning'], PARTIAL: ['Diterima Sebagian', 'info'], RECEIVED: ['Diterima', 'success'], CANCELLED: ['Dibatalkan', 'secondary'],
    },
    payment: { CASH: 'Tunai', CARD: 'Kartu', QRIS: 'QRIS', BANK_TRANSFER: 'Transfer Bank', E_WALLET: 'E-Wallet' },
    movement: {
      PURCHASE: ['Pembelian', 'success'], SALE: ['Penjualan', 'primary'], SALE_RETURN: ['Retur/Batal Jual', 'info'],
      TRANSFER_IN: ['Transfer Masuk', 'success'], TRANSFER_OUT: ['Transfer Keluar', 'warning'],
      ADJUSTMENT_IN: ['Penyesuaian Masuk', 'success'], ADJUSTMENT_OUT: ['Penyesuaian Keluar', 'danger'], STOCK_OPNAME: ['Stok Opname', 'secondary'],
    },
    stock: { HABIS: ['Habis', 'danger'], MENIPIS: ['Menipis', 'warning'], AMAN: ['Aman', 'success'] },
  };

  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const badge = (txt, color) => `<span class="badge text-bg-${color}">${esc(txt)}</span>`;
  const parseDate = v => new Date(String(v).replace(' ', 'T'));

  const fmt = {
    rupiah: v => 'Rp ' + nf.format(Number(v) || 0),
    number: v => nf3.format(Number(v) || 0),
    date: v => v ? parseDate(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-',
    datetime: v => v ? parseDate(v).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-',
  };

  /** Renderer kolom DataTables menurut tipe. */
  const renderers = {
    text: v => esc(v),
    html: v => v ?? '',
    rupiah: v => fmt.rupiah(v),
    number: v => fmt.number(v),
    date: v => fmt.date(v),
    datetime: v => fmt.datetime(v),
    active: v => Number(v) === 1 ? badge('Aktif', 'success') : badge('Nonaktif', 'secondary'),
    status: v => { const s = LABELS.status[v] || [v, 'secondary']; return badge(s[0], s[1]); },
    payment: v => esc(LABELS.payment[v] || v),
    movement: v => { const s = LABELS.movement[v] || [v, 'secondary']; return badge(s[0], s[1]); },
    stockstate: v => { const s = LABELS.stock[v] || [v, 'secondary']; return badge(s[0], s[1]); },
    signed: v => { const n = Number(v); return `<span class="fw-semibold text-${n < 0 ? 'danger' : 'success'}">${n > 0 ? '+' : ''}${nf3.format(n)}</span>`; },
  };
  const rightAligned = ['rupiah', 'number', 'signed'];

  const dtLanguage = {
    search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data', infoFiltered: '(disaring dari _MAX_ data)', zeroRecords: 'Data tidak ditemukan',
    emptyTable: 'Belum ada data', processing: 'Memuat data…', loadingRecords: 'Memuat…',
    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
  };

  toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3500 };

  /** Permintaan AJAX JSON (menyertakan CSRF), error ditangani seragam. */
  function request(method, url, data, { silent = false } = {}) {
    const opts = { url, method, dataType: 'json', headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } };
    if (data !== undefined && method !== 'GET') { opts.contentType = 'application/json'; opts.data = JSON.stringify(data); }
    else if (data) { opts.data = data; }
    return $.ajax(opts).fail(xhr => { if (!silent) handleError(xhr); });
  }

  function handleError(xhr) {
    const msg = xhr.responseJSON?.message;
    if (xhr.status === 401) { toastr.warning(msg || 'Sesi berakhir.'); setTimeout(() => location.href = baseUrl + 'login', 800); return; }
    if (xhr.status === 422) { return; } // validasi: ditampilkan pada form oleh pemanggil, kecuali tanpa errors
    if (xhr.status === 0) { toastr.error('Tidak dapat terhubung ke server. Periksa koneksi Anda.'); return; }
    toastr.error(msg || (xhr.status === 403 ? 'Akses ditolak atau sesi keamanan kedaluwarsa. Muat ulang halaman.' : 'Terjadi kesalahan pada server (' + xhr.status + ').'));
  }

  /** Tampilkan pesan 422 sebagai toast bila tidak ada error per-field. */
  function validationFeedback(xhr, $form) {
    if (xhr.status !== 422) return;
    const errs = xhr.responseJSON?.errors || {};
    if ($form && Object.keys(errs).length) {
      Object.entries(errs).forEach(([name, text]) => {
        const $el = $form.find(`[name="${name}"]`).addClass('is-invalid');
        const $anchor = $el.hasClass('select2-hidden-accessible') ? $el.next('.select2') : $el;
        $anchor.after(`<div class="invalid-feedback d-block">${esc(text)}</div>`);
      });
    }
    toastr.error(xhr.responseJSON?.message || 'Periksa kembali isian Anda.');
  }

  const clearErrors = $form => { $form.find('.is-invalid').removeClass('is-invalid'); $form.find('.invalid-feedback').remove(); };

  /** Inisialisasi Select2 (AJAX ke /lookup/{sumber}) pada elemen <select>. */
  function select2($el, { source, placeholder = 'Pilih…', allowClear = false, parent = null, minimumInputLength = 0 } = {}) {
    $el.select2({
      theme: 'bootstrap-5', width: '100%', placeholder, allowClear, minimumInputLength,
      dropdownParent: parent, language: {
        noResults: () => 'Data tidak ditemukan', searching: () => 'Mencari…', loadingMore: () => 'Memuat lainnya…',
        inputTooShort: a => `Ketik minimal ${a.minimum - a.input.length} karakter lagi`, errorLoading: () => 'Gagal memuat data',
      },
      ajax: {
        url: baseUrl + 'lookup/' + source, dataType: 'json', delay: 250, cache: true,
        data: p => ({ q: p.term || '', page: p.page || 1 }),
        processResults: (d, p) => ({ results: d.results, pagination: { more: d.pagination.more } }),
      },
    });
  }

  /** Dialog konfirmasi SweetAlert; resolve true bila disetujui. */
  const confirm = ({ title, text, confirmText = 'Ya, lanjutkan', icon = 'warning', danger = false }) =>
    Swal.fire({ title, text, icon, showCancelButton: true, confirmButtonText: confirmText, cancelButtonText: 'Batal',
      confirmButtonColor: danger ? '#dc3545' : '#0d6efd', reverseButtons: true, focusCancel: danger }).then(r => r.isConfirmed);

  /** Buat DataTable server-side/ajax dengan setelan standar aplikasi. */
  function dataTable(selector, cfg) {
    return new DataTable(selector, Object.assign({
      processing: true, language: dtLanguage, searchDelay: 400, pageLength: 10, lengthMenu: [10, 25, 50, 100], autoWidth: false,
    }, cfg));
  }

  /** Definisi kolom dari konfigurasi server. */
  const columnDefs = cols => cols.map(c => ({
    data: c.data, title: c.title, orderable: c.orderable !== false,
    className: rightAligned.includes(c.type) ? 'text-end' : '',
    render: (v, t) => t === 'display' ? (renderers[c.type] || renderers.text)(v) : v,
  }));

  $.ajaxSetup({ headers: { 'X-Requested-With': 'XMLHttpRequest' } });

  return { url: p => baseUrl + String(p).replace(/^\//, ''), csrf, request, validationFeedback, clearErrors, select2, confirm, dataTable, columnDefs, fmt, esc, badge, renderers, LABELS };
})();
