document.addEventListener('DOMContentLoaded', () => {
  // Confirmation prompt for destructive form submissions or links.
  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });

  document.addEventListener('click', (event) => {
    const confirmLink = event.target.closest('a[data-confirm], button[data-confirm]');
    if (confirmLink && !window.confirm(confirmLink.dataset.confirm)) {
      event.preventDefault();
      return;
    }

    // Password visibility toggle buttons.
    const toggleBtn = event.target.closest('[data-toggle-password]');
    if (toggleBtn) {
      const targetId = toggleBtn.dataset.togglePassword;
      const input = document.getElementById(targetId);
      const icon = toggleBtn.querySelector('i');
      if (input) {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) {
          icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
      }
      return;
    }

    // Letter detail modal hydration.
    const detailBtn = event.target.closest('[data-detail-json]');
    if (detailBtn) {
      try {
        const detail = JSON.parse(detailBtn.dataset.detailJson || '{}');
        hydrateDetailModal(detail);
      } catch (_err) {
        // Ignore malformed detail JSON.
      }
    }
  });

  // Mobile sidebar drawer controls.
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const toggleSidebarBtn = document.getElementById('sidebarToggle');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
  }

  if (toggleSidebarBtn && sidebar && backdrop) {
    toggleSidebarBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      backdrop.classList.toggle('show');
    });
    backdrop.addEventListener('click', closeSidebar);
  }
});

/**
 * Populate the Bootstrap letter detail modal from a row payload.
 */
function hydrateDetailModal(detail) {
  const modalEl = document.getElementById('letterDetailModal');
  if (!modalEl) return;

  const setText = (id, value) => {
    const el = document.getElementById(id);
    if (el) el.textContent = value || '-';
  };

  setText('detailTitle', detail.judul || 'Detail Surat');
  setText('detailAgenda', detail.nomor_agenda);
  setText('detailNomor', detail.nomor_surat);
  setText('detailPartyLabel', detail.party_label || 'Asal/Tujuan');
  setText('detailParty', detail.party);
  setText('detailTglSurat', detail.tanggal_surat);
  setText('detailTglProsesLabel', detail.date_label || 'Tanggal');
  setText('detailTglProses', detail.tanggal_proses);
  setText('detailJam', detail.jam_diterima);
  setText('detailKategori', detail.kategori);
  setText('detailPerihal', detail.perihal);
  setText('detailKeterangan', detail.keterangan);
  setText('detailAuthor', detail.petugas);

  const jamRow = document.getElementById('detailJamRow');
  if (jamRow) {
    jamRow.style.display = detail.show_jam ? '' : 'none';
  }

  const fileWrap = document.getElementById('detailFileWrap');
  const fileLink = document.getElementById('detailFileLink');
  if (fileWrap && fileLink) {
    if (detail.lampiran_url) {
      fileWrap.style.display = '';
      fileLink.setAttribute('href', detail.lampiran_url);
    } else {
      fileWrap.style.display = 'none';
      fileLink.setAttribute('href', '#');
    }
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}
