<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card"><div class="card-body text-center py-5">
  <i class="fa-solid fa-person-digging fa-3x text-warning mb-3"></i>
  <h4><?= esc($namaModul) ?> sedang dalam pengembangan</h4>
  <p class="text-muted mb-4">Menu ini sudah terdaftar pada struktur aplikasi, tetapi modulnya belum dibangun pada versi ini.</p>
  <a href="<?= site_url('dashboard') ?>" class="btn btn-primary"><i class="fa-solid fa-house me-1"></i> Kembali ke Dashboard</a>
</div></div>
<?= $this->endSection() ?>
