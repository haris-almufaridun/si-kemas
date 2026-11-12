<?php

define('SIKEMAS_APP', true);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $pw = (string) ($_POST['password'] ?? '');
    $uname = trim((string) ($_POST['username'] ?? '')) ?: null;
    $email = trim((string) ($_POST['email'] ?? '')) ?: null;

    if (strlen($pw) < 8) {
        $msg = 'Password minimal 8 karakter.';
        $msgType = 'warning';
    } elseif (!$uname && !$email) {
        $msg = 'Username atau email wajib diisi.';
        $msgType = 'warning';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')"
            );
            $stmt->execute([
                trim((string) ($_POST['nama'] ?? '')),
                $uname,
                $email,
                password_hash($pw, PASSWORD_DEFAULT),
            ]);
            $msg = 'Admin berhasil dibuat. Hapus install.php setelah selesai.';
            $msgType = 'success';
        } catch (Throwable $e) {
            $msg = 'Gagal. Pastikan database.sql sudah diimpor dan username/email belum digunakan.';
            $msgType = 'danger';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#072266">
  <title>Setup Admin - SI-KEMAS</title>
  <link rel="icon" type="image/png" href="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="login">
  <div class="loginbox">
    <div class="text-center">
      <img class="login-logo" src="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>" alt="Logo DPPKB OKU Timur">
      <h4 class="fw-bold mb-1">Setup Admin SI-KEMAS</h4>
      <p class="login-subtitle">Pastikan database.sql telah diimpor sebelum membuat akun admin pertama.</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= e($msgType) ?>"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= csrf() ?>
      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input class="form-control" name="nama" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email (opsional)</label>
        <input class="form-control" type="email" name="email">
      </div>
      <div class="mb-4">
        <label class="form-label">Password (minimal 8 karakter)</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-shield-check"></i> Buat Akun Admin
      </button>
    </form>

    <a href="index.php?page=login" class="d-block mt-3 text-center text-decoration-none small fw-semibold">
      <i class="bi bi-arrow-left"></i> Kembali ke halaman login
    </a>
  </div>
</body>
</html>
