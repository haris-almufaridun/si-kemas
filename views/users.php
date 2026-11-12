<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

$isEdit = !empty($editUser);
?>
<div class="panel">
  <div class="panel-header">
    <h5 class="panel-title">
      <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?> text-primary me-1"></i>
      <?= $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?>
    </h5>
    <?php if ($isEdit): ?>
      <a class="btn btn-sm btn-light" href="index.php?page=users">Batal Edit</a>
    <?php endif; ?>
  </div>

  <form method="post" class="row g-3 align-items-end">
    <?= csrf() ?>
    <input type="hidden" name="id" value="<?= e($editUser['id'] ?? '') ?>">

    <div class="col-md-3">
      <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
      <input
        class="form-control"
        name="nama"
        placeholder="Nama lengkap"
        value="<?= e($editUser['nama'] ?? '') ?>"
        required
      >
    </div>

    <div class="col-md-2">
      <label class="form-label">Username <span class="text-danger">*</span></label>
      <input
        class="form-control"
        name="username"
        placeholder="Username"
        value="<?= e($editUser['username'] ?? '') ?>"
        required
      >
    </div>

    <div class="col-md-3">
      <label class="form-label">Email (opsional)</label>
      <input
        class="form-control"
        type="email"
        name="email"
        placeholder="nama@instansi.go.id"
        value="<?= e($editUser['email'] ?? '') ?>"
      >
    </div>

    <div class="col-md-2">
      <label class="form-label">
        Password <?= $isEdit ? '<span class="text-secondary fw-normal">(opsional)</span>' : '<span class="text-danger">*</span>' ?>
      </label>
      <input
        class="form-control"
        type="password"
        name="password"
        placeholder="<?= $isEdit ? 'Kosongkan jika tetap' : 'Min. 6 karakter' ?>"
        <?= $isEdit ? '' : 'required' ?>
      >
    </div>

    <div class="col-md-1">
      <label class="form-label">Role</label>
      <?php $selectedRole = $editUser['role'] ?? 'petugas'; ?>
      <select class="form-select" name="role">
        <option value="petugas" <?= $selectedRole === 'petugas' ? 'selected' : '' ?>>Petugas</option>
        <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </div>

    <div class="col-md-1">
      <button class="btn btn-primary w-100" type="submit">
        <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Simpan' : 'Tambah' ?>
      </button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-header">
    <h5 class="panel-title">Daftar Pengguna</h5>
    <span class="small text-secondary">Total: <strong><?= count($users) ?></strong> akun</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Pengguna</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $r): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-sm"><?= e(user_initials($r['nama'])) ?></span>
                <span class="fw-semibold"><?= e($r['nama']) ?></span>
              </div>
            </td>
            <td><code><?= e($r['username'] ?: '-') ?></code></td>
            <td><?= e($r['email'] ?: '-') ?></td>
            <td>
              <span class="badge-pill <?= $r['role'] === 'admin' ? 'badge-admin' : 'badge-petugas' ?>">
                <?= e(ucfirst($r['role'])) ?>
              </span>
            </td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users&edit=<?= e($r['id']) ?>">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <?php if ((int) $r['id'] !== (int) user()['id']): ?>
                <form method="post" class="d-inline" data-confirm="Hapus pengguna ini?">
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
      </tbody>
    </table>
  </div>
</div>

