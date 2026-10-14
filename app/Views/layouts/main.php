<?php
/** Layout utama (berbasis AdminLTE 4). Section: content, scripts. */
$user = current_user();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= esc($title ?? 'Qiqi POS') ?> | Qiqi POS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="base-url" content="<?= base_url() ?>">
  <?= $this->include('layouts/head_assets') ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Buka/tutup menu"><i class="bi bi-list"></i></a></li>
        <li class="nav-item d-none d-md-block"><a href="<?= site_url('sales/create') ?>" class="nav-link"><i class="fa-solid fa-cash-register me-1"></i> Kasir</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item d-none d-md-block"><span class="nav-link text-muted"><i class="fa-solid fa-store me-1"></i><?= esc(db_connect()->table('stores')->select('name')->where('id', current_store_id())->get()->getRow('name') ?? '-') ?></span></li>
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-user me-1"></i><span class="d-none d-md-inline"><?= esc($user['full_name'] ?? '') ?></span></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2"><div class="fw-semibold"><?= esc($user['full_name'] ?? '') ?></div><div class="small text-muted"><?= esc($user['role_name'] ?? '') ?></div></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="<?= site_url('logout') ?>" method="post" class="px-3 pb-2">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</button>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>

  <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
      <a href="<?= site_url('dashboard') ?>" class="brand-link"><i class="fa-solid fa-cash-register brand-image fs-4 ms-2 me-2"></i><span class="brand-text fw-light">Qiqi POS</span></a>
    </div>
    <div class="sidebar-wrapper">
      <nav class="mt-2" aria-label="Menu utama">
        <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">
          <?php foreach ($menuTree as $m): ?>
            <?php $path = $m['path'] ? '/' . trim($m['path'], '/') : null; ?>
            <?php if (empty($m['children'])): ?>
              <li class="nav-item">
                <a href="<?= site_url(trim((string) $m['path'], '/')) ?>" class="nav-link <?= $path === $activePath ? 'active' : '' ?>">
                  <i class="nav-icon <?= esc($m['icon'] ?: 'fa-solid fa-circle') ?>"></i><p><?= esc($m['name']) ?></p>
                </a>
              </li>
            <?php else: ?>
              <?php $open = in_array($activePath, array_map(static fn ($c) => '/' . trim((string) $c['path'], '/'), $m['children']), true); ?>
              <li class="nav-item <?= $open ? 'menu-open' : '' ?>">
                <a href="#" class="nav-link <?= $open ? 'active' : '' ?>">
                  <i class="nav-icon <?= esc($m['icon'] ?: 'fa-solid fa-circle') ?>"></i>
                  <p><?= esc($m['name']) ?><i class="nav-arrow bi bi-chevron-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <?php foreach ($m['children'] as $c): ?>
                    <li class="nav-item">
                      <a href="<?= site_url(trim((string) $c['path'], '/')) ?>" class="nav-link <?= '/' . trim((string) $c['path'], '/') === $activePath ? 'active' : '' ?>">
                        <i class="nav-icon <?= esc($c['icon'] ?: 'fa-regular fa-circle') ?>"></i><p><?= esc($c['name']) ?></p>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </aside>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6"><h1 class="mb-0 fs-3"><?= esc($title ?? '') ?></h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
              <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Beranda</a></li>
              <li class="breadcrumb-item active" aria-current="page"><?= esc($title ?? '') ?></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    <div class="app-content">
      <div class="container-fluid">
        <?= $this->renderSection('content') ?>
      </div>
    </div>
  </main>

  <footer class="app-footer">
    <div class="float-end d-none d-sm-inline">Qiqi POS</div>
    <strong>&copy; <?= date('Y') ?> Qiqi POS.</strong> Seluruh hak cipta dilindungi.
  </footer>
</div>

<?= $this->include('layouts/foot_assets') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
