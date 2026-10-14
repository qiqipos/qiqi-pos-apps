<?php /** Aset CSS bersama (halaman utama dan login). */ ?>
<script>
  // Cegah kedipan tema (dari template AdminLTE)
  (() => { const r = document.documentElement; let s = null; try { s = localStorage.getItem('lte-theme'); } catch {}
    const t = (s === 'dark' || s === 'light') ? s : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    r.setAttribute('data-bs-theme', t); r.style.colorScheme = t; })();
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/adminlte/style.css') ?>">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
