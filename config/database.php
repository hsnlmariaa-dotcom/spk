<?php
/**
 * Database Connection Configuration
 * Clinical Decision Support System (CDSS) - Demam Berdarah Dengue (DBD)
 */

// Parse DATABASE_URL / MYSQL_URL if provided (e.g. Railway, PlanetScale, Supabase)
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl);
    $cfgHost = $parsedUrl['host'] ?? 'localhost';
    $cfgPort = (string)($parsedUrl['port'] ?? '3306');
    $cfgUser = $parsedUrl['user'] ?? 'root';
    $cfgPass = $parsedUrl['pass'] ?? '';
    $cfgName = ltrim($parsedUrl['path'] ?? 'cdss_dbd', '/');
} else {
    $cfgHost = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
    $cfgPort = (string)(getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306'));
    $cfgUser = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
    $cfgPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : ''));
    $cfgName = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'cdss_dbd');
}

define('DB_HOST', $cfgHost);
define('DB_PORT', $cfgPort);
define('DB_USER', $cfgUser);
define('DB_PASS', $cfgPass);
define('DB_NAME', $cfgName);

// Base URL configuration (Supports localhost, Laragon/XAMPP subfolders, and Vercel HTTPS reverse proxy)
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// If APP_URL is explicitly set in env, prioritize it
if (getenv('APP_URL')) {
    $baseUrl = rtrim(getenv('APP_URL'), '/');
} else {
    $baseUrl = rtrim($protocol . $host . $scriptDir, '/');
    if (substr($baseUrl, -7) === '/config' || substr($baseUrl, -4) === '/api') {
        $baseUrl = dirname($baseUrl);
    }
}
define('BASE_URL', $baseUrl);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Enable SSL for cloud MySQL providers (TiDB Cloud, Aiven, PlanetScale, etc.)
            $isRemoteHost = (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1');
            $useSsl = getenv('DB_SSL') === 'true' || (getenv('DB_SSL') !== 'false' && $isRemoteHost);

            if ($useSsl && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $isVercel = !empty($_SERVER['VERCEL']) || !empty(getenv('VERCEL'));
            $advice = $isVercel
                ? "<p><strong>Panduan Vercel:</strong> Buka <em>Vercel Dashboard &rarr; Settings &rarr; Environment Variables</em>, pastikan <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_USER</code>, <code>DB_PASS</code>, dan <code>DB_NAME</code> telah terisi dengan database cloud (seperti TiDB Serverless, Aiven, atau Railway).</p>"
                : "<p>Pastikan MySQL di Laragon/XAMPP sudah aktif dan database sudah di-import.</p>";

            die("<div style='font-family: system-ui, -apple-system, sans-serif; padding: 24px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 10px; margin: 30px auto; max-width: 650px; line-height: 1.6;'>
                <h3 style='margin-top:0; color: #7f1d1d; display: flex; align-items: center; gap: 8px;'>
                    <span>&#9888;</span> Koneksi Database Gagal
                </h3>
                <p>Tidak dapat terhubung ke MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong> di host <strong>" . htmlspecialchars(DB_HOST) . ":" . htmlspecialchars(DB_PORT) . "</strong>.</p>
                <div style='background: rgba(0,0,0,0.05); padding: 10px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; margin: 12px 0;'>
                    " . htmlspecialchars($e->getMessage()) . "
                </div>
                {$advice}
            </div>");
        }
    }
    return $pdo;
}
