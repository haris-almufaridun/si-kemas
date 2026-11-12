<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$isEdit = !empty($editCategory);
?>
<div class="panel">
  <div class="panel-header">
    <h5 class="panel-title">
      <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-primary me-1"></i>
      <?= $isEdit ? 'Edit Kategori Surat' : 'Tambah Kategori Surat' ?>
    </h5>
    <?php if ($isEdit): ?>
      <a class="btn btn-sm btn-light" href="index.php?page=categories">Batal Edit</a>
    <?php endif; ?>
  </div>

  <form method="post" class="row g-3 align-items-end">
    <?= csrf() ?>
    <input type="hidden" name="id" value="<?= e($editCategory['id'] ?? '') ?>">

    <div class="col-md-3">
      <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
      <input
        class="form-control"
        name="nama"
        placeholder="Mis. Surat Keputusan"
        value="<?= e($editCategory['nama'] ?? '') ?>"
        required
      >
    </div>

    <div class="col-md-2">
      <label class="form-label">Kode <span class="text-danger">*</span></label>
      <input
        class="form-control"
        name="kode"
        placeholder="Mis. SKP"
        value="<?= e($editCategory['kode'] ?? '') ?>"
        required
      >
    </div>

    <div class="col-md-1">
      <label class="form-label">Warna</label>
      <input
        class="form-control form-control-color w-100"
        type="color"
        name="warna"
        value="<?= e(safe_color($editCategory['warna'] ?? '#2563eb')) ?>"
      >
    </div>

    <div class="col-md-4">
      <label class="form-label">Deskripsi</label>
      <input
        class="form-control"
        name="deskripsi"
        placeholder="Keterangan singkat kategori"
        value="<?= e($editCategory['deskripsi'] ?? '') ?>"
      >
    </div>

    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit">
        <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Perbarui' : 'Tambah' ?>
      </button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-header">
    <h5 class="panel-title">Daftar Kategori</h5>
    <span class="small text-secondary">Total: <strong><?= count($categories) ?></strong> kategori</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nama Kategori</th>
          <th>Kode</th>
          <th>Deskripsi</th>
          <th>Digunakan</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$categories): ?>
          <tr>
            <td colspan="5">
              <div class="empty-state">
                <i class="bi bi-tags"></i>
                Belum ada kategori surat.
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($categories as $r): ?>
            <?php $color = safe_color($r['warna']); ?>
            <tr>
              <td>
                <span class="badge-pill" style="background: <?= e($color) ?>1a; color: <?= e($color) ?>;">
                  <span class="cat-dot" style="background: <?= e($color) ?>"></span>
                  <?= e($r['nama']) ?>
                </span>
              </td>
              <td><code class="text-dark fw-semibold"><?= e($r['kode']) ?></code></td>
              <td><?= e($r['deskripsi'] ?: '-') ?></td>
              <td><?= number_format((int) $r['total_used']) ?> surat</td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-secondary" href="index.php?page=categories&edit=<?= e($r['id']) ?>">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <form method="post" class="d-inline" data-confirm="Hapus kategori ini?">
                  <?= csrf() ?>
                  <input type="hidden" name="delete_id" value="<?= e($r['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="bi bi-trash"></i> Hapus
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

