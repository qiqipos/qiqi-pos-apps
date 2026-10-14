<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center">
    <h3 class="card-title mb-0 me-auto"><?= esc($config['title']) ?></h3>
    <?php foreach ($config['toolbar'] as $t): ?>
      <a class="btn btn-outline-primary btn-sm" href="<?= site_url($t['href']) ?>"><i class="<?= esc($t['icon']) ?> me-1"></i><?= esc($t['label']) ?></a>
    <?php endforeach; ?>
    <?php if (! $config['readOnly']): ?>
      <button type="button" class="btn btn-primary btn-sm" id="btn-add"><i class="fa-solid fa-plus me-1"></i>Tambah</button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tbl" class="table table-striped table-hover align-middle w-100"></table>
    </div>
  </div>
</div>

<?php if (! $config['readOnly']): ?>
<div class="modal fade" id="crud-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" id="crud-form" novalidate>
      <div class="modal-header"><h5 class="modal-title" id="crud-modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
      <div class="modal-body"><div class="row g-3" id="crud-fields"></div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>window.CRUD = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= base_url('assets/js/crud.js') ?>"></script>
<?= $extraView ? view($extraView) : '' ?>
<?= $this->endSection() ?>
