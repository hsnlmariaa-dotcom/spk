<?php
/**
 * Logout Handler - CDSS DBD
 */

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Hapus semua session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect ke login
header('Location: ' . BASE_URL . '/login.php?logout=1');
exit();
