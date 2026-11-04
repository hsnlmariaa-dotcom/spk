<?php
/**
 * Logout Handler - CDSS DBD
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Hapus session dan cookie autentikasi
clearAuthSession();

// Redirect ke login
header('Location: ' . BASE_URL . '/login.php?logout=1');
exit();
