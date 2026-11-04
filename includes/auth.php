<?php
/**
 * Authentication Guard & Serverless Session Synchronizer
 * CDSS-DBD - Session & Access Control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Secret key for signing authentication cookie tokens (vital for stateless serverless on Vercel)
define('AUTH_SECRET_KEY', getenv('AUTH_SECRET') ?: 'cdss_dbd_cf_system_secret_key_2026');

// Auto-restore session from signed HMAC cookie if session is empty across serverless container cold starts
if (empty($_SESSION['user_id']) && !empty($_COOKIE['cdss_auth'])) {
    $cookieVal = $_COOKIE['cdss_auth'];
    $parts = explode('.', $cookieVal, 2);
    if (count($parts) === 2) {
        list($payloadB64, $signature) = $parts;
        $expectedSig = hash_hmac('sha256', $payloadB64, AUTH_SECRET_KEY);
        if (hash_equals($expectedSig, $signature)) {
            $data = json_decode(base64_decode($payloadB64), true);
            if (is_array($data) && !empty($data['user_id']) && (!isset($data['exp']) || $data['exp'] > time())) {
                $_SESSION['user_id']      = $data['user_id'];
                $_SESSION['username']     = $data['username'] ?? '';
                $_SESSION['nama_lengkap'] = $data['nama_lengkap'] ?? '';
                $_SESSION['role']         = $data['role'] ?? 'dokter';
            }
        }
    }
}

/**
 * Set user authenticated session & issue signed persistent cookie
 */
function setAuthSession(array $user, int $days = 7): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = (int)($user['id'] ?? $user['id_user'] ?? 0);
    $username = (string)($user['username'] ?? '');
    $namaLengkap = (string)($user['nama_lengkap'] ?? 'Pengguna');
    $role = (string)($user['role'] ?? 'dokter');

    $_SESSION['user_id']      = $userId;
    $_SESSION['username']     = $username;
    $_SESSION['nama_lengkap'] = $namaLengkap;
    $_SESSION['role']         = $role;

    // Build HMAC token for serverless persistence
    $payload = [
        'user_id'      => $userId,
        'username'     => $username,
        'nama_lengkap' => $namaLengkap,
        'role'         => $role,
        'exp'          => time() + ($days * 86400)
    ];

    $b64 = base64_encode(json_encode($payload));
    $sig = hash_hmac('sha256', $b64, AUTH_SECRET_KEY);
    $token = $b64 . '.' . $sig;

    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    setcookie('cdss_auth', $token, [
        'expires'  => time() + ($days * 86400),
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Destroy session and invalidate auth cookie
 */
function clearAuthSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    setcookie('cdss_auth', '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'httponly' => true
    ]);

    session_destroy();
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $loginUrl = BASE_URL . '/login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user info
 */
function currentUser(): array {
    return [
        'id'           => $_SESSION['user_id'] ?? null,
        'username'     => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Pengguna',
        'role'         => $_SESSION['role'] ?? 'dokter',
        'initials'     => strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 2)),
    ];
}
