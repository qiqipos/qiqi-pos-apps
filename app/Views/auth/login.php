<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Masuk | Qiqi POS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="base-url" content="<?= base_url() ?>">
  <?= view('layouts/head_assets') ?>
</head>
<body class="login-page bg-body-secondary">
<main class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <h1 class="mb-0"><i class="fa-solid fa-cash-register text-primary me-2"></i><b>POS</b> UMK</h1>
    </div>
    <div class="card-body login-card-body">
      <p class="login-box-msg">Masuk untuk memulai sesi Anda</p>
      <form id="form-login" novalidate>
        <div class="input-group mb-3">
          <div class="form-floating">
            <input id="username" name="username" type="text" class="form-control" placeholder="Username" autocomplete="username" autofocus required>
            <label for="username">Username</label>
          </div>
          <div class="input-group-text"><span class="bi bi-person"></span></div>
        </div>
        <div class="input-group mb-3">
          <div class="form-floating">
            <input id="password" name="password" type="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
            <label for="password">Password</label>
          </div>
          <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
        </div>
        <div class="d-grid"><button type="submit" class="btn btn-primary" id="btn-login"><i class="fa-solid fa-right-to-bracket me-1"></i> Masuk</button></div>
      </form>
    </div>
  </div>
</main>
<?= view('layouts/foot_assets') ?>
<script>
$('#form-login').on('submit', function (e) {
  e.preventDefault();
  const $btn = $('#btn-login').prop('disabled', true);
  App.request('POST', App.url('login'), { username: $('#username').val(), password: $('#password').val() })
    .done(res => { toastr.success(res.message); location.href = res.data.redirect; })
    .fail(() => { $btn.prop('disabled', false); $('#password').val('').trigger('focus'); });
});
</script>
</body>
</html>
