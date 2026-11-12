<?php

define('SIKEMAS_APP', true);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? (user() ? 'dashboard' : 'login');

if ($page === 'logout') {
    $_SESSION = [];
    session_destroy();
    go('index.php?page=login');
}

if ($page === 'login') {
    if (user()) {
        go('index.php');
    }

    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $loginId = trim((string) ($_POST['username'] ?? $_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$loginId, $loginId]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => $account['id'],
                'nama'     => $account['nama'],
                'username' => $account['username'] ?? '',
                'email'    => $account['email'] ?? '',
                'role'     => $account['role'],
            ];
            go('index.php');
        }

        $err = 'Username atau password salah.';
    }

    render_view('login', ['err' => $err]);
    exit;
}

login_required();

if (in_array($page, ['categories', 'users'], true) && (user()['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses khusus admin.');
}

$modules = [
    'masuk' => [
        'table'       => 'surat_masuk',
        'party'       => 'asal_surat',
        'partyLabel'  => 'Asal Surat',
        'label'       => 'Surat Masuk',
        'folder'      => 'surat-masuk',
        'datecol'     => 'tanggal_diterima',
        'dateLabel'   => 'Tanggal Diterima',
        'fields'      => [
            'nomor_agenda'     => 'Nomor Agenda',
            'asal_surat'       => 'Asal Surat',
            'nomor_surat'      => 'Nomor Surat',
            'tanggal_surat'    => 'Tanggal Surat',
            'tanggal_diterima' => 'Tanggal Diterima',
            'jam_diterima'     => 'Jam Diterima',
            'perihal'          => 'Perihal',
        ],
    ],
    'keluar' => [
        'table'       => 'surat_keluar',
        'party'       => 'tujuan_surat',
        'partyLabel'  => 'Tujuan Surat',
        'label'       => 'Surat Keluar',
        'folder'      => 'surat-keluar',
        'datecol'     => 'tanggal_kirim',
        'dateLabel'   => 'Tanggal Kirim',
        'fields'      => [
            'nomor_agenda'  => 'Nomor Agenda',
            'tujuan_surat'  => 'Tujuan Surat',
            'nomor_surat'   => 'Nomor Surat',
            'tanggal_surat' => 'Tanggal Surat',
            'tanggal_kirim' => 'Tanggal Kirim',
            'perihal'       => 'Perihal',
        ],
    ],
];

$titles = [
    'dashboard'  => 'Dashboard',
    'masuk'      => 'Surat Masuk',
    'keluar'     => 'Surat Keluar',
    'categories' => 'Kategori Surat',
    'users'      => 'Manajemen Pengguna',
    'reports'    => 'Laporan & Rekapitulasi',
];

$title = $titles[$page] ?? 'Dashboard';
$contentView = 'dashboard';
$viewData = [];

if ($page === 'dashboard') {
    $monthStart = date('Y-m-01');

    $stats = [
        'masuk'        => (int) $pdo->query('SELECT COUNT(*) FROM surat_masuk')->fetchColumn(),
        'keluar'       => (int) $pdo->query('SELECT COUNT(*) FROM surat_keluar')->fetchColumn(),
        'kategori'     => (int) $pdo->query('SELECT COUNT(*) FROM kategoris')->fetchColumn(),
        'pengguna'     => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'masuk_bulan'  => 0,
        'keluar_bulan' => 0,
    ];

    $sMasukBulan = $pdo->prepare('SELECT COUNT(*) FROM surat_masuk WHERE tanggal_diterima >= ?');
    $sMasukBulan->execute([$monthStart]);
    $stats['masuk_bulan'] = (int) $sMasukBulan->fetchColumn();

    $sKeluarBulan = $pdo->prepare('SELECT COUNT(*) FROM surat_keluar WHERE tanggal_kirim >= ?');
    $sKeluarBulan->execute([$monthStart]);
    $stats['keluar_bulan'] = (int) $sKeluarBulan->fetchColumn();

    $recentSql = "
        SELECT 'Masuk' AS jenis, nomor_surat, asal_surat AS pihak, perihal, tanggal_diterima AS tgl, created_at
        FROM surat_masuk
        UNION ALL
        SELECT 'Keluar' AS jenis, nomor_surat, tujuan_surat AS pihak, perihal, tanggal_kirim AS tgl, created_at
        FROM surat_keluar
        ORDER BY tgl DESC, created_at DESC
        LIMIT 5
    ";
    $recentLetters = $pdo->query($recentSql)->fetchAll();

    $catStatsSql = '
        SELECT k.nama, k.warna,
               (SELECT COUNT(*) FROM surat_masuk sm WHERE sm.kategori_id = k.id) +
               (SELECT COUNT(*) FROM surat_keluar sk WHERE sk.kategori_id = k.id) AS total
        FROM kategoris k
        ORDER BY total DESC, k.nama ASC
    ';
    $categoryStats = $pdo->query($catStatsSql)->fetchAll();

    $contentView = 'dashboard';
    $viewData = [
        'stats'         => $stats,
        'recentLetters' => $recentLetters,
        'categoryStats' => $categoryStats,
    ];
} elseif (isset($modules[$page])) {
    $cfg = $modules[$page];
    $table = $cfg['table'];
    $party = $cfg['party'];
    $folder = $cfg['folder'];
    $datecol = $cfg['datecol'];
    $formError = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

        if (isset($_POST['delete_id'])) {
            admin_required();
            $deleteId = (int) $_POST['delete_id'];
            $oldStmt = $pdo->prepare("SELECT lampiran FROM $table WHERE id = ?");
            $oldStmt->execute([$deleteId]);
            $old = $oldStmt->fetch();

            $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$deleteId]);
            if ($old) {
                delete_file($old['lampiran'], $folder);
            }

            flash('success', 'Data surat dihapus.');
            go("index.php?page=$page");
        }

        try {
            $id = (int) ($_POST['id'] ?? 0);
            $upload = upload_file('lampiran', $folder);
            $catId = ($_POST['kategori_id'] ?? '') !== '' ? (int) $_POST['kategori_id'] : null;

            $fieldNames = array_keys($cfg['fields']);
            $values = [];
            foreach ($fieldNames as $fn) {
                $raw = trim((string) ($_POST[$fn] ?? ''));
                if ($fn === 'tanggal_surat' || $fn === 'jam_diterima') {
                    $values[] = $raw !== '' ? $raw : null;
                } else {
                    $values[] = $raw;
                }
            }
            $fieldNames[] = 'kategori_id';
            $values[] = $catId;
            $fieldNames[] = 'keterangan';
            $values[] = trim((string) ($_POST['keterangan'] ?? ''));

            if ($id > 0) {
                $oldStmt = $pdo->prepare("SELECT lampiran FROM $table WHERE id = ?");
                $oldStmt->execute([$id]);
                $old = $oldStmt->fetch();
                $file = $upload ?? ($old['lampiran'] ?? null);

                $sets = implode(', ', array_map(fn(string $col): string => "$col = ?", $fieldNames));
                $values[] = $file;
                $values[] = $id;
                $pdo->prepare("UPDATE $table SET $sets, lampiran = ? WHERE id = ?")->execute($values);

                if ($upload && $old) {
                    delete_file($old['lampiran'], $folder);
                }
            } else {
                $cols = implode(', ', $fieldNames) . ', lampiran, user_id';
                $placeholders = implode(', ', array_fill(0, count($values) + 2, '?'));
                $values[] = $upload;
                $values[] = user()['id'];
                $pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)")->execute($values);
            }

            flash('success', 'Data berhasil disimpan.');
            go("index.php?page=$page");
        } catch (PDOException $ex) {
            if ((string) $ex->getCode() === '23000') {
                $formError = 'Nomor surat sudah digunakan. Gunakan nomor surat yang berbeda.';
            } else {
                $formError = 'Terjadi kesalahan saat menyimpan data.';
            }
        } catch (Throwable $ex) {
            $formError = $ex->getMessage();
        }
    }

    $edit = null;
    if (isset($_GET['edit'])) {
        $editStmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $editStmt->execute([(int) $_GET['edit']]);
        $edit = $editStmt->fetch() ?: null;
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    $katFilter = trim((string) ($_GET['kat'] ?? ''));
    $perPage = 10;
    $currentPage = max(1, (int) ($_GET['p'] ?? 1));

    $whereParts = [];
    $params = [];

    if ($q !== '') {
        $like = "%$q%";
        $whereParts[] = "(t.nomor_surat LIKE ? OR t.perihal LIKE ? OR t.$party LIKE ? OR t.nomor_agenda LIKE ?)";
        array_push($params, $like, $like, $like, $like);
    }
    if ($katFilter !== '') {
        $whereParts[] = 't.kategori_id = ?';
        $params[] = (int) $katFilter;
    }

    $whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table t $whereSql");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    $listSql = "
        SELECT t.*, k.nama AS kategori, k.warna AS kategori_warna, u.nama AS petugas_nama
        FROM $table t
        LEFT JOIN kategoris k ON k.id = t.kategori_id
        LEFT JOIN users u ON u.id = t.user_id
        $whereSql
        ORDER BY t.id DESC
        LIMIT ? OFFSET ?
    ";
    $listStmt = $pdo->prepare($listSql);
    $idx = 1;
    foreach ($params as $val) {
        $listStmt->bindValue($idx++, $val);
    }
    $listStmt->bindValue($idx++, $perPage, PDO::PARAM_INT);
    $listStmt->bindValue($idx, $offset, PDO::PARAM_INT);
    $listStmt->execute();

    $rows = $listStmt->fetchAll();
    $cats = $pdo->query('SELECT * FROM kategoris ORDER BY nama')->fetchAll();

    $contentView = 'letters';
    $viewData = [
        'page'        => $page,
        'label'       => $cfg['label'],
        'party'       => $party,
        'partyLabel'  => $cfg['partyLabel'],
        'folder'      => $folder,
        'datecol'     => $datecol,
        'dateLabel'   => $cfg['dateLabel'],
        'fields'      => $cfg['fields'],
        'edit'        => $edit,
        'formError'   => $formError,
        'q'           => $q,
        'katFilter'   => $katFilter,
        'rows'        => $rows,
        'cats'        => $cats,
        'totalRows'   => $totalRows,
        'currentPage' => $currentPage,
        'totalPages'  => $totalPages,
    ];
} elseif ($page === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            $usedStmt = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM surat_masuk WHERE kategori_id = ?) + (SELECT COUNT(*) FROM surat_keluar WHERE kategori_id = ?)'
            );
            $usedStmt->execute([$delId, $delId]);

            if ((int) $usedStmt->fetchColumn() > 0) {
                flash('danger', 'Kategori masih digunakan oleh data surat.');
            } else {
                $pdo->prepare('DELETE FROM kategoris WHERE id = ?')->execute([$delId]);
                flash('success', 'Kategori dihapus.');
            }
            go('index.php?page=categories');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $kode = trim((string) ($_POST['kode'] ?? ''));
        $warna = safe_color($_POST['warna'] ?? '#2563eb');
        $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));

        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE kategoris SET nama = ?, kode = ?, warna = ?, deskripsi = ? WHERE id = ?')
                    ->execute([$nama, $kode, $warna, $deskripsi, $id]);
            } else {
                $pdo->prepare('INSERT INTO kategoris (nama, kode, warna, deskripsi) VALUES (?, ?, ?, ?)')
                    ->execute([$nama, $kode, $warna, $deskripsi]);
            }
            flash('success', 'Kategori berhasil disimpan.');
        } catch (PDOException $ex) {
            flash('danger', 'Gagal menyimpan kategori. Pastikan kode kategori belum digunakan.');
        }
        go('index.php?page=categories');
    }

    $editCategory = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare('SELECT * FROM kategoris WHERE id = ?');
        $stmt->execute([(int) $_GET['edit']]);
        $editCategory = $stmt->fetch() ?: null;
    }

    $categories = $pdo->query('
        SELECT k.*,
               (SELECT COUNT(*) FROM surat_masuk sm WHERE sm.kategori_id = k.id) +
               (SELECT COUNT(*) FROM surat_keluar sk WHERE sk.kategori_id = k.id) AS total_used
        FROM kategoris k
        ORDER BY k.nama ASC
    ')->fetchAll();

    $contentView = 'categories';
    $viewData = [
        'editCategory' => $editCategory,
        'categories'   => $categories,
    ];
} elseif ($page === 'users') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            if ($delId !== (int) user()['id']) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$delId]);
                flash('success', 'Pengguna dihapus.');
            }
            go('index.php?page=users');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $uname = trim((string) ($_POST['username'] ?? '')) ?: null;
        $email = trim((string) ($_POST['email'] ?? '')) ?: null;
        $role = ($_POST['role'] ?? 'petugas') === 'admin' ? 'admin' : 'petugas';
        $password = (string) ($_POST['password'] ?? '');

        try {
            if ($id > 0) {
                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET nama = ?, username = ?, email = ?, role = ?, password = ? WHERE id = ?')
                        ->execute([$nama, $uname, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $pdo->prepare('UPDATE users SET nama = ?, username = ?, email = ?, role = ? WHERE id = ?')
                        ->execute([$nama, $uname, $email, $role, $id]);
                }
            } else {
                $pdo->prepare('INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$nama, $uname, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            }
            flash('success', 'Data pengguna berhasil disimpan.');
        } catch (Throwable $ex) {
            flash('danger', 'Gagal menyimpan pengguna. Pastikan username atau email belum digunakan.');
        }
        go('index.php?page=users');
    }

    $editUser = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare('SELECT id, nama, username, email, role FROM users WHERE id = ?');
        $stmt->execute([(int) $_GET['edit']]);
        $editUser = $stmt->fetch() ?: null;
    }

    $users = $pdo->query('SELECT id, nama, username, email, role FROM users ORDER BY id DESC')->fetchAll();

    $contentView = 'users';
    $viewData = [
        'editUser' => $editUser,
        'users'    => $users,
    ];
} elseif ($page === 'reports') {
    $startDate = (string) ($_GET['awal'] ?? date('Y-m-01'));
    $endDate = (string) ($_GET['akhir'] ?? date('Y-m-d'));
    $jenisFilter = (string) ($_GET['jenis'] ?? '');
    $katFilter = trim((string) ($_GET['kat'] ?? ''));

    $queries = [];
    $params = [];

    if ($jenisFilter === '' || $jenisFilter === 'masuk') {
        $katCond = $katFilter !== '' ? ' AND sm.kategori_id = ?' : '';
        $queries[] = "
            SELECT 'Masuk' AS jenis, sm.nomor_agenda, sm.asal_surat AS pihak, sm.nomor_surat,
                   sm.tanggal_diterima AS tgl, sm.perihal, k.nama AS kategori
            FROM surat_masuk sm
            LEFT JOIN kategoris k ON k.id = sm.kategori_id
            WHERE sm.tanggal_diterima BETWEEN ? AND ? $katCond
        ";
        array_push($params, $startDate, $endDate);
        if ($katFilter !== '') {
            $params[] = (int) $katFilter;
        }
    }

    if ($jenisFilter === '' || $jenisFilter === 'keluar') {
        $katCond = $katFilter !== '' ? ' AND sk.kategori_id = ?' : '';
        $queries[] = "
            SELECT 'Keluar' AS jenis, sk.nomor_agenda, sk.tujuan_surat AS pihak, sk.nomor_surat,
                   sk.tanggal_kirim AS tgl, sk.perihal, k.nama AS kategori
            FROM surat_keluar sk
            LEFT JOIN kategoris k ON k.id = sk.kategori_id
            WHERE sk.tanggal_kirim BETWEEN ? AND ? $katCond
        ";
        array_push($params, $startDate, $endDate);
        if ($katFilter !== '') {
            $params[] = (int) $katFilter;
        }
    }

    $sql = implode(' UNION ALL ', $queries) . ' ORDER BY tgl DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reportRows = $stmt->fetchAll();

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rekap-surat-' . $startDate . '-sd-' . $endDate . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['No', 'Jenis', 'Nomor Agenda', 'Asal/Tujuan', 'Nomor Surat', 'Kategori', 'Tanggal', 'Perihal']);

        $safeCsv = static function (mixed $v): string {
            $str = (string) ($v ?? '');
            return preg_match('/^[=+\-@\t\r]/', $str) ? "'" . $str : $str;
        };

        foreach ($reportRows as $i => $row) {
            fputcsv($out, [
                $i + 1,
                $safeCsv($row['jenis']),
                $safeCsv($row['nomor_agenda']),
                $safeCsv($row['pihak']),
                $safeCsv($row['nomor_surat']),
                $safeCsv($row['kategori'] ?: '-'),
                $safeCsv($row['tgl']),
                $safeCsv($row['perihal']),
            ]);
        }
        fclose($out);
        exit;
    }

    $masukCount = count(array_filter($reportRows, fn(array $r): bool => $r['jenis'] === 'Masuk'));
    $keluarCount = count($reportRows) - $masukCount;

    $contentView = 'reports';
    $viewData = [
        'startDate'   => $startDate,
        'endDate'     => $endDate,
        'jenisFilter' => $jenisFilter,
        'katFilter'   => $katFilter,
        'cats'        => $pdo->query('SELECT * FROM kategoris ORDER BY nama')->fetchAll(),
        'reportRows'  => $reportRows,
        'summary'     => [
            'masuk'  => $masukCount,
            'keluar' => $keluarCount,
            'total'  => count($reportRows),
        ],
    ];
} else {
    $contentView = 'dashboard';
    flash('warning', 'Halaman yang diminta tidak ditemukan.');
    go('index.php?page=dashboard');
}

render_view('layout', [
    'page'        => $page,
    'title'       => $title,
    'contentView' => $contentView,
    'viewData'    => $viewData,
]);
