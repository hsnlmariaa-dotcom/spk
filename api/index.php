<?php
/**
 * Vercel Serverless Function Entrypoint / Front Controller
 * Clinical Decision Support System (CDSS) - Demam Berdarah Dengue (DBD)
 */

// Enable output buffering to prevent header/cookie issues
ob_start();

$rootDir = dirname(__DIR__);

// Normalize requested URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Default target script
$script = 'index.php';

if ($path === '' || $path === '/') {
    $script = 'index.php';
} else {
    // 1. Direct match with .php extension
    if (file_exists($rootDir . '/' . $path) && is_file($rootDir . '/' . $path) && substr($path, -4) === '.php') {
        $script = $path;
    }
    // 2. Clean URL without .php (e.g. /login -> login.php)
    elseif (file_exists($rootDir . '/' . $path . '.php') && is_file($rootDir . '/' . $path . '.php')) {
        $script = $path . '.php';
    }
    // 3. Static asset fallback (in case route bypass did not catch it)
    else {
        $staticPath = $rootDir . '/' . $path;
        if (file_exists($staticPath) && is_file($staticPath)) {
            $ext = strtolower(pathinfo($staticPath, PATHINFO_EXTENSION));
            $mimes = [
                'css'   => 'text/css',
                'js'    => 'application/javascript',
                'json'  => 'application/json',
                'png'   => 'image/png',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'gif'   => 'image/gif',
                'svg'   => 'image/svg+xml',
                'ico'   => 'image/x-icon',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf'
            ];
            $contentType = $mimes[$ext] ?? 'application/octet-stream';
            header('Content-Type: ' . $contentType);
            readfile($staticPath);
            exit();
        }

        // Default fallback to index.php
        $script = 'index.php';
    }
}

// Emulate direct script execution
$_SERVER['SCRIPT_NAME'] = '/' . $script;
$_SERVER['PHP_SELF'] = '/' . $script;

// Switch working directory to root so relative requires work seamlessly
chdir($rootDir);

// Execute the target script
require $rootDir . '/' . $script;

// Flush output buffer
ob_end_flush();

