<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$currentUser = user();
$mainMenu = [
    'dashboard'  => ['Dashboard', 'bi-grid-1x2'],
    'masuk'      => ['Surat Masuk', 'bi-inbox'],
    'keluar'     => ['Surat Keluar', 'bi-send'],
    'reports'    => ['Laporan & Rekapitulasi', 'bi-file-earmark-bar-graph'],
];
$adminMenu = [
    'categories' => ['Kategori Surat', 'bi-tags'],
    'users'      => ['Manajemen Pengguna', 'bi-people'],
];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#072266">
  <title><?= e($title) ?> - SI-KEMAS</title>
  <link rel="icon" type="image/png" href="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

  <div class="shell">
    <aside id="appSidebar" class="side">
      <a class="brand" href="index.php">
        <div class="brand-logo-wrap">
          <img src="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>" alt="Logo DPPKB OKU Timur">
        </div>
        <div class="brand-caption">
          <b>SI-KEMAS</b>
          <small>ARSIP SURAT</small>
        </div>
      </a>

      <div class="profile">
        <div class="avatar-circle"><?= e(user_initials($currentUser['nama'])) ?></div>
        <div class="overflow-hidden">
          <div class="fw-bold text-truncate"><?= e($currentUser['nama']) ?></div>
          <div class="small text-info"><?= e(ucfirst($currentUser['role'])) ?></div>
        </div>
      </div>

      <div class="navlabel">MENU UTAMA</div>
      <nav>
        <?php foreach ($mainMenu as $key => [$label, $icon]): ?>
          <a class="<?= $page === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>">
            <i class="bi <?= e($icon) ?>"></i>
            <span><?= e($label) ?></span>
          </a>
        <?php endforeach; ?>

        <?php if ($currentUser['role'] === 'admin'): ?>
          <div class="navlabel">PENGATURAN</div>
          <?php foreach ($adminMenu as $key => [$label, $icon]): ?>
            <a class="<?= $page === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>">
              <i class="bi <?= e($icon) ?>"></i>
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>

      <a class="logout" href="index.php?page=logout">
        <i class="bi bi-box-arrow-left"></i>
        <span>Keluar</span>
      </a>
    </aside>

    <main class="main">
      <header class="top">
        <div class="d-flex align-items-center gap-3">
          <button
            id="sidebarToggle"
            class="btn btn-outline-secondary btn-sm mobile-menu-btn"
            type="button"
            aria-label="Buka menu navigasi"
          >
            <i class="bi bi-list fs-5"></i>
          </button>
          <div>
            <div class="top-kicker">Sistem Informasi Arsip Surat</div>
            <h1><?= e($title) ?></h1>
          </div>
        </div>

        <div class="top-right">
          <span class="date-pill">
            <i class="bi bi-calendar3"></i>
            <?= e(format_date_id(date('Y-m-d'))) ?>
          </span>
          <a class="btn btn-sm btn-outline-primary quick-top-btn" href="index.php?page=masuk&new=1">
            <i class="bi bi-plus-lg"></i> Surat Masuk
          </a>
          <a class="btn btn-sm btn-primary quick-top-btn" href="index.php?page=keluar&new=1">
            <i class="bi bi-plus-lg"></i> Surat Keluar
          </a>
        </div>
      </header>

      <div class="content">
        <?php notice(); ?>
        <?php render_view($contentView, $viewData); ?>
      </div>
    </main>
  </div>

  <!-- Detail Surat Modal -->
  <div class="modal fade" id="letterDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom">
          <div>
            <h5 class="modal-title fw-bold" id="detailTitle">Detail Surat</h5>
            <div class="small text-secondary" id="detailKategori">-</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="small text-secondary">Nomor Agenda</div>
              <div class="fw-semibold" id="detailAgenda">-</div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary">Nomor Surat</div>
              <div class="fw-semibold" id="detailNomor">-</div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary" id="detailPartyLabel">Asal/Tujuan</div>
              <div class="fw-semibold" id="detailParty">-</div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary">Tanggal Surat</div>
              <div class="fw-semibold" id="detailTglSurat">-</div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary" id="detailTglProsesLabel">Tanggal</div>
              <div class="fw-semibold" id="detailTglProses">-</div>
            </div>
            <div class="col-md-6" id="detailJamRow">
              <div class="small text-secondary">Jam Diterima</div>
              <div class="fw-semibold" id="detailJam">-</div>
            </div>
            <div class="col-12">
              <div class="small text-secondary">Perihal</div>
              <div class="fw-semibold" id="detailPerihal">-</div>
            </div>
            <div class="col-12">
              <div class="small text-secondary">Keterangan</div>
              <div id="detailKeterangan">-</div>
            </div>
            <div class="col-12">
              <div class="small text-secondary">Diinput oleh</div>
              <div id="detailAuthor">-</div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top">
          <div id="detailFileWrap" class="me-auto">
            <a id="detailFileLink" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-paperclip"></i> Buka Lampiran
            </a>
          </div>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>

