<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$exportQs = http_build_query([
    'page'   => 'reports',
    'export' => 'csv',
    'awal'   => $startDate,
    'akhir'  => $endDate,
    'jenis'  => $jenisFilter,
    'kat'    => $katFilter,
]);
?>
<div class="panel no-print">
  <form class="row g-3 align-items-end" method="get">
    <input type="hidden" name="page" value="reports">

    <div class="col-md-2">
      <label class="form-label">Tanggal Mulai</label>
      <input class="form-control" type="date" name="awal" value="<?= e($startDate) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Tanggal Akhir</label>
      <input class="form-control" type="date" name="akhir" value="<?= e($endDate) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Jenis Surat</label>
      <select class="form-select" name="jenis">
        <option value="">Semua Jenis</option>
        <option value="masuk" <?= $jenisFilter === 'masuk' ? 'selected' : '' ?>>Surat Masuk</option>
        <option value="keluar" <?= $jenisFilter === 'keluar' ? 'selected' : '' ?>>Surat Keluar</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Kategori</label>
      <select class="form-select" name="kat">
        <option value="">Semua Kategori</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= e($c['id']) ?>" <?= (string) $katFilter === (string) $c['id'] ? 'selected' : '' ?>>
            <?= e($c['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4 d-flex flex-wrap gap-2">
      <button class="btn btn-primary" type="submit">
        <i class="bi bi-funnel"></i> Tampilkan
      </button>
      <a class="btn btn-outline-success" href="index.php?<?= e($exportQs) ?>">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
      </a>
      <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
        <i class="bi bi-printer"></i> Cetak / PDF
      </button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="letterhead text-center">
    <img class="letterhead-logo" src="<?= e(asset_url('assets/images/logo-dppkb.png')) ?>" alt="Logo DPPKB OKU Timur">
    <div class="fw-bold fs-6">PEMERINTAH KABUPATEN OGAN KOMERING ULU TIMUR</div>
    <div class="fw-bold fs-5">DINAS PENGENDALIAN PENDUDUK DAN KELUARGA BERENCANA</div>
    <div class="letterhead-divider"></div>
    <h5 class="fw-bold mt-3 mb-1">LAPORAN REKAPITULASI SURAT</h5>
    <div class="small text-secondary">
      Periode <?= e(format_date_id($startDate)) ?> s.d. <?= e(format_date_id($endDate)) ?>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-4">
      <div class="border rounded-3 p-3 bg-light">
        <div class="small text-secondary">Surat Masuk</div>
        <div class="fs-5 fw-bold text-primary"><?= number_format($summary['masuk']) ?></div>
      </div>
    </div>
    <div class="col-4">
      <div class="border rounded-3 p-3 bg-light">
        <div class="small text-secondary">Surat Keluar</div>
        <div class="fs-5 fw-bold text-success"><?= number_format($summary['keluar']) ?></div>
      </div>
    </div>
    <div class="col-4">
      <div class="border rounded-3 p-3 bg-light">
        <div class="small text-secondary">Total Rekap</div>
        <div class="fs-5 fw-bold"><?= number_format($summary['total']) ?></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Jenis</th>
          <th>Nomor Agenda</th>
          <th>Asal / Tujuan</th>
          <th>Nomor Surat</th>
          <th>Kategori</th>
          <th>Tanggal</th>
          <th>Perihal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reportRows): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <i class="bi bi-file-earmark-x"></i>
                Tidak ada data surat pada filter periode ini.
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($reportRows as $idx => $r): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <span class="badge-pill <?= $r['jenis'] === 'Masuk' ? 'badge-masuk' : 'badge-keluar' ?>">
                  <?= e($r['jenis']) ?>
                </span>
              </td>
              <td><?= e($r['nomor_agenda']) ?></td>
              <td><?= e($r['pihak']) ?></td>
              <td><?= e($r['nomor_surat']) ?></td>
              <td><?= e($r['kategori'] ?: '-') ?></td>
              <td class="text-nowrap"><?= e(format_date_id($r['tgl'])) ?></td>
              <td><?= e($r['perihal']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

