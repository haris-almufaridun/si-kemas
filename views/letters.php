<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$isEdit = !empty($edit);
$showForm = isset($_GET['new']) || $isEdit || !empty($formError);
?>
<div class="actions-bar">
  <form class="filter-group" method="get">
    <input type="hidden" name="page" value="<?= e($page) ?>">
    <div class="input-group" style="max-width: 300px;">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input
        class="form-control"
        name="q"
        value="<?= e($q) ?>"
        placeholder="Cari nomor, pihak, perihal..."
      >
    </div>

    <select class="form-select" name="kat" style="max-width: 200px;">
      <option value="">Semua Kategori</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= e($c['id']) ?>" <?= (string) $katFilter === (string) $c['id'] ? 'selected' : '' ?>>
          <?= e($c['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-outline-primary" type="submit">
      <i class="bi bi-funnel"></i> Filter
    </button>

    <?php if ($q !== '' || $katFilter !== ''): ?>
      <a class="btn btn-light" href="index.php?page=<?= e($page) ?>">Reset</a>
    <?php endif; ?>
  </form>

  <a class="btn btn-primary" href="index.php?page=<?= e($page) ?>&new=1">
    <i class="bi bi-plus-lg"></i> Tambah <?= e($label) ?>
  </a>
</div>

<?php if ($showForm): ?>
  <div class="panel">
    <div class="panel-header">
      <h5 class="panel-title">
        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-primary me-1"></i>
        <?= $isEdit ? 'Edit' : 'Tambah' ?> <?= e($label) ?>
      </h5>
      <a class="btn btn-sm btn-light" href="index.php?page=<?= e($page) ?>">Tutup</a>
    </div>

    <?php if (!empty($formError)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><?= e($formError) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf() ?>
      <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

      <div class="row g-3">
        <?php foreach ($fields as $key => $fieldLabel): ?>
          <?php
          $type = str_contains($key, 'tanggal') ? 'date' : ($key === 'jam_diterima' ? 'time' : 'text');
          $val = $edit[$key] ?? ($_POST[$key] ?? ($type === 'date' ? date('Y-m-d') : ''));
          ?>
          <div class="col-md-6">
            <label class="form-label"><?= e($fieldLabel) ?> <span class="text-danger">*</span></label>
            <input
              class="form-control"
              type="<?= e($type) ?>"
              name="<?= e($key) ?>"
              value="<?= e($val) ?>"
              required
            >
          </div>
        <?php endforeach; ?>

        <div class="col-md-6">
          <label class="form-label">Kategori</label>
          <select class="form-select" name="kategori_id">
            <option value="">Pilih kategori</option>
            <?php $selectedCat = $edit['kategori_id'] ?? ($_POST['kategori_id'] ?? ''); ?>
            <?php foreach ($cats as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= (string) $selectedCat === (string) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Keterangan</label>
          <textarea
            class="form-control"
            name="keterangan"
            rows="2"
            placeholder="Catatan tambahan (opsional)"
          ><?= e($edit['keterangan'] ?? ($_POST['keterangan'] ?? '')) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label">Lampiran PDF/JPG/PNG (maks 5 MB)</label>
          <input class="form-control" type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
          <?php if (!empty($edit['lampiran'])): ?>
            <div class="form-text">
              Lampiran saat ini:
              <a target="_blank" rel="noopener" href="uploads/<?= e($folder) ?>/<?= e($edit['lampiran']) ?>">
                <?= e($edit['lampiran']) ?>
              </a>
              (kosongkan jika tidak ingin mengganti).
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-check-lg"></i> Simpan Data
        </button>
        <a class="btn btn-light" href="index.php?page=<?= e($page) ?>">Batal</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel-header">
    <h5 class="panel-title">Daftar <?= e($label) ?></h5>
    <span class="small text-secondary">Total: <strong><?= number_format($totalRows) ?></strong> data</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Agenda</th>
          <th><?= e($partyLabel) ?></th>
          <th>Nomor Surat</th>
          <th>Perihal</th>
          <th>Tanggal</th>
          <th>Kategori</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <i class="bi bi-inbox"></i>
                Belum ada data <?= e(strtolower($label)) ?> yang cocok.
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
            $catColor = safe_color($r['kategori_warna'] ?? null);
            $detailPayload = json_encode([
                'judul'          => $label,
                'nomor_agenda'   => $r['nomor_agenda'] ?? '',
                'nomor_surat'    => $r['nomor_surat'] ?? '',
                'party_label'    => $partyLabel,
                'party'          => $r[$party] ?? '',
                'tanggal_surat'  => format_date_id($r['tanggal_surat'] ?? null),
                'date_label'     => $dateLabel,
                'tanggal_proses' => format_date_id($r[$datecol] ?? null),
                'show_jam'       => $page === 'masuk',
                'jam_diterima'   => !empty($r['jam_diterima']) ? $r['jam_diterima'] : '-',
                'kategori'       => !empty($r['kategori']) ? $r['kategori'] : 'Tanpa kategori',
                'perihal'        => $r['perihal'] ?? '',
                'keterangan'     => !empty($r['keterangan']) ? $r['keterangan'] : '-',
                'petugas'        => !empty($r['petugas_nama']) ? $r['petugas_nama'] : '-',
                'lampiran_url'   => !empty($r['lampiran']) ? ('uploads/' . $folder . '/' . $r['lampiran']) : '',
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
            <tr>
              <td class="fw-semibold text-nowrap"><?= e($r['nomor_agenda']) ?></td>
              <td><?= e($r[$party]) ?></td>
              <td class="text-nowrap"><?= e($r['nomor_surat']) ?></td>
              <td><?= e($r['perihal']) ?></td>
              <td class="text-nowrap"><?= e(format_date_id($r[$datecol])) ?></td>
              <td>
                <?php if (!empty($r['kategori'])): ?>
                  <span class="badge-pill" style="background: <?= e($catColor) ?>1a; color: <?= e($catColor) ?>;">
                    <span class="cat-dot" style="background: <?= e($catColor) ?>"></span>
                    <?= e($r['kategori']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-secondary">-</span>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <button
                  class="btn btn-sm btn-outline-info"
                  type="button"
                  data-detail-json="<?= e($detailPayload) ?>"
                  title="Lihat Detail"
                >
                  <i class="bi bi-eye"></i> Detail
                </button>
                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=<?= e($page) ?>&edit=<?= e($r['id']) ?>">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <?php if (!empty($r['lampiran'])): ?>
                  <a
                    class="btn btn-sm btn-outline-primary"
                    target="_blank"
                    rel="noopener"
                    href="uploads/<?= e($folder) ?>/<?= e($r['lampiran']) ?>"
                  >
                    <i class="bi bi-paperclip"></i> File
                  </a>
                <?php endif; ?>
                <?php if (user()['role'] === 'admin'): ?>
                  <form method="post" class="d-inline" data-confirm="Hapus data surat ini?">
                    <?= csrf() ?>
                    <input type="hidden" name="delete_id" value="<?= e($r['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">
                      <i class="bi bi-trash"></i> Hapus
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="small text-secondary">
        Halaman <?= $currentPage ?> dari <?= $totalPages ?>
      </div>
      <ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php
          $qs = http_build_query(array_filter([
              'page' => $page,
              'q'    => $q,
              'kat'  => $katFilter,
              'p'    => $p,
          ], fn($v) => $v !== ''));
          ?>
          <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="index.php?<?= e($qs) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

