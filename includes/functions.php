<?php

if (!defined('SIKEMAS_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Escape HTML special characters safely for template output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a target URL and terminate script execution.
 */
function go(string $url): never
{
    header("Location: $url");
    exit;
}

/**
 * Return a hidden CSRF token input field for HTML forms.
 */
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf']) . '">';
}

/**
 * Verify CSRF token on POST requests.
 */
function check_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('Token formulir tidak valid. Muat ulang halaman.');
    }
}

/**
 * Store a flash alert message in the session.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [$type, $message];
}

/**
 * Render and clear the pending flash alert message if present.
 */
function notice(): void
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    [$type, $message] = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill',
    ];
    $icon = $icons[$type] ?? 'bi-info-circle-fill';

    echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">'
        . '<i class="bi ' . e($icon) . '"></i>'
        . '<div>' . e($message) . '</div>'
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>'
        . '</div>';
}

/**
 * Get the currently authenticated user array or null.
 */
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Require an authenticated session, redirecting to login otherwise.
 */
function login_required(): void
{
    if (!user()) {
        go('index.php?page=login');
    }
}

/**
 * Require administrator privileges for protected actions.
 */
function admin_required(): void
{
    login_required();
    if ((user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Akses khusus admin.');
    }
}

/**
 * Validate and persist an uploaded PDF/JPG/PNG attachment up to 5 MB.
 */
function upload_file(string $field, string $folder): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal.');
    }
    if ($_FILES[$field]['size'] > 5242880) {
        throw new RuntimeException('Maksimal ukuran file 5 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $allowedTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    if (!isset($allowedTypes[$mime])) {
        throw new RuntimeException('File harus PDF, JPG, JPEG, atau PNG.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $targetDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetDir . '/' . $filename)) {
        throw new RuntimeException('Gagal menyimpan lampiran.');
    }

    return $filename;
}

/**
 * Remove a stored attachment file if it exists.
 */
function delete_file(?string $name, string $folder): void
{
    if (!$name) {
        return;
    }
    $path = __DIR__ . '/../uploads/' . $folder . '/' . basename($name);
    if (is_file($path)) {
        unlink($path);
    }
}

/**
 * Validate a 6-digit hex color string, falling back to brand blue.
 */
function safe_color(?string $hex, string $fallback = '#2563eb'): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex) ? (string) $hex : $fallback;
}

/**
 * Build up to two uppercase initials from a display name.
 */
function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = array_map(fn(string $p): string => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: 'U';
}

/**
 * Format a YYYY-MM-DD date string into Indonesian readable format.
 */
function format_date_id(?string $dateStr): string
{
    if (!$dateStr) {
        return '-';
    }
    $ts = strtotime($dateStr);
    if ($ts === false) {
        return $dateStr;
    }
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    return date('d', $ts) . ' ' . ($months[(int) date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
}

/**
 * Build a cache-busted relative URL for local assets based on file modification time.
 */
function asset_url(string $relativePath): string
{
    $clean = ltrim($relativePath, '/');
    $fullPath = __DIR__ . '/../' . $clean;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : '1';
    return $clean . '?v=' . $version;
}

/**
 * Render a view template file with extracted variables.
 */
function render_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $view . '.php';
}
