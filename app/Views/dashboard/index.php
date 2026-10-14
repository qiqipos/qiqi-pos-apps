<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row g-3 mb-3" id="cards">
  <?php foreach ([
    ['omzet_hari', 'Omzet Hari Ini', 'fa-sack-dollar', 'primary', 'rupiah'], ['trx_hari', 'Transaksi Hari Ini', 'fa-receipt', 'info', 'number'],
    ['omzet_bulan', 'Omzet Bulan Ini', 'fa-chart-line', 'success', 'rupiah'], ['laba_kotor', 'Laba Kotor Bulan Ini', 'fa-coins', 'warning', 'rupiah'],
    ['pengeluaran', 'Pengeluaran Bulan Ini', 'fa-money-bill-transfer', 'danger', 'rupiah'], ['stok_menipis', 'Produk Stok Menipis', 'fa-triangle-exclamation', 'secondary', 'number'],
  ] as [$key, $label, $icon, $color, $type]): ?>
    <div class="col-6 col-lg-4 col-xl-2">
      <div class="card stat-card border-<?= $color ?> h-100"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small"><?= $label ?></div><div class="stat-value" data-card="<?= $key ?>" data-type="<?= $type ?>">–</div></div>
        <i class="fa-solid <?= $icon ?> stat-icon text-<?= $color ?>"></i>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">Omzet 14 Hari Terakhir</h3></div><div class="card-body"><div class="chart-box"><canvas id="ch-omzet"></canvas></div></div></div></div>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">Metode Pembayaran (30 hari)</h3></div><div class="card-body"><div class="chart-box"><canvas id="ch-bayar"></canvas></div></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Produk Terlaris (30 hari)</h3></div><div class="card-body"><div class="chart-box"><canvas id="ch-terlaris"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">Stok Menipis</h3><div class="card-tools"><a href="<?= site_url('inventory') ?>" class="btn btn-sm btn-outline-primary">Lihat semua</a></div></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Minimum</th></tr></thead><tbody id="low-stock"><tr><td colspan="3" class="text-center text-muted py-3">Memuat…</td></tr></tbody></table></div></div></div></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
  const palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0', '#fd7e14'];
  const rp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
  const charts = {};
  const draw = (id, cfg) => { charts[id]?.destroy(); charts[id] = new Chart(document.getElementById(id), cfg); };

  App.request('GET', App.url('dashboard/data')).done(({ data: d }) => {
    $('[data-card]').each(function () {
      const v = d.cards[$(this).data('card')];
      $(this).text($(this).data('type') === 'rupiah' ? rp(v) : App.fmt.number(v));
    });
    draw('ch-omzet', { type: 'line', data: { labels: d.omzet.labels, datasets: [{ label: 'Omzet', data: d.omzet.values, borderColor: palette[0], backgroundColor: 'rgba(13,110,253,.15)', fill: true, tension: .3 }] },
      options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => rp(c.parsed.y) } } }, scales: { y: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v) } } } } });
    draw('ch-bayar', { type: 'doughnut', data: { labels: d.bayar.labels, datasets: [{ data: d.bayar.values, backgroundColor: palette }] },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => `${c.label}: ${rp(c.parsed)}` } } } } });
    draw('ch-terlaris', { type: 'bar', data: { labels: d.terlaris.labels, datasets: [{ label: 'Terjual', data: d.terlaris.values, backgroundColor: palette[1] }] },
      options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } } });
    $('#low-stock').html(d.stok_menipis.length ? d.stok_menipis.map(r =>
      `<tr><td>${App.esc(r.name)}<div class="small text-muted">${App.esc(r.sku)}</div></td><td class="text-end ${Number(r.quantity) <= 0 ? 'text-danger fw-bold' : ''}">${App.fmt.number(r.quantity)} ${App.esc(r.unit)}</td><td class="text-end">${App.fmt.number(r.min_stock)}</td></tr>`).join('')
      : '<tr><td colspan="3" class="text-center text-muted py-3">Semua stok dalam kondisi aman.</td></tr>');
  });
})();
</script>
<?= $this->endSection() ?>
