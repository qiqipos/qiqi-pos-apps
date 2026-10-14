<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card mb-3"><div class="card-body">
  <form class="row g-2 align-items-end" id="filter">
    <?php if ($config['dates']): ?>
      <div class="col-6 col-md-3 col-lg-2"><label class="form-label mb-1" for="from">Dari tanggal</label><input type="date" class="form-control" id="from" value="<?= date('Y-m-01') ?>"></div>
      <div class="col-6 col-md-3 col-lg-2"><label class="form-label mb-1" for="to">Sampai tanggal</label><input type="date" class="form-control" id="to" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Terapkan</button></div>
      <div class="col-auto btn-group">
        <button class="btn btn-outline-secondary" type="button" data-range="7">7 hari</button>
        <button class="btn btn-outline-secondary" type="button" data-range="30">30 hari</button>
        <button class="btn btn-outline-secondary" type="button" data-range="month">Bulan ini</button>
      </div>
    <?php else: ?>
      <div class="col"><span class="text-muted">Laporan ini menampilkan kondisi stok saat ini.</span></div>
    <?php endif; ?>
    <div class="col-auto ms-auto"><button class="btn btn-outline-dark" type="button" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Cetak</button></div>
  </form>
</div></div>
<div class="row g-3 mb-3" id="summary"></div>
<div class="card mb-3"><div class="card-header"><h3 class="card-title"><?= esc($config['chart']['label']) ?></h3></div><div class="card-body"><div class="chart-box"><canvas id="chart"></canvas></div></div></div>
<div class="card"><div class="card-body"><div class="table-responsive"><table id="tbl" class="table table-striped table-hover align-middle w-100"></table></div></div></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
  const C = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0', '#fd7e14', '#20c997', '#6610f2', '#d63384'];
  const fmtRp = v => App.fmt.rupiah(v);
  let chart;

  const params = () => C.dates ? { from: $('#from').val(), to: $('#to').val() } : {};

  const table = App.dataTable('#tbl', {
    columns: App.columnDefs(C.columns), order: [], paging: C.name !== 'profit-loss', searching: C.name !== 'profit-loss',
    ordering: C.name !== 'profit-loss', info: C.name !== 'profit-loss',
    ajax: (data, callback) => {
      App.request('GET', App.url(`reports/${C.name}/data`), params()).done(res => {
        const d = res.data;
        $('#summary').html(d.summary.map((s, i) => `<div class="col-md-4"><div class="card border-${['primary', 'success', 'warning'][i % 3]} h-100"><div class="card-body"><div class="text-muted small">${App.esc(s.label)}</div><div class="stat-value">${s.type === 'rupiah' ? fmtRp(s.value) : App.fmt.number(s.value)}</div></div></div></div>`).join(''));
        chart?.destroy();
        const isDo = C.chart.type === 'doughnut';
        chart = new Chart(document.getElementById('chart'), {
          type: C.chart.type,
          data: { labels: d.chart.labels, datasets: [{ label: C.chart.label, data: d.chart.values, backgroundColor: isDo || C.chart.type === 'bar' ? palette : 'rgba(13,110,253,.15)', borderColor: palette[0], fill: C.chart.type === 'line', tension: .3 }] },
          options: { maintainAspectRatio: false, plugins: { legend: { display: isDo, position: 'bottom' }, tooltip: { callbacks: { label: c => `${c.label}: ${fmtRp(c.parsed.y ?? c.parsed)}` } } },
            scales: isDo ? {} : { y: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v) } } } },
        });
        callback({ data: d.rows });
      }).fail(() => callback({ data: [] }));
    },
    createdRow: (row, data) => { if (data.uraian && /^[A-Z ]+$/.test(data.uraian)) $(row).addClass('fw-bold table-active'); },
  });

  $('#filter').on('submit', e => { e.preventDefault(); table.ajax.reload(); });
  $('[data-range]').on('click', function () {
    const r = $(this).data('range'), end = new Date(), start = new Date();
    if (r === 'month') start.setDate(1); else start.setDate(end.getDate() - (Number(r) - 1));
    $('#from').val(start.toLocaleDateString('en-CA')); $('#to').val(end.toLocaleDateString('en-CA')); table.ajax.reload();
  });
})();
</script>
<?= $this->endSection() ?>
