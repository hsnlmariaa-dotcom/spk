<?php
/**
 * Header Template - Minimalist White Clinical Dashboard Shell
 * Clinical Decision Support System (CDSS) - Demam Berdarah Dengue (DBD)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cf_engine.php';
require_once __DIR__ . '/auth.php';

$pdo = getDB();
requireLogin(); // Proteksi semua halaman
$authUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Map page names for breadcrumbs
$pageNames = [
    'index.php' => 'Dashboard Utama',
    'diagnosa.php' => 'Formulir Diagnosa CDSS',
    'hasil.php' => 'Hasil Diagnosis Medis',
    'pasien.php' => 'Rekam Medis Pasien',
    'validasi.php' => 'Validasi & Akurasi Model',
    'basis_pengetahuan.php' => 'Basis Pengetahuan & CF Pakar',
    'panduan.php' => 'Panduan Teori & Protokol WHO'
];
$currentBreadcrumb = $pageNames[$currentPage] ?? 'Dashboard';

// Count total patients for sidebar badge
$totalPasienSidebar = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Sistem Pendukung Keputusan Klinis (CDSS / SPKK) untuk diagnosis awal Demam Berdarah Dengue (DBD) dengan metode Certainty Factor.">
    <meta name="keywords" content="Dashboard CDSS, SPKK Medis, Demam Berdarah Dengue, Certainty Factor, WHO Dengue">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>CDSS-DBD • Dashboard SPKK Demam Berdarah Dengue</title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'><path d='M19 14V6c0-1.1-.9-2-2-2H7c-1.1 0-2 .9-2 2v8H3v2h2v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4h2v-2h-2zm-8-6h2v3h3v2h-3v3h-2v-3H8v-2h3V8z'/></svg>">
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="dashboard-sidebar no-print" id="dashboardSidebar">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-header">
            <div class="sidebar-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 14V6c0-1.1-.9-2-2-2H7c-1.1 0-2 .9-2 2v8H3v2h2v4c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-4h2v-2h-2z"/>
                    <path d="M12 8v8"/>
                    <path d="M8 12h8"/>
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <h1>CDSS-DBD</h1>
                <span>Clinical Decision Support</span>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <!-- Group 1: Menu Utama -->
            <div class="sidebar-nav-group">
                <span class="nav-section-label">Menu Utama</span>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?= BASE_URL ?>/index.php" class="sidebar-link <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/diagnosa.php" class="sidebar-link <?= $currentPage == 'diagnosa.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                            <span>Diagnosa CDSS</span>
                            <span class="badge badge-primary sidebar-badge">Live CF</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/pasien.php" class="sidebar-link <?= $currentPage == 'pasien.php' || $currentPage == 'hasil.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Rekam Medis</span>
                            <span class="badge badge-secondary sidebar-badge"><?= $totalPasienSidebar ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 2: Analisis & Validasi -->
            <div class="sidebar-nav-group">
                <span class="nav-section-label">Analisis &amp; Validasi</span>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?= BASE_URL ?>/validasi.php" class="sidebar-link <?= $currentPage == 'validasi.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                            <span>Validasi &amp; Akurasi</span>
                            <span class="badge badge-success sidebar-badge">100%</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/basis_pengetahuan.php" class="sidebar-link <?= $currentPage == 'basis_pengetahuan.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>
                            <span>Basis Pengetahuan</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 3: Pedoman Medis -->
            <div class="sidebar-nav-group">
                <span class="nav-section-label">Pedoman Medis</span>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?= BASE_URL ?>/panduan.php" class="sidebar-link <?= $currentPage == 'panduan.php' ? 'active' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                            <span>Panduan Teori CF</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Sidebar Footer Status Box -->
        <div class="sidebar-footer">
            <div class="sidebar-status-box">
                <div class="sidebar-status-title">
                    <span class="status-indicator"></span>
                    Sistem CDSS Aktif
                </div>
                <div class="sidebar-status-desc">
                    CF Engine &bull; Kriteria WHO
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Dashboard Body -->
    <div class="dashboard-body">
        <!-- Top Navigation Bar -->
        <header class="dashboard-topbar no-print">
            <div class="topbar-left">
                <button type="button" class="topbar-menu-toggle" id="sidebarToggle" aria-label="Toggle Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div class="topbar-breadcrumb">
                    <span>CDSS DBD</span>
                    <span class="divider">/</span>
                    <span class="active-crumb"><?= htmlspecialchars($currentBreadcrumb) ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <div class="topbar-date">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span><?= date('D, d M Y') ?></span>
                </div>

                <?php if ($currentPage != 'diagnosa.php'): ?>
                    <a href="<?= BASE_URL ?>/diagnosa.php" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Diagnosa Baru
                    </a>
                <?php endif; ?>

                <div class="topbar-separator"></div>

                <div class="topbar-user" style="position:relative;">
                    <div class="user-avatar" style="cursor:default;"><?= htmlspecialchars($authUser['initials']) ?></div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($authUser['nama_lengkap']) ?></span>
                        <span class="user-role" style="text-transform:capitalize;"><?= htmlspecialchars($authUser['role']) ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/logout.php" title="Keluar dari sistem"
                       style="margin-left:10px;display:inline-flex;align-items:center;padding:6px 8px;border-radius:7px;color:#64748b;text-decoration:none;transition:all .2s;border:1px solid #e2e8f0;"
                       onmouseover="this.style.background='#fee2e2';this.style.color='#dc2626';this.style.borderColor='#fca5a5';"
                       onmouseout="this.style.background='';this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="dashboard-content">
            <div class="container-fluid">
