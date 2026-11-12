<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$cards = [
    [
        'label' => 'Total Surat Masuk',
        'value' => $stats['masuk'],
        'foot'  => 'Bulan ini: ' . number_format($stats['masuk_bulan']) . ' surat',
        'icon'  => 'bi-inbox-fill',
        'tone'  => 'icon-blue',
    ],
    [
        'label' => 'Total Surat Keluar',
        'value' => $stats['keluar'],
        'foot'  => 'Bulan ini: ' . number_format($stats['keluar_bulan']) . ' surat',
        'icon'  => 'bi-send-fill',
        'tone'  => 'icon-emerald',
    ],
    [
        'label' => 'Kategori Surat',
        'value' => $stats['kategori'],
        'foot'  => 'Klasifikasi arsip aktif',
        'icon'  => 'bi-tags-fill',
        'tone'  => 'icon-violet',
    ],
    [
        'label' => 'Pengguna Sistem',
        'value' => $stats['pengguna'],
        'foot'  => 'Admin & petugas terdaftar',
        'icon'  => 'bi-people-fill',
        'tone'  => 'icon-amber',
    ],
];
?>
<div class="row g-3 mb-4">
  <?php foreach ($cards as $c): ?>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="stat">
        <div class="stat-head">
          <div>
            <div class="stat-label"><?= e($c['label']) ?></div>
            <div class="num"><?= number_format((int) $c['value']) ?></div>
          </div>
          <div class="icon-box <?= e($c['tone']) ?>">
            <i class="bi <?= e($c['icon']) ?>"></i>
          </div>
        </div>
        <div class="stat-foot"><?= e($c['foot']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="panel h-100 mb-0">
      <div class="panel-header">
        <div>
          <h5 class="panel-title">Arsip Surat Terbaru</h5>
          <div class="small text-secondary">5 pencatatan surat masuk &amp; keluar terakhir</div>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="index.php?page=reports">
          <i class="bi bi-file-earmark-bar-graph"></i> Lihat Rekap
        </a>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Jenis</th>
              <th>Nomor Surat</th>
              <th>Asal / Tujuan</th>
              <th>Perihal</th>
              <th>Tanggal</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recentLetters): ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <i class="bi bi-archive"></i>
                    Belum ada surat tercatat. Mulai dengan mencatat surat masuk atau surat keluar.
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentLetters as $item): ?>
                <tr>
                  <td>
                    <span class="badge-pill <?= $item['jenis'] === 'Masuk' ? 'badge-masuk' : 'badge-keluar' ?>">
                      <i class="bi <?= $item['jenis'] === 'Masuk' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                      <?= e($item['jenis']) ?>
                    </span>
                  </td>
                  <td class="fw-semibold"><?= e($item['nomor_surat']) ?></td>
                  <td><?= e($item['pihak']) ?></td>
                  <td><?= e($item['perihal']) ?></td>
                  <td class="text-nowrap"><?= e(format_date_id($item['tgl'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header">
        <h5 class="panel-title">Akses Cepat</h5>
      </div>
      <div class="d-grid gap-2">
        <a class="btn btn-primary justify-content-start" href="index.php?page=masuk&new=1">
          <i class="bi bi-plus-circle"></i> Catat Surat Masuk Baru
        </a>
        <a class="btn btn-outline-primary justify-content-start" href="index.php?page=keluar&new=1">
          <i class="bi bi-send-plus"></i> Catat Surat Keluar Baru
        </a>
        <a class="btn btn-outline-secondary justify-content-start" href="index.php?page=reports">
          <i class="bi bi-printer"></i> Cetak Rekapitulasi Bulanan
        </a>
      </div>
    </div>

    <div class="panel mb-0">
      <div class="panel-header">
        <h5 class="panel-title">Distribusi Kategori</h5>
      </div>
      <?php if (!$categoryStats): ?>
        <div class="small text-secondary">Belum ada kategori tersedia.</div>
      <?php else: ?>
        <?php $maxCat = max(1, max(array_column($categoryStats, 'total'))); ?>
        <?php foreach ($categoryStats as $cat): ?>
          <?php
          $color = safe_color($cat['warna']);
          $pct = (int) round(($cat['total'] / $maxCat) * 100);
          ?>
          <div class="category-bar-item">
            <div class="d-flex justify-content-between align-items-center small">
              <span class="fw-semibold d-flex align-items-center gap-2">
                <span class="cat-dot" style="background: <?= e($color) ?>"></span>
                <?= e($cat['nama']) ?>
              </span>
              <span class="text-secondary"><?= number_format((int) $cat['total']) ?> surat</span>
            </div>
            <div class="progress-thin">
              <div style="width: <?= $pct ?>%; background: <?= e($color) ?>"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

