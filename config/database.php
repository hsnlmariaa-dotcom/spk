<?php
/**
 * Database Connection Configuration
 * Clinical Decision Support System (CDSS) - Demam Berdarah Dengue (DBD)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cdss_dbd');
define('DB_PORT', '3306');

// Base URL configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
if (substr($baseUrl, -7) === '/config' || substr($baseUrl, -4) === '/api') {
    $baseUrl = dirname($baseUrl);
}
define('BASE_URL', $baseUrl);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px; margin: 20px auto; max-width: 600px;'>
                <h3 style='margin-top:0;'>Koneksi Database Gagal</h3>
                <p>Tidak dapat terhubung ke MySQL database <strong>" . DB_NAME . "</strong> di <strong>" . DB_HOST . "</strong>.</p>
                <p><small>Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
                <p>Pastikan MySQL di Laragon/XAMPP sudah aktif dan database sudah di-import.</p>
            </div>");
        }
    }
    return $pdo;
}
