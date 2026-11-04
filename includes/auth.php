<?php
/**
 * Authentication Guard
 * CDSS-DBD - Session & Access Control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
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
