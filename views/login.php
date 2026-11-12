<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#072266">
  <title>Login - SI-KEMAS</title>
  <link rel="icon" type="image/png" href="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="login">
  <div class="loginbox text-center">
    <img class="login-logo" src="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>" alt="Logo DPPKB OKU Timur">
    <h2 class="fw-bold mb-1">SI-KEMAS</h2>
    <p class="login-subtitle">
      Sistem Informasi Keluar Masuk Surat<br>
      <strong>DPPKB Kabupaten OKU Timur</strong>
    </p>

    <?php if (!empty($err)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 text-start py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><?= e($err) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" class="text-start">
      <?= csrf() ?>
      <div class="mb-3">
        <label class="form-label" for="loginUsername">Username atau Email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input
            id="loginUsername"
            class="form-control"
            type="text"
            name="username"
            placeholder="Masukkan username"
            value="<?= e($_POST['username'] ?? '') ?>"
            required
            autofocus
          >
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label" for="loginPassword">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input
            id="loginPassword"
            class="form-control"
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
          >
          <button
            class="btn btn-outline-secondary"
            type="button"
            data-toggle-password="loginPassword"
            aria-label="Tampilkan atau sembunyikan password"
          >
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-box-arrow-in-right"></i> Masuk ke Aplikasi
      </button>
    </form>

    <div class="mt-4 pt-3 border-top small text-secondary">
      &copy; <?= date('Y') ?> SI-KEMAS &middot; DPPKB Kab. OKU Timur
    </div>
  </div>

  <script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>

