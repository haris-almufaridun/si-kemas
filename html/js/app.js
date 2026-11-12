(() => {
  'use strict';

  const STORAGE_KEY = 'sikemas_html_db_v1';
  const SESSION_KEY = 'sikemas_html_session_v1';
  const MAX_FILE_BYTES = 5 * 1024 * 1024;
  const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
  const PER_PAGE = 10;

  const MODULES = {
    masuk: {
      storeKey: 'suratMasuk',
      partyField: 'asal_surat',
      partyLabel: 'Asal Surat',
      label: 'Surat Masuk',
      dateField: 'tanggal_diterima',
      dateLabel: 'Tanggal Diterima',
      uniqueNomor: false,
      fields: [
        { key: 'nomor_agenda', label: 'Nomor Agenda', type: 'text' },
        { key: 'asal_surat', label: 'Asal Surat', type: 'text' },
        { key: 'nomor_surat', label: 'Nomor Surat', type: 'text' },
        { key: 'tanggal_surat', label: 'Tanggal Surat', type: 'date' },
        { key: 'tanggal_diterima', label: 'Tanggal Diterima', type: 'date' },
        { key: 'jam_diterima', label: 'Jam Diterima', type: 'time' },
        { key: 'perihal', label: 'Perihal', type: 'text' },
      ],
    },
    keluar: {
      storeKey: 'suratKeluar',
      partyField: 'tujuan_surat',
      partyLabel: 'Tujuan Surat',
      label: 'Surat Keluar',
      dateField: 'tanggal_kirim',
      dateLabel: 'Tanggal Kirim',
      uniqueNomor: true,
      fields: [
        { key: 'nomor_agenda', label: 'Nomor Agenda', type: 'text' },
        { key: 'tujuan_surat', label: 'Tujuan Surat', type: 'text' },
        { key: 'nomor_surat', label: 'Nomor Surat', type: 'text' },
        { key: 'tanggal_surat', label: 'Tanggal Surat', type: 'date' },
        { key: 'tanggal_kirim', label: 'Tanggal Kirim', type: 'date' },
        { key: 'perihal', label: 'Perihal', type: 'text' },
      ],
    },
  };

  const PAGE_TITLES = {
    dashboard: 'Dashboard',
    masuk: 'Surat Masuk',
    keluar: 'Surat Keluar',
    categories: 'Kategori Surat',
    users: 'Manajemen Pengguna',
    reports: 'Laporan & Rekapitulasi',
  };

  const MAIN_MENU = [
    ['dashboard', 'Dashboard', 'bi-grid-1x2'],
    ['masuk', 'Surat Masuk', 'bi-inbox'],
    ['keluar', 'Surat Keluar', 'bi-send'],
    ['reports', 'Laporan & Rekapitulasi', 'bi-file-earmark-bar-graph'],
  ];

  const ADMIN_MENU = [
    ['categories', 'Kategori Surat', 'bi-tags'],
    ['users', 'Manajemen Pengguna', 'bi-people'],
  ];

  const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

  const uiState = {
    page: 'dashboard',
    searchQuery: { masuk: '', keluar: '' },
    catFilter: { masuk: '', keluar: '' },
    listPage: { masuk: 1, keluar: 1 },
    showForm: { masuk: false, keluar: false },
    editId: { masuk: null, keluar: null },
    editCategoryId: null,
    editUserId: null,
    reportStart: '',
    reportEnd: '',
    reportJenis: '',
    reportKat: '',
    flash: null,
    loginError: '',
    formError: '',
    sidebarOpen: false,
    modalAttachment: null,
  };

  /** Escape HTML special characters safely. */
  function esc(val) {
    return String(val ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /** Return local YYYY-MM-DD date string without UTC shift. */
  function todayStr() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function monthStartStr() {
    return `${todayStr().slice(0, 8)}01`;
  }

  function formatDateId(dateStr) {
    if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr || '-';
    const [y, m, d] = dateStr.split('-');
    return `${d} ${MONTHS_ID[Number(m) - 1] || m} ${y}`;
  }

  function userInitials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'U';
  }

  function sanitizeColor(hex) {
    return /^#[0-9a-fA-F]{6}$/.test(hex || '') ? hex : '#2563eb';
  }

  /** Deterministic password hash compatible with both file:// and http:// origins. */
  function hashPassword(password) {
    const salted = `sikemas_v1::${password}`;
    let h1 = 0x811c9dc5;
    let h2 = 0x9e3779b9;
    for (let i = 0; i < salted.length; i += 1) {
      const ch = salted.charCodeAt(i);
      h1 ^= ch;
      h1 = Math.imul(h1, 0x01000193);
      h2 ^= ch + (i << 5);
      h2 = Math.imul(h2, 0x85ebca6b);
    }
    return `${(h1 >>> 0).toString(16).padStart(8, '0')}${(h2 >>> 0).toString(16).padStart(8, '0')}`;
  }

  function createSeedDb() {
    return {
      nextIds: { users: 2, kategoris: 5, suratMasuk: 1, suratKeluar: 1 },
      users: [
        {
          id: 1,
          nama: 'Administrator',
          username: 'admin',
          email: 'admin@sikemas.local',
          passwordHash: hashPassword('admin123'),
          role: 'admin',
          createdAt: new Date().toISOString(),
        },
      ],
      kategoris: [
        { id: 1, nama: 'Umum', kode: 'UMM', warna: '#2563eb', deskripsi: 'Surat umum' },
        { id: 2, nama: 'Kepegawaian', kode: 'PEG', warna: '#0f766e', deskripsi: 'Surat kepegawaian' },
        { id: 3, nama: 'Undangan', kode: 'UND', warna: '#7c3aed', deskripsi: 'Surat undangan' },
        { id: 4, nama: 'Pemberitahuan', kode: 'PEM', warna: '#d97706', deskripsi: 'Surat pemberitahuan' },
      ],
      suratMasuk: [],
      suratKeluar: [],
    };
  }

  function loadDb() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        const seed = createSeedDb();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(seed));
        return seed;
      }
      const parsed = JSON.parse(raw);
      if (!parsed || !Array.isArray(parsed.users) || !Array.isArray(parsed.kategoris)) {
        const seed = createSeedDb();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(seed));
        return seed;
      }
      return parsed;
    } catch (_err) {
      return createSeedDb();
    }
  }

  function saveDb(db) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(db));
    } catch (err) {
      if (err && err.name === 'QuotaExceededError') {
        throw new Error('Penyimpanan browser penuh. Hapus lampiran lama atau gunakan file berukuran lebih kecil.');
      }
      throw new Error('Gagal menyimpan data ke penyimpanan browser.');
    }
  }

  function nextId(db, key) {
    const id = db.nextIds[key] || 1;
    db.nextIds[key] = id + 1;
    return id;
  }

  function getSessionUser(db) {
    try {
      const raw = localStorage.getItem(SESSION_KEY);
      if (!raw) return null;
      const sess = JSON.parse(raw);
      return db.users.find((u) => u.id === sess.id) || null;
    } catch (_err) {
      return null;
    }
  }

  function setSessionUser(user) {
    if (!user) {
      localStorage.removeItem(SESSION_KEY);
      return;
    }
    localStorage.setItem(SESSION_KEY, JSON.stringify({ id: user.id }));
  }

  function setFlash(type, message) {
    uiState.flash = { type, message };
  }

  function consumeFlashHtml() {
    if (!uiState.flash) return '';
    const { type, message } = uiState.flash;
    uiState.flash = null;
    const iconMap = {
      success: 'bi-check-circle-fill',
      danger: 'bi-exclamation-triangle-fill',
      warning: 'bi-exclamation-circle-fill',
      info: 'bi-info-circle-fill',
    };
    const icon = iconMap[type] || 'bi-info-circle-fill';
    return `
      <div class="alert alert-${esc(type)} alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi ${esc(icon)}"></i>
        <div>${esc(message)}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    `;
  }

  function readAttachment(file) {
    return new Promise((resolve, reject) => {
      if (!file || !file.name) {
        resolve(null);
        return;
      }
      if (file.size > MAX_FILE_BYTES) {
        reject(new Error('Maksimal ukuran file 5 MB.'));
        return;
      }
      const extOk = /\.(pdf|jpe?g|png)$/i.test(file.name);
      if (!ALLOWED_TYPES.includes(file.type) && !extOk) {
        reject(new Error('File harus PDF, JPG, JPEG, atau PNG.'));
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        resolve({
          name: file.name,
          type: file.type || 'application/octet-stream',
          dataUrl: String(reader.result || ''),
        });
      };
      reader.onerror = () => reject(new Error('Gagal membaca lampiran.'));
      reader.readAsDataURL(file);
    });
  }

  function openAttachment(lampiran) {
    if (!lampiran || !lampiran.dataUrl) return;
    const parts = lampiran.dataUrl.split(',');
    if (parts.length !== 2) return;
    const mimeMatch = parts[0].match(/:(.*?);/);
    const mime = mimeMatch ? mimeMatch[1] : (lampiran.type || 'application/octet-stream');
    const binary = atob(parts[1]);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
      bytes[i] = binary.charCodeAt(i);
    }
    const blob = new Blob([bytes], { type: mime });
    const url = URL.createObjectURL(blob);
    window.open(url, '_blank', 'noopener');
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  }

  function openLetterDetail(pageKey, id) {
    const cfg = MODULES[pageKey];
    if (!cfg || typeof bootstrap === 'undefined') return;

    const db = loadDb();
    const row = db[cfg.storeKey].find((r) => r.id === id);
    if (!row) return;

    const cat = db.kategoris.find((c) => c.id === row.kategori_id);
    const author = db.users.find((u) => u.id === row.user_id);

    const setText = (elId, value) => {
      const el = document.getElementById(elId);
      if (el) el.textContent = value || '-';
    };

    setText('detailTitle', cfg.label);
    setText('detailKategori', cat ? cat.nama : 'Tanpa kategori');
    setText('detailAgenda', row.nomor_agenda);
    setText('detailNomor', row.nomor_surat);
    setText('detailPartyLabel', cfg.partyLabel);
    setText('detailParty', row[cfg.partyField]);
    setText('detailTglSurat', formatDateId(row.tanggal_surat));
    setText('detailTglProsesLabel', cfg.dateLabel);
    setText('detailTglProses', formatDateId(row[cfg.dateField]));
    setText('detailJam', row.jam_diterima);
    setText('detailPerihal', row.perihal);
    setText('detailKeterangan', row.keterangan);
    setText('detailAuthor', author ? author.nama : '-');

    const jamRow = document.getElementById('detailJamRow');
    if (jamRow) jamRow.style.display = pageKey === 'masuk' ? '' : 'none';

    const fileWrap = document.getElementById('detailFileWrap');
    uiState.modalAttachment = row.lampiran && row.lampiran.dataUrl ? row.lampiran : null;
    if (fileWrap) {
      fileWrap.style.display = uiState.modalAttachment ? '' : 'none';
    }

    const modalEl = document.getElementById('letterDetailModal');
    if (modalEl) {
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
  }

  function getPageFromHash() {
    const hash = window.location.hash.replace(/^#/, '').trim();
    return hash || 'dashboard';
  }

  function navigate(page) {
    uiState.formError = '';
    uiState.sidebarOpen = false;
    if (window.location.hash !== `#${page}`) {
      window.location.hash = `#${page}`;
    } else {
      uiState.page = page;
      render();
    }
  }

  function renderLogin() {
    document.title = 'Login - SI-KEMAS';
    document.body.className = 'login';
    const errHtml = uiState.loginError
      ? `
        <div class="alert alert-danger d-flex align-items-center gap-2 text-start py-2" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>${esc(uiState.loginError)}</div>
        </div>`
      : '';

    return `
      <div class="loginbox text-center">
        <img class="login-logo" src="images/logo-dppkb.png" alt="Logo DPPKB OKU Timur">
        <h2 class="fw-bold mb-1">SI-KEMAS</h2>
        <p class="login-subtitle">
          Sistem Informasi Keluar Masuk Surat<br>
          <strong>DPPKB Kabupaten OKU Timur</strong>
        </p>
        ${errHtml}
        <form id="loginForm" class="text-start">
          <div class="mb-3">
            <label class="form-label" for="loginUsername">Username atau Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input id="loginUsername" class="form-control" type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label" for="loginPassword">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input id="loginPassword" class="form-control" type="password" name="password" placeholder="Masukkan password" required>
              <button class="btn btn-outline-secondary" type="button" data-toggle-password="loginPassword" aria-label="Tampilkan atau sembunyikan password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Aplikasi
          </button>
        </form>
        <div class="mt-4 pt-3 border-top small text-secondary">
          &copy; ${new Date().getFullYear()} SI-KEMAS &middot; DPPKB Kab. OKU Timur
        </div>
      </div>
    `;
  }

  function renderDashboard(db) {
    const mStart = monthStartStr();
    const masukMonth = db.suratMasuk.filter((r) => r.tanggal_diterima >= mStart).length;
    const keluarMonth = db.suratKeluar.filter((r) => r.tanggal_kirim >= mStart).length;

    const cards = [
      ['Total Surat Masuk', db.suratMasuk.length, `Bulan ini: ${masukMonth.toLocaleString('id-ID')} surat`, 'bi-inbox-fill', 'icon-blue'],
      ['Total Surat Keluar', db.suratKeluar.length, `Bulan ini: ${keluarMonth.toLocaleString('id-ID')} surat`, 'bi-send-fill', 'icon-emerald'],
      ['Kategori Surat', db.kategoris.length, 'Klasifikasi arsip aktif', 'bi-tags-fill', 'icon-violet'],
      ['Pengguna Sistem', db.users.length, 'Admin & petugas terdaftar', 'bi-people-fill', 'icon-amber'],
    ];

    const cardsHtml = cards
      .map(
        ([label, total, foot, icon, tone]) => `
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="stat">
            <div class="stat-head">
              <div>
                <div class="stat-label">${esc(label)}</div>
                <div class="num">${total.toLocaleString('id-ID')}</div>
              </div>
              <div class="icon-box ${esc(tone)}"><i class="bi ${esc(icon)}"></i></div>
            </div>
            <div class="stat-foot">${esc(foot)}</div>
          </div>
        </div>`
      )
      .join('');

    const recent = [
      ...db.suratMasuk.map((r) => ({
        jenis: 'Masuk',
        nomor_surat: r.nomor_surat,
        pihak: r.asal_surat,
        perihal: r.perihal,
        tgl: r.tanggal_diterima,
      })),
      ...db.suratKeluar.map((r) => ({
        jenis: 'Keluar',
        nomor_surat: r.nomor_surat,
        pihak: r.tujuan_surat,
        perihal: r.perihal,
        tgl: r.tanggal_kirim,
      })),
    ]
      .sort((a, b) => b.tgl.localeCompare(a.tgl))
      .slice(0, 5);

    const recentRows = recent.length
      ? recent
          .map(
            (item) => `
            <tr>
              <td>
                <span class="badge-pill ${item.jenis === 'Masuk' ? 'badge-masuk' : 'badge-keluar'}">
                  <i class="bi ${item.jenis === 'Masuk' ? 'bi-arrow-down-left' : 'bi-arrow-up-right'}"></i>
                  ${esc(item.jenis)}
                </span>
              </td>
              <td class="fw-semibold">${esc(item.nomor_surat)}</td>
              <td>${esc(item.pihak)}</td>
              <td>${esc(item.perihal)}</td>
              <td class="text-nowrap">${esc(formatDateId(item.tgl))}</td>
            </tr>`
          )
          .join('')
      : `<tr><td colspan="5"><div class="empty-state"><i class="bi bi-archive"></i>Belum ada surat tercatat. Mulai dengan mencatat surat masuk atau surat keluar.</div></td></tr>`;

    const catCounts = db.kategoris
      .map((c) => ({
        nama: c.nama,
        warna: sanitizeColor(c.warna),
        total:
          db.suratMasuk.filter((r) => r.kategori_id === c.id).length +
          db.suratKeluar.filter((r) => r.kategori_id === c.id).length,
      }))
      .sort((a, b) => b.total - a.total || a.nama.localeCompare(b.nama));

    const maxCat = Math.max(1, ...catCounts.map((c) => c.total));
    const catBarsHtml = catCounts.length
      ? catCounts
          .map((c) => {
            const pct = Math.round((c.total / maxCat) * 100);
            return `
              <div class="category-bar-item">
                <div class="d-flex justify-content-between align-items-center small">
                  <span class="fw-semibold d-flex align-items-center gap-2">
                    <span class="cat-dot" style="background: ${esc(c.warna)}"></span>
                    ${esc(c.nama)}
                  </span>
                  <span class="text-secondary">${c.total.toLocaleString('id-ID')} surat</span>
                </div>
                <div class="progress-thin"><div style="width: ${pct}%; background: ${esc(c.warna)}"></div></div>
              </div>`;
          })
          .join('')
      : '<div class="small text-secondary">Belum ada kategori tersedia.</div>';

    return `
      <div class="row g-3 mb-4">${cardsHtml}</div>
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="panel h-100 mb-0">
            <div class="panel-header">
              <div>
                <h5 class="panel-title">Arsip Surat Terbaru</h5>
                <div class="small text-secondary">5 pencatatan surat masuk &amp; keluar terakhir</div>
              </div>
              <a class="btn btn-sm btn-outline-primary" href="#reports">
                <i class="bi bi-file-earmark-bar-graph"></i> Lihat Rekap
              </a>
            </div>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr><th>Jenis</th><th>Nomor Surat</th><th>Asal / Tujuan</th><th>Perihal</th><th>Tanggal</th></tr>
                </thead>
                <tbody>${recentRows}</tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="panel">
            <div class="panel-header"><h5 class="panel-title">Akses Cepat</h5></div>
            <div class="d-grid gap-2">
              <button class="btn btn-primary justify-content-start" type="button" data-action="quick-new" data-module="masuk">
                <i class="bi bi-plus-circle"></i> Catat Surat Masuk Baru
              </button>
              <button class="btn btn-outline-primary justify-content-start" type="button" data-action="quick-new" data-module="keluar">
                <i class="bi bi-send-plus"></i> Catat Surat Keluar Baru
              </button>
              <a class="btn btn-outline-secondary justify-content-start" href="#reports">
                <i class="bi bi-printer"></i> Cetak Rekapitulasi Bulanan
              </a>
            </div>
          </div>
          <div class="panel mb-0">
            <div class="panel-header"><h5 class="panel-title">Distribusi Kategori</h5></div>
            ${catBarsHtml}
          </div>
        </div>
      </div>
    `;
  }

  function renderLetterForm(pageKey, db) {
    const cfg = MODULES[pageKey];
    const editId = uiState.editId[pageKey];
    const isEdit = editId !== null;
    if (!uiState.showForm[pageKey] && !isEdit) return '';

    const item = isEdit ? db[cfg.storeKey].find((r) => r.id === editId) || {} : {};
    const errHtml = uiState.formError
      ? `<div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i><div>${esc(uiState.formError)}</div></div>`
      : '';

    const fieldInputs = cfg.fields
      .map((f) => {
        const val = item[f.key] ?? (f.type === 'date' ? todayStr() : '');
        return `
          <div class="col-md-6">
            <label class="form-label">${esc(f.label)} <span class="text-danger">*</span></label>
            <input class="form-control" type="${esc(f.type)}" name="${esc(f.key)}" value="${esc(val)}" required>
          </div>`;
      })
      .join('');

    const catOptions = [...db.kategoris]
      .sort((a, b) => a.nama.localeCompare(b.nama))
      .map((c) => {
        const selected = String(item.kategori_id ?? '') === String(c.id) ? 'selected' : '';
        return `<option value="${esc(c.id)}" ${selected}>${esc(c.nama)}</option>`;
      })
      .join('');

    const currentFileNote = item.lampiran && item.lampiran.name
      ? `<div class="form-text">Lampiran saat ini: ${esc(item.lampiran.name)} (kosongkan jika tidak ingin mengganti).</div>`
      : '';

    return `
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">
            <i class="bi ${isEdit ? 'bi-pencil-square' : 'bi-plus-circle'} text-primary me-1"></i>
            ${isEdit ? 'Edit' : 'Tambah'} ${esc(cfg.label)}
          </h5>
          <button class="btn btn-sm btn-light" type="button" data-action="cancel-letter" data-module="${esc(pageKey)}">Tutup</button>
        </div>
        ${errHtml}
        <form id="letterForm" data-module="${esc(pageKey)}">
          <input type="hidden" name="id" value="${esc(item.id ?? '')}">
          <div class="row g-3">
            ${fieldInputs}
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <select class="form-select" name="kategori_id">
                <option value="">Pilih kategori</option>
                ${catOptions}
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Keterangan</label>
              <textarea class="form-control" name="keterangan" rows="2" placeholder="Catatan tambahan (opsional)">${esc(item.keterangan ?? '')}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Lampiran PDF/JPG/PNG (maks 5 MB)</label>
              <input class="form-control" type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
              ${currentFileNote}
            </div>
          </div>
          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Simpan Data</button>
            <button class="btn btn-light" type="button" data-action="cancel-letter" data-module="${esc(pageKey)}">Batal</button>
          </div>
        </form>
      </div>
    `;
  }

  function renderLetterModule(pageKey, db, currentUser) {
    const cfg = MODULES[pageKey];
    const query = (uiState.searchQuery[pageKey] || '').trim();
    const catFilter = uiState.catFilter[pageKey] || '';
    const qLower = query.toLowerCase();
    const catMap = new Map(db.kategoris.map((c) => [c.id, c]));

    const filtered = [...db[cfg.storeKey]]
      .filter((r) => {
        if (catFilter && String(r.kategori_id ?? '') !== catFilter) return false;
        if (!qLower) return true;
        return [r.nomor_surat, r.perihal, r[cfg.partyField], r.nomor_agenda]
          .some((val) => String(val ?? '').toLowerCase().includes(qLower));
      })
      .sort((a, b) => b.id - a.id);

    const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    const currentPage = Math.min(Math.max(1, uiState.listPage[pageKey] || 1), totalPages);
    const startIdx = (currentPage - 1) * PER_PAGE;
    const rows = filtered.slice(startIdx, startIdx + PER_PAGE);

    const catFilterOpts = [...db.kategoris]
      .sort((a, b) => a.nama.localeCompare(b.nama))
      .map((c) => `<option value="${esc(c.id)}" ${catFilter === String(c.id) ? 'selected' : ''}>${esc(c.nama)}</option>`)
      .join('');

    const rowsHtml = rows.length
      ? rows
          .map((r) => {
            const cat = catMap.get(r.kategori_id);
            const catHtml = cat
              ? `<span class="badge-pill" style="background:${esc(sanitizeColor(cat.warna))}1a;color:${esc(sanitizeColor(cat.warna))};"><span class="cat-dot" style="background:${esc(sanitizeColor(cat.warna))}"></span>${esc(cat.nama)}</span>`
              : '<span class="text-secondary">-</span>';

            const fileBtn = r.lampiran && r.lampiran.dataUrl
              ? ` <button class="btn btn-sm btn-outline-primary" type="button" data-action="view-file" data-module="${esc(pageKey)}" data-id="${esc(r.id)}"><i class="bi bi-paperclip"></i> File</button>`
              : '';
            const deleteBtn = currentUser.role === 'admin'
              ? ` <button class="btn btn-sm btn-outline-danger" type="button" data-action="delete-letter" data-module="${esc(pageKey)}" data-id="${esc(r.id)}"><i class="bi bi-trash"></i> Hapus</button>`
              : '';

            return `
              <tr>
                <td class="fw-semibold text-nowrap">${esc(r.nomor_agenda)}</td>
                <td>${esc(r[cfg.partyField])}</td>
                <td class="text-nowrap">${esc(r.nomor_surat)}</td>
                <td>${esc(r.perihal)}</td>
                <td class="text-nowrap">${esc(formatDateId(r[cfg.dateField]))}</td>
                <td>${catHtml}</td>
                <td class="text-end text-nowrap">
                  <button class="btn btn-sm btn-outline-info" type="button" data-action="detail-letter" data-module="${esc(pageKey)}" data-id="${esc(r.id)}"><i class="bi bi-eye"></i> Detail</button>
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-action="edit-letter" data-module="${esc(pageKey)}" data-id="${esc(r.id)}"><i class="bi bi-pencil"></i> Edit</button>${fileBtn}${deleteBtn}
                </td>
              </tr>`;
          })
          .join('')
      : `<tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>Belum ada data ${esc(cfg.label.toLowerCase())} yang cocok.</div></td></tr>`;

    let pagerHtml = '';
    if (totalPages > 1) {
      const items = Array.from({ length: totalPages }, (_, i) => i + 1)
        .map(
          (p) => `<li class="page-item ${p === currentPage ? 'active' : ''}"><button class="page-link" type="button" data-action="goto-page" data-module="${esc(pageKey)}" data-page="${p}">${p}</button></li>`
        )
        .join('');
      pagerHtml = `
        <nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="small text-secondary">Halaman ${currentPage} dari ${totalPages}</div>
          <ul class="pagination pagination-sm mb-0">${items}</ul>
        </nav>
      `;
    }

    const resetBtn = query || catFilter
      ? `<button class="btn btn-light" type="button" data-action="reset-filter" data-module="${esc(pageKey)}">Reset</button>`
      : '';

    return `
      <div class="actions-bar">
        <form id="searchForm" class="filter-group" data-module="${esc(pageKey)}">
          <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="q" value="${esc(query)}" placeholder="Cari nomor, pihak, perihal...">
          </div>
          <select class="form-select" name="kat" style="max-width: 200px;">
            <option value="">Semua Kategori</option>
            ${catFilterOpts}
          </select>
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
          ${resetBtn}
        </form>
        <button class="btn btn-primary" type="button" data-action="new-letter" data-module="${esc(pageKey)}">
          <i class="bi bi-plus-lg"></i> Tambah ${esc(cfg.label)}
        </button>
      </div>
      ${renderLetterForm(pageKey, db)}
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">Daftar ${esc(cfg.label)}</h5>
          <span class="small text-secondary">Total: <strong>${filtered.length.toLocaleString('id-ID')}</strong> data</span>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Agenda</th>
                <th>${esc(cfg.partyLabel)}</th>
                <th>Nomor Surat</th>
                <th>Perihal</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
        ${pagerHtml}
      </div>
    `;
  }

  function renderCategories(db) {
    const editCat = uiState.editCategoryId
      ? db.kategoris.find((c) => c.id === uiState.editCategoryId) || null
      : null;

    const rows = [...db.kategoris]
      .sort((a, b) => a.nama.localeCompare(b.nama))
      .map((c) => {
        const color = sanitizeColor(c.warna);
        const usedCount =
          db.suratMasuk.filter((r) => r.kategori_id === c.id).length +
          db.suratKeluar.filter((r) => r.kategori_id === c.id).length;
        return `
          <tr>
            <td>
              <span class="badge-pill" style="background:${esc(color)}1a;color:${esc(color)};">
                <span class="cat-dot" style="background:${esc(color)}"></span>
                ${esc(c.nama)}
              </span>
            </td>
            <td><code class="text-dark fw-semibold">${esc(c.kode)}</code></td>
            <td>${esc(c.deskripsi || '-')}</td>
            <td>${usedCount.toLocaleString('id-ID')} surat</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-action="edit-category" data-id="${esc(c.id)}"><i class="bi bi-pencil"></i> Edit</button>
              <button class="btn btn-sm btn-outline-danger" type="button" data-action="delete-category" data-id="${esc(c.id)}"><i class="bi bi-trash"></i> Hapus</button>
            </td>
          </tr>`;
      })
      .join('');

    return `
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">
            <i class="bi ${editCat ? 'bi-pencil-square' : 'bi-plus-circle'} text-primary me-1"></i>
            ${editCat ? 'Edit Kategori Surat' : 'Tambah Kategori Surat'}
          </h5>
          ${editCat ? '<button class="btn btn-sm btn-light" type="button" data-action="cancel-category">Batal Edit</button>' : ''}
        </div>
        <form id="categoryForm" class="row g-3 align-items-end">
          <input type="hidden" name="id" value="${esc(editCat ? editCat.id : '')}">
          <div class="col-md-3">
            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input class="form-control" name="nama" placeholder="Mis. Surat Keputusan" value="${esc(editCat ? editCat.nama : '')}" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Kode <span class="text-danger">*</span></label>
            <input class="form-control" name="kode" placeholder="Mis. SKP" value="${esc(editCat ? editCat.kode : '')}" required>
          </div>
          <div class="col-md-1">
            <label class="form-label">Warna</label>
            <input class="form-control form-control-color w-100" type="color" name="warna" value="${esc(sanitizeColor(editCat ? editCat.warna : '#2563eb'))}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Deskripsi</label>
            <input class="form-control" name="deskripsi" placeholder="Keterangan singkat kategori" value="${esc(editCat ? editCat.deskripsi : '')}">
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg"></i> ${editCat ? 'Perbarui' : 'Tambah'}</button>
          </div>
        </form>
      </div>
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">Daftar Kategori</h5>
          <span class="small text-secondary">Total: <strong>${db.kategoris.length}</strong> kategori</span>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Nama Kategori</th><th>Kode</th><th>Deskripsi</th><th>Digunakan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>
    `;
  }

  function renderUsers(db, currentUser) {
    const editUser = uiState.editUserId
      ? db.users.find((u) => u.id === uiState.editUserId) || null
      : null;

    const rows = [...db.users]
      .sort((a, b) => b.id - a.id)
      .map((u) => {
        const delBtn = u.id !== currentUser.id
          ? ` <button class="btn btn-sm btn-outline-danger" type="button" data-action="delete-user" data-id="${esc(u.id)}"><i class="bi bi-trash"></i> Hapus</button>`
          : '';
        return `
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-sm">${esc(userInitials(u.nama))}</span>
                <span class="fw-semibold">${esc(u.nama)}</span>
              </div>
            </td>
            <td><code>${esc(u.username || '-')}</code></td>
            <td>${esc(u.email || '-')}</td>
            <td><span class="badge-pill ${u.role === 'admin' ? 'badge-admin' : 'badge-petugas'}">${esc(u.role.charAt(0).toUpperCase() + u.role.slice(1))}</span></td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-action="edit-user" data-id="${esc(u.id)}"><i class="bi bi-pencil"></i> Edit</button>${delBtn}
            </td>
          </tr>`;
      })
      .join('');

    const roleVal = editUser ? editUser.role : 'petugas';

    return `
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">
            <i class="bi ${editUser ? 'bi-pencil-square' : 'bi-person-plus'} text-primary me-1"></i>
            ${editUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru'}
          </h5>
          ${editUser ? '<button class="btn btn-sm btn-light" type="button" data-action="cancel-user">Batal Edit</button>' : ''}
        </div>
        <form id="userForm" class="row g-3 align-items-end">
          <input type="hidden" name="id" value="${esc(editUser ? editUser.id : '')}">
          <div class="col-md-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input class="form-control" name="nama" placeholder="Nama lengkap" value="${esc(editUser ? editUser.nama : '')}" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input class="form-control" name="username" placeholder="Username" value="${esc(editUser ? editUser.username : '')}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Email (opsional)</label>
            <input class="form-control" type="email" name="email" placeholder="nama@instansi.go.id" value="${esc(editUser ? (editUser.email || '') : '')}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Password ${editUser ? '<span class="text-secondary fw-normal">(opsional)</span>' : '<span class="text-danger">*</span>'}</label>
            <input class="form-control" type="password" name="password" placeholder="${editUser ? 'Kosongkan jika tetap' : 'Min. 6 karakter'}" ${editUser ? '' : 'required'}>
          </div>
          <div class="col-md-1">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <option value="petugas" ${roleVal === 'petugas' ? 'selected' : ''}>Petugas</option>
              <option value="admin" ${roleVal === 'admin' ? 'selected' : ''}>Admin</option>
            </select>
          </div>
          <div class="col-md-1">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg"></i> ${editUser ? 'Simpan' : 'Tambah'}</button>
          </div>
        </form>
      </div>
      <div class="panel">
        <div class="panel-header">
          <h5 class="panel-title">Daftar Pengguna</h5>
          <span class="small text-secondary">Total: <strong>${db.users.length}</strong> akun</span>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Pengguna</th><th>Username</th><th>Email</th><th>Role</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>
    `;
  }

  function queryReportRows(db) {
    const start = uiState.reportStart || monthStartStr();
    const end = uiState.reportEnd || todayStr();
    const jenis = uiState.reportJenis || '';
    const kat = uiState.reportKat || '';
    const catMap = new Map(db.kategoris.map((c) => [c.id, c.nama]));

    const rows = [];
    if (!jenis || jenis === 'masuk') {
      db.suratMasuk.forEach((r) => {
        if (r.tanggal_diterima < start || r.tanggal_diterima > end) return;
        if (kat && String(r.kategori_id ?? '') !== kat) return;
        rows.push({
          jenis: 'Masuk',
          nomor_agenda: r.nomor_agenda,
          pihak: r.asal_surat,
          nomor_surat: r.nomor_surat,
          kategori: catMap.get(r.kategori_id) || '-',
          tgl: r.tanggal_diterima,
          perihal: r.perihal,
        });
      });
    }
    if (!jenis || jenis === 'keluar') {
      db.suratKeluar.forEach((r) => {
        if (r.tanggal_kirim < start || r.tanggal_kirim > end) return;
        if (kat && String(r.kategori_id ?? '') !== kat) return;
        rows.push({
          jenis: 'Keluar',
          nomor_agenda: r.nomor_agenda,
          pihak: r.tujuan_surat,
          nomor_surat: r.nomor_surat,
          kategori: catMap.get(r.kategori_id) || '-',
          tgl: r.tanggal_kirim,
          perihal: r.perihal,
        });
      });
    }

    rows.sort((a, b) => b.tgl.localeCompare(a.tgl));
    return { start, end, jenis, kat, rows };
  }

  function exportReportCsv(db) {
    const { start, end, rows } = queryReportRows(db);
    const safeCell = (v) => {
      const s = String(v ?? '');
      const neutralized = /^[=+\-@\t\r]/.test(s) ? `'${s}` : s;
      return `"${neutralized.replace(/"/g, '""')}"`;
    };
    const header = ['No', 'Jenis', 'Nomor Agenda', 'Asal/Tujuan', 'Nomor Surat', 'Kategori', 'Tanggal', 'Perihal'];
    const lines = [
      header.map(safeCell).join(','),
      ...rows.map((r, i) => [i + 1, r.jenis, r.nomor_agenda, r.pihak, r.nomor_surat, r.kategori, r.tgl, r.perihal].map(safeCell).join(',')),
    ];
    const blob = new Blob([`\uFEFF${lines.join('\r\n')}`], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `rekap-surat-${start}-sd-${end}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  }

  function renderReports(db) {
    const { start, end, jenis, kat, rows } = queryReportRows(db);
    const masukTotal = rows.filter((r) => r.jenis === 'Masuk').length;
    const keluarTotal = rows.length - masukTotal;

    const catOptions = [...db.kategoris]
      .sort((a, b) => a.nama.localeCompare(b.nama))
      .map((c) => `<option value="${esc(c.id)}" ${kat === String(c.id) ? 'selected' : ''}>${esc(c.nama)}</option>`)
      .join('');

    const rowsHtml = rows.length
      ? rows
          .map(
            (r, idx) => `
            <tr>
              <td>${idx + 1}</td>
              <td><span class="badge-pill ${r.jenis === 'Masuk' ? 'badge-masuk' : 'badge-keluar'}">${esc(r.jenis)}</span></td>
              <td>${esc(r.nomor_agenda)}</td>
              <td>${esc(r.pihak)}</td>
              <td>${esc(r.nomor_surat)}</td>
              <td>${esc(r.kategori)}</td>
              <td class="text-nowrap">${esc(formatDateId(r.tgl))}</td>
              <td>${esc(r.perihal)}</td>
            </tr>`
          )
          .join('')
      : '<tr><td colspan="8"><div class="empty-state"><i class="bi bi-file-earmark-x"></i>Tidak ada data surat pada filter periode ini.</div></td></tr>';

    return `
      <div class="panel no-print">
        <form id="reportForm" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label">Tanggal Mulai</label>
            <input class="form-control" type="date" name="awal" value="${esc(start)}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Tanggal Akhir</label>
            <input class="form-control" type="date" name="akhir" value="${esc(end)}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Jenis Surat</label>
            <select class="form-select" name="jenis">
              <option value="">Semua Jenis</option>
              <option value="masuk" ${jenis === 'masuk' ? 'selected' : ''}>Surat Masuk</option>
              <option value="keluar" ${jenis === 'keluar' ? 'selected' : ''}>Surat Keluar</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Kategori</label>
            <select class="form-select" name="kat">
              <option value="">Semua Kategori</option>
              ${catOptions}
            </select>
          </div>
          <div class="col-md-4 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button>
            <button class="btn btn-outline-success" type="button" data-action="export-csv"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
            <button class="btn btn-outline-secondary" type="button" data-action="print-report"><i class="bi bi-printer"></i> Cetak / PDF</button>
          </div>
        </form>
      </div>
      <div class="panel">
        <div class="letterhead text-center">
          <img class="letterhead-logo" src="images/logo-dppkb.png" alt="Logo DPPKB OKU Timur">
          <div class="fw-bold fs-6">PEMERINTAH KABUPATEN OGAN KOMERING ULU TIMUR</div>
          <div class="fw-bold fs-5">DINAS PENGENDALIAN PENDUDUK DAN KELUARGA BERENCANA</div>
          <div class="letterhead-divider"></div>
          <h5 class="fw-bold mt-3 mb-1">LAPORAN REKAPITULASI SURAT</h5>
          <div class="small text-secondary">Periode ${esc(formatDateId(start))} s.d. ${esc(formatDateId(end))}</div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-4">
            <div class="border rounded-3 p-3 bg-light">
              <div class="small text-secondary">Surat Masuk</div>
              <div class="fs-5 fw-bold text-primary">${masukTotal.toLocaleString('id-ID')}</div>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded-3 p-3 bg-light">
              <div class="small text-secondary">Surat Keluar</div>
              <div class="fs-5 fw-bold text-success">${keluarTotal.toLocaleString('id-ID')}</div>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded-3 p-3 bg-light">
              <div class="small text-secondary">Total Rekap</div>
              <div class="fs-5 fw-bold">${rows.length.toLocaleString('id-ID')}</div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr><th>No</th><th>Jenis</th><th>Nomor Agenda</th><th>Asal / Tujuan</th><th>Nomor Surat</th><th>Kategori</th><th>Tanggal</th><th>Perihal</th></tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
      </div>
    `;
  }

  function renderMainShell(db, currentUser) {
    const page = uiState.page;
    const isAdminOnly = page === 'categories' || page === 'users';
    const title = PAGE_TITLES[page] || 'Dashboard';

    document.title = `${title} - SI-KEMAS`;
    document.body.className = '';

    const mainLinks = MAIN_MENU
      .map(([k, label, icon]) => `<a class="${page === k ? 'active' : ''}" href="#${esc(k)}"><i class="bi ${esc(icon)}"></i><span>${esc(label)}</span></a>`)
      .join('');

    const adminLinks = currentUser.role === 'admin'
      ? `<div class="navlabel">PENGATURAN</div>${ADMIN_MENU
          .map(([k, label, icon]) => `<a class="${page === k ? 'active' : ''}" href="#${esc(k)}"><i class="bi ${esc(icon)}"></i><span>${esc(label)}</span></a>`)
          .join('')}`
      : '';

    let bodyHtml = '';
    if (isAdminOnly && currentUser.role !== 'admin') {
      bodyHtml = '<div class="alert alert-danger">Akses khusus admin.</div>';
    } else if (page === 'dashboard') {
      bodyHtml = renderDashboard(db);
    } else if (MODULES[page]) {
      bodyHtml = renderLetterModule(page, db, currentUser);
    } else if (page === 'categories') {
      bodyHtml = renderCategories(db);
    } else if (page === 'users') {
      bodyHtml = renderUsers(db, currentUser);
    } else if (page === 'reports') {
      bodyHtml = renderReports(db);
    } else {
      bodyHtml = '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
    }

    const roleLabel = currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1);
    const drawerClass = uiState.sidebarOpen ? 'open' : '';
    const backdropClass = uiState.sidebarOpen ? 'show' : '';

    return `
      <div class="sidebar-backdrop ${backdropClass}" data-action="close-sidebar"></div>
      <div class="shell">
        <aside class="side ${drawerClass}">
          <a class="brand" href="#dashboard">
            <div class="brand-logo-wrap">
              <img src="images/logo-dppkb.png" alt="Logo DPPKB OKU Timur">
            </div>
            <div class="brand-caption">
              <b>SI-KEMAS</b>
              <small>ARSIP SURAT</small>
            </div>
          </a>
          <div class="profile">
            <div class="avatar-circle">${esc(userInitials(currentUser.nama))}</div>
            <div class="overflow-hidden">
              <div class="fw-bold text-truncate">${esc(currentUser.nama)}</div>
              <div class="small text-info">${esc(roleLabel)}</div>
            </div>
          </div>
          <div class="navlabel">MENU UTAMA</div>
          <nav>${mainLinks}${adminLinks}</nav>
          <a class="logout" href="#logout" data-action="logout"><i class="bi bi-box-arrow-left"></i><span>Keluar</span></a>
        </aside>
        <main class="main">
          <header class="top">
            <div class="d-flex align-items-center gap-3">
              <button class="btn btn-outline-secondary btn-sm mobile-menu-btn" type="button" data-action="toggle-sidebar" aria-label="Buka menu navigasi">
                <i class="bi bi-list fs-5"></i>
              </button>
              <div>
                <div class="top-kicker">Sistem Informasi Arsip Surat</div>
                <h1>${esc(title)}</h1>
              </div>
            </div>
            <div class="top-right">
              <span class="date-pill"><i class="bi bi-calendar3"></i> ${esc(formatDateId(todayStr()))}</span>
              <button class="btn btn-sm btn-outline-primary quick-top-btn" type="button" data-action="quick-new" data-module="masuk">
                <i class="bi bi-plus-lg"></i> Surat Masuk
              </button>
              <button class="btn btn-sm btn-primary quick-top-btn" type="button" data-action="quick-new" data-module="keluar">
                <i class="bi bi-plus-lg"></i> Surat Keluar
              </button>
            </div>
          </header>
          <div class="content">
            ${consumeFlashHtml()}
            ${bodyHtml}
          </div>
        </main>
      </div>
    `;
  }

  function render() {
    const db = loadDb();
    const currentUser = getSessionUser(db);
    const app = document.getElementById('app');
    if (!app) return;

    if (!currentUser) {
      app.innerHTML = renderLogin();
      return;
    }

    app.innerHTML = renderMainShell(db, currentUser);
  }

  async function handleLetterSubmit(form) {
    const pageKey = form.dataset.module;
    const cfg = MODULES[pageKey];
    if (!cfg) return;

    const db = loadDb();
    const currentUser = getSessionUser(db);
    if (!currentUser) return;

    const fd = new FormData(form);
    const idRaw = String(fd.get('id') || '').trim();
    const id = idRaw ? Number(idRaw) : 0;
    const list = db[cfg.storeKey];

    const record = {};
    for (const f of cfg.fields) {
      record[f.key] = String(fd.get(f.key) || '').trim();
    }

    const katVal = String(fd.get('kategori_id') || '').trim();
    record.kategori_id = katVal ? Number(katVal) : null;
    record.keterangan = String(fd.get('keterangan') || '').trim();

    if (cfg.uniqueNomor) {
      const duplicate = list.some(
        (r) => r.id !== id && r.nomor_surat.toLowerCase() === record.nomor_surat.toLowerCase()
      );
      if (duplicate) {
        uiState.formError = 'Nomor surat sudah terdaftar pada Surat Keluar.';
        render();
        return;
      }
    }

    const fileInput = form.querySelector('input[name="lampiran"]');
    const file = fileInput && fileInput.files ? fileInput.files[0] : null;
    const newAttachment = await readAttachment(file);

    if (id) {
      const idx = list.findIndex((r) => r.id === id);
      if (idx === -1) return;
      const prev = list[idx];
      list[idx] = { ...prev, ...record, lampiran: newAttachment || prev.lampiran || null };
    } else {
      list.push({
        id: nextId(db, cfg.storeKey),
        ...record,
        lampiran: newAttachment,
        user_id: currentUser.id,
        created_at: new Date().toISOString(),
      });
    }

    saveDb(db);
    uiState.formError = '';
    uiState.showForm[pageKey] = false;
    uiState.editId[pageKey] = null;
    setFlash('success', 'Data berhasil disimpan.');
    render();
  }

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    event.preventDefault();

    const db = loadDb();

    if (form.id === 'loginForm') {
      const fd = new FormData(form);
      const loginId = String(fd.get('username') || '').trim();
      const hashed = hashPassword(String(fd.get('password') || ''));
      const user = db.users.find(
        (u) => (u.username === loginId || (u.email && u.email === loginId)) && u.passwordHash === hashed
      );
      if (user) {
        uiState.loginError = '';
        setSessionUser(user);
        uiState.page = getPageFromHash();
        if (uiState.page === 'logout') uiState.page = 'dashboard';
        render();
      } else {
        uiState.loginError = 'Username atau password salah.';
        render();
      }
      return;
    }

    const currentUser = getSessionUser(db);
    if (!currentUser) {
      render();
      return;
    }

    if (form.id === 'searchForm') {
      const pageKey = form.dataset.module;
      const fd = new FormData(form);
      uiState.searchQuery[pageKey] = String(fd.get('q') || '').trim();
      uiState.catFilter[pageKey] = String(fd.get('kat') || '').trim();
      uiState.listPage[pageKey] = 1;
      render();
      return;
    }

    if (form.id === 'letterForm') {
      try {
        await handleLetterSubmit(form);
      } catch (err) {
        uiState.formError = err instanceof Error ? err.message : 'Gagal menyimpan data.';
        render();
      }
      return;
    }

    if (form.id === 'categoryForm' && currentUser.role === 'admin') {
      const fd = new FormData(form);
      const id = Number(fd.get('id') || 0);
      const nama = String(fd.get('nama') || '').trim();
      const kode = String(fd.get('kode') || '').trim();
      const warna = sanitizeColor(String(fd.get('warna') || '#2563eb'));
      const deskripsi = String(fd.get('deskripsi') || '').trim();

      const dup = db.kategoris.some((c) => c.id !== id && c.kode.toLowerCase() === kode.toLowerCase());
      if (dup) {
        setFlash('danger', 'Gagal menyimpan kategori. Pastikan kode kategori belum digunakan.');
        render();
        return;
      }

      if (id > 0) {
        const idx = db.kategoris.findIndex((c) => c.id === id);
        if (idx !== -1) db.kategoris[idx] = { ...db.kategoris[idx], nama, kode, warna, deskripsi };
      } else {
        db.kategoris.push({ id: nextId(db, 'kategoris'), nama, kode, warna, deskripsi });
      }
      saveDb(db);
      uiState.editCategoryId = null;
      setFlash('success', 'Kategori berhasil disimpan.');
      render();
      return;
    }

    if (form.id === 'userForm' && currentUser.role === 'admin') {
      const fd = new FormData(form);
      const id = Number(fd.get('id') || 0);
      const nama = String(fd.get('nama') || '').trim();
      const username = String(fd.get('username') || '').trim();
      const email = String(fd.get('email') || '').trim() || null;
      const password = String(fd.get('password') || '');
      const role = String(fd.get('role') || 'petugas') === 'admin' ? 'admin' : 'petugas';

      const dup = db.users.some(
        (u) => u.id !== id && (u.username === username || (email && u.email === email))
      );
      if (dup) {
        setFlash('danger', 'Gagal menyimpan pengguna. Pastikan username atau email belum digunakan.');
        render();
        return;
      }

      if (id > 0) {
        const idx = db.users.findIndex((u) => u.id === id);
        if (idx !== -1) {
          db.users[idx] = {
            ...db.users[idx],
            nama,
            username,
            email,
            role,
            passwordHash: password !== '' ? hashPassword(password) : db.users[idx].passwordHash,
          };
        }
      } else {
        db.users.push({
          id: nextId(db, 'users'),
          nama,
          username,
          email,
          passwordHash: hashPassword(password),
          role,
          createdAt: new Date().toISOString(),
        });
      }
      saveDb(db);
      uiState.editUserId = null;
      setFlash('success', 'Data pengguna berhasil disimpan.');
      render();
      return;
    }

    if (form.id === 'reportForm') {
      const fd = new FormData(form);
      uiState.reportStart = String(fd.get('awal') || monthStartStr());
      uiState.reportEnd = String(fd.get('akhir') || todayStr());
      uiState.reportJenis = String(fd.get('jenis') || '');
      uiState.reportKat = String(fd.get('kat') || '');
      render();
    }
  });

  document.addEventListener('click', (event) => {
    const toggleBtn = event.target.closest('[data-toggle-password]');
    if (toggleBtn) {
      const input = document.getElementById(toggleBtn.dataset.togglePassword);
      const icon = toggleBtn.querySelector('i');
      if (input) {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
      }
      return;
    }

    if (event.target.closest('#detailFileBtn')) {
      if (uiState.modalAttachment) openAttachment(uiState.modalAttachment);
      return;
    }

    const target = event.target.closest('[data-action]');
    if (!target) return;

    const action = target.dataset.action;

    if (action === 'logout') {
      event.preventDefault();
      setSessionUser(null);
      uiState.loginError = '';
      uiState.flash = null;
      uiState.sidebarOpen = false;
      render();
      return;
    }

    if (action === 'toggle-sidebar') {
      uiState.sidebarOpen = !uiState.sidebarOpen;
      render();
      return;
    }

    if (action === 'close-sidebar') {
      uiState.sidebarOpen = false;
      render();
      return;
    }

    if (action === 'print-report') {
      window.print();
      return;
    }

    const db = loadDb();
    const currentUser = getSessionUser(db);
    if (!currentUser) return;

    if (action === 'export-csv') {
      exportReportCsv(db);
      return;
    }

    const pageKey = target.dataset.module;
    const id = Number(target.dataset.id || 0);

    if (action === 'quick-new' && MODULES[pageKey]) {
      uiState.formError = '';
      uiState.editId[pageKey] = null;
      uiState.showForm[pageKey] = true;
      navigate(pageKey);
      return;
    }

    if (action === 'new-letter' && MODULES[pageKey]) {
      uiState.formError = '';
      uiState.editId[pageKey] = null;
      uiState.showForm[pageKey] = true;
      render();
      return;
    }

    if (action === 'cancel-letter' && MODULES[pageKey]) {
      uiState.formError = '';
      uiState.editId[pageKey] = null;
      uiState.showForm[pageKey] = false;
      render();
      return;
    }

    if (action === 'edit-letter' && MODULES[pageKey]) {
      uiState.formError = '';
      uiState.editId[pageKey] = id;
      uiState.showForm[pageKey] = true;
      render();
      return;
    }

    if (action === 'detail-letter' && MODULES[pageKey]) {
      openLetterDetail(pageKey, id);
      return;
    }

    if (action === 'reset-filter' && MODULES[pageKey]) {
      uiState.searchQuery[pageKey] = '';
      uiState.catFilter[pageKey] = '';
      uiState.listPage[pageKey] = 1;
      render();
      return;
    }

    if (action === 'goto-page' && MODULES[pageKey]) {
      uiState.listPage[pageKey] = Math.max(1, Number(target.dataset.page || 1));
      render();
      return;
    }

    if (action === 'view-file' && MODULES[pageKey]) {
      const item = db[MODULES[pageKey].storeKey].find((r) => r.id === id);
      if (item && item.lampiran) openAttachment(item.lampiran);
      return;
    }

    if (action === 'delete-letter' && MODULES[pageKey]) {
      if (currentUser.role !== 'admin') return;
      if (!window.confirm('Hapus data surat ini?')) return;
      const storeKey = MODULES[pageKey].storeKey;
      db[storeKey] = db[storeKey].filter((r) => r.id !== id);
      saveDb(db);
      setFlash('success', 'Data surat dihapus.');
      render();
      return;
    }

    if (action === 'edit-category' && currentUser.role === 'admin') {
      uiState.editCategoryId = id;
      render();
      return;
    }

    if (action === 'cancel-category' && currentUser.role === 'admin') {
      uiState.editCategoryId = null;
      render();
      return;
    }

    if (action === 'delete-category' && currentUser.role === 'admin') {
      if (!window.confirm('Hapus kategori ini?')) return;
      const used = db.suratMasuk.some((r) => r.kategori_id === id)
        || db.suratKeluar.some((r) => r.kategori_id === id);
      if (used) {
        setFlash('danger', 'Kategori masih digunakan oleh data surat.');
      } else {
        db.kategoris = db.kategoris.filter((c) => c.id !== id);
        saveDb(db);
        setFlash('success', 'Kategori dihapus.');
      }
      render();
      return;
    }

    if (action === 'edit-user' && currentUser.role === 'admin') {
      uiState.editUserId = id;
      render();
      return;
    }

    if (action === 'cancel-user' && currentUser.role === 'admin') {
      uiState.editUserId = null;
      render();
      return;
    }

    if (action === 'delete-user' && currentUser.role === 'admin') {
      if (!window.confirm('Hapus pengguna ini?')) return;
      if (id !== currentUser.id) {
        db.users = db.users.filter((u) => u.id !== id);
        saveDb(db);
      }
      setFlash('success', 'Pengguna dihapus.');
      render();
    }
  });

  window.addEventListener('hashchange', () => {
    const next = getPageFromHash();
    if (next === 'logout') {
      setSessionUser(null);
      navigate('dashboard');
      return;
    }
    uiState.formError = '';
    uiState.sidebarOpen = false;
    uiState.page = next;
    render();
  });

  uiState.page = getPageFromHash();
  if (uiState.page === 'logout') uiState.page = 'dashboard';
  render();
})();
