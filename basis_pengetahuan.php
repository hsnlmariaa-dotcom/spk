<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cf_engine.php';

$pdo = getDB();
$successMsg = '';
$errorMsg = '';

// Handle URL status messages (from PRG pattern redirects)
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $successMsg = 'Gejala / parameter laboratorium baru berhasil ditambahkan ke basis pengetahuan!';
            break;
        case 'updated':
            $successMsg = 'Data gejala / parameter laboratorium berhasil diperbarui.';
            break;
        case 'deleted':
            $successMsg = 'Data gejala / parameter laboratorium berhasil dihapus dari sistem.';
            break;
        case 'cf_updated':
            $successMsg = 'Nilai CF Pakar pada basis pengetahuan berhasil disimpan dan diperbarui!';
            break;
        case 'synced':
            $successMsg = 'Basis pengetahuan berhasil disinkronkan ke file JSON dan backup basis data.';
            break;
        case 'skala_saved':
            $successMsg = 'Data skala konversi CF User berhasil diperbarui.';
            break;
        case 'rule_saved':
            $successMsg = 'Rule Base WHO berhasil disimpan.';
            break;
        case 'rule_deleted':
            $successMsg = 'Rule Base WHO berhasil dihapus.';
            break;
    }
}
if (isset($_GET['err'])) {
    $errorMsg = htmlspecialchars($_GET['err']);
}

// -------------------------------------------------------------
// BACKEND ACTION HANDLERS (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH GEJALA / PARAMETER LAB BARU
    if ($action === 'tambah_gejala') {
        $kode = strtoupper(trim($_POST['kode_gejala'] ?? ''));
        $nama = trim($_POST['nama_gejala'] ?? '');
        $kategori = in_array($_POST['kategori'] ?? '', ['klinis', 'lab']) ? $_POST['kategori'] : 'klinis';
        $kriteria = !empty($_POST['kriteria']) ? trim($_POST['kriteria']) : null;
        $keterangan = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : null;
        $cfPakar = floatval($_POST['cf_pakar'] ?? 0.8);
        $cfPakar = max(0.0, min(1.0, $cfPakar));

        if (empty($kode) || empty($nama)) {
            $err = urlencode('Kode dan nama gejala/parameter wajib diisi.');
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }

        try {
            // Check uniqueness of code
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM gejala WHERE kode_gejala = ?");
            $stmtCheck->execute([$kode]);
            if ($stmtCheck->fetchColumn() > 0) {
                $err = urlencode("Kode gejala '$kode' sudah digunakan dalam basis data.");
                header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
                exit();
            }

            $pdo->beginTransaction();

            // Insert into gejala
            $stmtInsG = $pdo->prepare("INSERT INTO gejala (kode_gejala, nama_gejala, kategori, kriteria, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmtInsG->execute([$kode, $nama, $kategori, $kriteria, $keterangan]);
            $newGejalaId = $pdo->lastInsertId();

            // Insert into aturan for disease ID 1 (DBD)
            $stmtInsA = $pdo->prepare("INSERT INTO aturan (id_penyakit, id_gejala, nilai_cf) VALUES (1, ?, ?)");
            $stmtInsA->execute([$newGejalaId, $cfPakar]);

            $pdo->commit();

            // Automatically sync cdss_data.json
            CFEngine::syncKnowledgeBaseToJson($pdo);

            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=added");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = urlencode('Gagal menambahkan data: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 2. EDIT GEJALA / PARAMETER LAB
    if ($action === 'edit_gejala') {
        $idGejala = (int)($_POST['id_gejala'] ?? 0);
        $kode = strtoupper(trim($_POST['kode_gejala'] ?? ''));
        $nama = trim($_POST['nama_gejala'] ?? '');
        $kategori = in_array($_POST['kategori'] ?? '', ['klinis', 'lab']) ? $_POST['kategori'] : 'klinis';
        $kriteria = !empty($_POST['kriteria']) ? trim($_POST['kriteria']) : null;
        $keterangan = !empty($_POST['keterangan']) ? trim($_POST['keterangan']) : null;
        $cfPakar = floatval($_POST['cf_pakar'] ?? 0.8);
        $cfPakar = max(0.0, min(1.0, $cfPakar));

        if ($idGejala <= 0 || empty($kode) || empty($nama)) {
            $err = urlencode('Data edit tidak lengkap.');
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }

        try {
            // Check uniqueness of code excluding self
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM gejala WHERE kode_gejala = ? AND id_gejala != ?");
            $stmtCheck->execute([$kode, $idGejala]);
            if ($stmtCheck->fetchColumn() > 0) {
                $err = urlencode("Kode gejala '$kode' sudah digunakan oleh gejala lain.");
                header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
                exit();
            }

            $pdo->beginTransaction();

            // Update gejala
            $stmtUpdG = $pdo->prepare("UPDATE gejala SET kode_gejala = ?, nama_gejala = ?, kategori = ?, kriteria = ?, keterangan = ? WHERE id_gejala = ?");
            $stmtUpdG->execute([$kode, $nama, $kategori, $kriteria, $keterangan, $idGejala]);

            // Update or insert aturan
            $stmtCheckA = $pdo->prepare("SELECT COUNT(*) FROM aturan WHERE id_gejala = ? AND id_penyakit = 1");
            $stmtCheckA->execute([$idGejala]);
            if ($stmtCheckA->fetchColumn() > 0) {
                $stmtUpdA = $pdo->prepare("UPDATE aturan SET nilai_cf = ? WHERE id_gejala = ? AND id_penyakit = 1");
                $stmtUpdA->execute([$cfPakar, $idGejala]);
            } else {
                $stmtInsA = $pdo->prepare("INSERT INTO aturan (id_penyakit, id_gejala, nilai_cf) VALUES (1, ?, ?)");
                $stmtInsA->execute([$idGejala, $cfPakar]);
            }

            $pdo->commit();

            // Sync cdss_data.json
            CFEngine::syncKnowledgeBaseToJson($pdo);

            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=updated");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = urlencode('Gagal memperbarui data: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 3. HAPUS GEJALA / PARAMETER LAB
    if ($action === 'hapus_gejala') {
        $idGejala = (int)($_POST['id_gejala'] ?? 0);

        if ($idGejala <= 0) {
            $err = urlencode('ID gejala tidak valid.');
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Delete cascading foreign keys
            $pdo->prepare("DELETE FROM pasien_gejala WHERE id_gejala = ?")->execute([$idGejala]);
            $pdo->prepare("DELETE FROM aturan WHERE id_gejala = ?")->execute([$idGejala]);
            $pdo->prepare("DELETE FROM gejala WHERE id_gejala = ?")->execute([$idGejala]);

            $pdo->commit();

            // Sync cdss_data.json
            CFEngine::syncKnowledgeBaseToJson($pdo);

            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=deleted");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = urlencode('Gagal menghapus gejala: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 4. BATCH UPDATE NILAI CF PAKAR
    if ($action === 'batch_update_cf') {
        try {
            $pdo->beginTransaction();
            $cfInputs = $_POST['cf_pakar'] ?? [];
            $stmtUpdateA = $pdo->prepare("UPDATE aturan SET nilai_cf = ? WHERE id_gejala = ? AND id_penyakit = 1");

            foreach ($cfInputs as $idGejala => $newCF) {
                $val = floatval($newCF);
                if ($val >= 0.0 && $val <= 1.0) {
                    $stmtUpdateA->execute([$val, (int)$idGejala]);
                }
            }

            $pdo->commit();

            // Sync cdss_data.json
            CFEngine::syncKnowledgeBaseToJson($pdo);

            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=cf_updated");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = urlencode('Gagal memperbarui CF Pakar: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 5. UPDATE / TAMBAH SKALA CF USER
    if ($action === 'save_skala') {
        $idSkala = !empty($_POST['id_skala']) ? (int)$_POST['id_skala'] : null;
        $pilihan = trim($_POST['pilihan'] ?? '');
        $nilaiCf = floatval($_POST['nilai_cf'] ?? 0.0);
        $keterangan = trim($_POST['keterangan'] ?? '');

        if (empty($pilihan)) {
            $err = urlencode('Nama pilihan skala keyakinan tidak boleh kosong.');
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }

        try {
            if ($idSkala) {
                $stmt = $pdo->prepare("UPDATE skala_cf SET pilihan = ?, nilai_cf = ?, keterangan = ? WHERE id_skala = ?");
                $stmt->execute([$pilihan, $nilaiCf, $keterangan, $idSkala]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO skala_cf (pilihan, nilai_cf, keterangan) VALUES (?, ?, ?)");
                $stmt->execute([$pilihan, $nilaiCf, $keterangan]);
            }

            CFEngine::syncKnowledgeBaseToJson($pdo);
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=skala_saved");
            exit();
        } catch (Exception $e) {
            $err = urlencode('Gagal menyimpan skala: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 6. SAVE RULE BASE WHO
    if ($action === 'save_rule') {
        $idRule = !empty($_POST['id_rule']) ? (int)$_POST['id_rule'] : null;
        $kodeRule = strtoupper(trim($_POST['kode_rule'] ?? ''));
        $kondisi = trim($_POST['kondisi'] ?? '');
        $kesimpulan = trim($_POST['kesimpulan'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');

        if (empty($kodeRule) || empty($kondisi) || empty($kesimpulan)) {
            $err = urlencode('Kode rule, kondisi IF, dan kesimpulan THEN wajib diisi.');
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }

        try {
            if ($idRule) {
                $stmt = $pdo->prepare("UPDATE rule_base SET kode_rule = ?, kondisi = ?, kesimpulan = ?, catatan = ? WHERE id_rule = ?");
                $stmt->execute([$kodeRule, $kondisi, $kesimpulan, $catatan, $idRule]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rule_base (kode_rule, kondisi, kesimpulan, catatan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kodeRule, $kondisi, $kesimpulan, $catatan]);
            }

            CFEngine::syncKnowledgeBaseToJson($pdo);
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=rule_saved");
            exit();
        } catch (Exception $e) {
            $err = urlencode('Gagal menyimpan rule base: ' . $e->getMessage());
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
            exit();
        }
    }

    // 7. DELETE RULE BASE WHO
    if ($action === 'delete_rule') {
        $idRule = (int)($_POST['id_rule'] ?? 0);
        if ($idRule > 0) {
            $pdo->prepare("DELETE FROM rule_base WHERE id_rule = ?")->execute([$idRule]);
            CFEngine::syncKnowledgeBaseToJson($pdo);
            header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=rule_deleted");
            exit();
        }
    }

    // 8. SINKRONISASI JSON & BACKUP
    if ($action === 'sync_json') {
        CFEngine::syncKnowledgeBaseToJson($pdo);
        header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=synced");
        exit();
    }
}

// -------------------------------------------------------------
// GET DELETE HANDLER (Alternative direct link with confirmation)
// -------------------------------------------------------------
if (isset($_GET['delete_gejala'])) {
    $idGejala = (int)$_GET['delete_gejala'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM pasien_gejala WHERE id_gejala = ?")->execute([$idGejala]);
        $pdo->prepare("DELETE FROM aturan WHERE id_gejala = ?")->execute([$idGejala]);
        $pdo->prepare("DELETE FROM gejala WHERE id_gejala = ?")->execute([$idGejala]);
        $pdo->commit();

        CFEngine::syncKnowledgeBaseToJson($pdo);
        header("Location: " . BASE_URL . "/basis_pengetahuan.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = urlencode("Gagal menghapus gejala: " . $e->getMessage());
        header("Location: " . BASE_URL . "/basis_pengetahuan.php?err=$err");
        exit();
    }
}

// -------------------------------------------------------------
// FETCH DATA FOR VIEW
// -------------------------------------------------------------
$gejalaList = $pdo->query("
    SELECT g.*, a.id_aturan, COALESCE(a.nilai_cf, 0.0) as cf_pakar 
    FROM gejala g
    LEFT JOIN aturan a ON g.id_gejala = a.id_gejala AND a.id_penyakit = 1
    ORDER BY g.id_gejala ASC
")->fetchAll();

$gejalaKlinis = array_values(array_filter($gejalaList, fn($g) => $g['kategori'] === 'klinis'));
$parameterLab = array_values(array_filter($gejalaList, fn($g) => $g['kategori'] === 'lab'));

// Fetch user certainty scale
$skalaList = $pdo->query("SELECT * FROM skala_cf ORDER BY nilai_cf ASC")->fetchAll();

// Fetch WHO rules
$ruleList = $pdo->query("SELECT * FROM rule_base ORDER BY id_rule ASC")->fetchAll();

// Calculate next suggested codes
$maxG = 0;
foreach ($gejalaKlinis as $gk) {
    if (preg_match('/^G(\d+)$/i', $gk['kode_gejala'], $m)) {
        $maxG = max($maxG, (int)$m[1]);
    }
}
$suggestedNextG = 'G' . str_pad($maxG + 1, 2, '0', STR_PAD_LEFT);

$maxL = 0;
foreach ($parameterLab as $pl) {
    if (preg_match('/^L(\d+)$/i', $pl['kode_gejala'], $m)) {
        $maxL = max($maxL, (int)$m[1]);
    }
}
$suggestedNextL = 'L' . str_pad($maxL + 1, 2, '0', STR_PAD_LEFT);

// Calculate averages and counts
$totalKlinis = count($gejalaKlinis);
$totalLab = count($parameterLab);
$totalGejala = count($gejalaList);
$avgCF = $totalGejala > 0 ? (array_sum(array_column($gejalaList, 'cf_pakar')) / $totalGejala) : 0.0;

$pageTitle = 'Basis Pengetahuan & Aturan Pakar';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb & Page Header -->
    <div class="page-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <div class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>
                Basis Pengetahuan & Aturan Pakar (Knowledge Base)
            </div>
            <div class="page-subtitle">
                Manajemen data gejala klinis, parameter laboratorium, bobot kepakaran Certainty Factor ($CF[h,e]$), skala keyakinan klinis, dan rule base klasifikasi WHO.
            </div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <button type="button" class="btn btn-primary" onclick="openTambahModal('klinis')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Tambah Gejala Klinis
            </button>
            <button type="button" class="btn btn-secondary" onclick="openTambahModal('lab')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Tambah Parameter Lab
            </button>
            <form method="POST" style="margin: 0; display: inline;">
                <input type="hidden" name="action" value="sync_json">
                <button type="submit" class="btn btn-secondary" title="Sinkronkan database ke file cdss_data.json">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                    Sinkronkan JSON
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($successMsg): ?>
        <div class="alert-banner" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: #047857; margin-bottom: 1.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-banner danger" style="margin-bottom: 1.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div>
                <div class="stat-value" style="color: var(--primary);"><?= $totalKlinis ?></div>
                <div class="stat-label">Gejala Klinis (G)</div>
            </div>
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
        </div>

        <div class="stat-card emerald">
            <div>
                <div class="stat-value" style="color: var(--emerald);"><?= $totalLab ?></div>
                <div class="stat-label">Parameter Lab (L)</div>
            </div>
            <div class="stat-icon" style="background: var(--emerald-light); color: var(--emerald);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.31"/><path d="M14 9.3V1.99"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/><path d="M5.52 16h12.96"/></svg>
            </div>
        </div>

        <div class="stat-card teal">
            <div>
                <div class="stat-value" style="color: var(--teal);"><?= number_format($avgCF, 2) ?></div>
                <div class="stat-label">Rata-rata CF Pakar</div>
            </div>
            <div class="stat-icon" style="background: var(--teal-light); color: var(--teal);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m16 12-4-4-4 4"/><path d="M12 16V8"/></svg>
            </div>
        </div>

        <div class="stat-card amber">
            <div>
                <div class="stat-value" style="color: var(--amber);"><?= count($skalaList) ?></div>
                <div class="stat-label">Skala Keyakinan User</div>
            </div>
            <div class="stat-icon" style="background: var(--amber-light); color: var(--amber);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
        </div>

        <div class="stat-card indigo">
            <div>
                <div class="stat-value" style="color: var(--indigo);"><?= count($ruleList) ?></div>
                <div class="stat-label">Rule Base WHO</div>
            </div>
            <div class="stat-icon" style="background: var(--indigo-light); color: var(--indigo);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
        </div>
    </div>

    <!-- Quick Search Filter Bar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 0.85rem 1.25rem;">
        <div style="display: flex; align-items: center; gap: 12px; justify-content: space-between; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="kbSearchInput" class="form-control" placeholder="Cari kode gejala, nama tanda klinis, kriteria lab, atau keterangan..." style="border-radius: var(--radius-full); padding-left: 14px; font-size: 0.85rem;" onkeyup="filterTables()">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">Status Basis Data:</span>
                <span class="badge badge-success">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #059669; display: inline-block;"></span>
                    Tersinkronisasi
                </span>
            </div>
        </div>
    </div>

    <!-- Master Form for Batch Updating CF Pakar -->
    <form method="POST" id="batchCfForm">
        <input type="hidden" name="action" value="batch_update_cf">

        <!-- Gejala Klinis Table Card -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Tabel Basis Pengetahuan — Gejala Klinis DBD
                    <span class="badge badge-primary" style="margin-left: 6px; font-size: 0.72rem;" id="countKlinisBadge"><?= $totalKlinis ?> Data</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openTambahModal('klinis')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        + Tambah Gejala
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Simpan Bobot CF
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="tableKlinis">
                    <thead>
                        <tr>
                            <th style="width: 75px; text-align: center;">Kode</th>
                            <th style="min-width: 200px;">Gejala Klinis</th>
                            <th>Sumber / Dasar Kriteria Medis</th>
                            <th style="width: 140px; text-align: center;">CF Pakar ($CF[h,e]$)</th>
                            <th style="width: 110px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gejalaKlinis)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada gejala klinis terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gejalaKlinis as $g): ?>
                                <tr class="row-symptom" data-search="<?= strtolower(htmlspecialchars($g['kode_gejala'] . ' ' . $g['nama_gejala'] . ' ' . ($g['keterangan'] ?? ''))) ?>">
                                    <td style="text-align: center;">
                                        <span class="item-code"><?= htmlspecialchars($g['kode_gejala']) ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: #0f172a; font-size: 0.88rem;"><?= htmlspecialchars($g['nama_gejala']) ?></strong>
                                    </td>
                                    <td style="color: var(--text-secondary); font-size: 0.82rem;">
                                        <?= htmlspecialchars($g['keterangan'] ?? '-') ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" 
                                               step="0.05" 
                                               min="0" 
                                               max="1" 
                                               name="cf_pakar[<?= $g['id_gejala'] ?>]" 
                                               value="<?= number_format($g['cf_pakar'], 2) ?>" 
                                               class="form-control" 
                                               title="Edit bobot CF pakar langsung"
                                               style="width: 85px; margin: 0 auto; text-align: center; font-family: var(--font-mono); font-weight: 700; color: var(--primary);">
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                            <button type="button" 
                                                    class="btn btn-secondary btn-sm" 
                                                    style="padding: 4px 7px;"
                                                    title="Edit Nama / Kriteria / Bobot"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($g)) ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    style="padding: 4px 7px;"
                                                    title="Hapus Gejala"
                                                    onclick="confirmDelete(<?= $g['id_gejala'] ?>, '<?= htmlspecialchars(addslashes($g['kode_gejala'])) ?>', '<?= htmlspecialchars(addslashes($g['nama_gejala'])) ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Parameter Laboratorium Table Card -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.31"/><path d="M14 9.3V1.99"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/><path d="M5.52 16h12.96"/></svg>
                    Tabel Basis Pengetahuan — Parameter Laboratorium
                    <span class="badge badge-success" style="margin-left: 6px; font-size: 0.72rem;" id="countLabBadge"><?= $totalLab ?> Data</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openTambahModal('lab')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        + Tambah Lab
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Simpan Bobot CF
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="tableLab">
                    <thead>
                        <tr>
                            <th style="width: 75px; text-align: center;">Kode</th>
                            <th style="min-width: 180px;">Parameter Laboratorium</th>
                            <th>Kriteria Nilai Kritis</th>
                            <th>Sumber / Keterangan Medis</th>
                            <th style="width: 140px; text-align: center;">CF Pakar ($CF[h,e]$)</th>
                            <th style="width: 110px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parameterLab)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada parameter laboratorium terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parameterLab as $l): ?>
                                <tr class="row-symptom" data-search="<?= strtolower(htmlspecialchars($l['kode_gejala'] . ' ' . $l['nama_gejala'] . ' ' . ($l['kriteria'] ?? '') . ' ' . ($l['keterangan'] ?? ''))) ?>">
                                    <td style="text-align: center;">
                                        <span class="item-code lab"><?= htmlspecialchars($l['kode_gejala']) ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: #0f172a; font-size: 0.88rem;"><?= htmlspecialchars($l['nama_gejala']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($l['kriteria'])): ?>
                                            <span class="badge badge-warning" style="font-family: var(--font-mono); font-size: 0.75rem;"><?= htmlspecialchars($l['kriteria']) ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-secondary); font-size: 0.82rem;">
                                        <?= htmlspecialchars($l['keterangan'] ?? '-') ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number" 
                                               step="0.05" 
                                               min="0" 
                                               max="1" 
                                               name="cf_pakar[<?= $l['id_gejala'] ?>]" 
                                               value="<?= number_format($l['cf_pakar'], 2) ?>" 
                                               class="form-control" 
                                               title="Edit bobot CF pakar langsung"
                                               style="width: 85px; margin: 0 auto; text-align: center; font-family: var(--font-mono); font-weight: 700; color: var(--emerald);">
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                            <button type="button" 
                                                    class="btn btn-secondary btn-sm" 
                                                    style="padding: 4px 7px;"
                                                    title="Edit Nama / Kriteria / Bobot"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($l)) ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    style="padding: 4px 7px;"
                                                    title="Hapus Parameter Lab"
                                                    onclick="confirmDelete(<?= $l['id_gejala'] ?>, '<?= htmlspecialchars(addslashes($l['kode_gejala'])) ?>', '<?= htmlspecialchars(addslashes($l['nama_gejala'])) ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <!-- Additional Tables: Skala CF User & Rule Base WHO -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Skala CF User Table Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Tabel Konversi Skala CF User
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openSkalaModal()">
                    + Tambah Skala
                </button>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tingkat Keyakinan</th>
                            <th style="text-align: center; width: 100px;">Nilai CF</th>
                            <th>Keterangan Klinis</th>
                            <th style="width: 50px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skalaList as $s): ?>
                            <tr>
                                <td>
                                    <strong style="color: #0f172a; font-size: 0.85rem;"><?= htmlspecialchars($s['pilihan']) ?></strong>
                                </td>
                                <td style="text-align: center; font-family: var(--font-mono); font-weight: 700; color: var(--primary);">
                                    <?= number_format($s['nilai_cf'], 2) ?>
                                </td>
                                <td style="font-size: 0.82rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($s['keterangan'] ?? '-') ?>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            style="padding: 3px 6px;"
                                            title="Edit Skala"
                                            onclick="openSkalaModal(<?= htmlspecialchars(json_encode($s)) ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rule Base WHO Table Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Rule Base Derajat DBD (Pedoman WHO)
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openRuleModal()">
                    + Tambah Rule
                </button>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 65px; text-align: center;">Rule</th>
                            <th style="min-width: 130px;">Kondisi (IF)</th>
                            <th>Kesimpulan / Derajat</th>
                            <th style="width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ruleList as $r): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <span class="badge badge-primary"><?= htmlspecialchars($r['kode_rule']) ?></span>
                                </td>
                                <td style="font-family: var(--font-mono); font-size: 0.78rem; color: var(--primary);">
                                    <?= htmlspecialchars($r['kondisi']) ?>
                                </td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 0.85rem; display: block;"><?= htmlspecialchars($r['kesimpulan']) ?></strong>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($r['catatan'] ?? '') ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                        <button type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                style="padding: 3px 6px;"
                                                title="Edit Rule"
                                                onclick="openRuleModal(<?= htmlspecialchars(json_encode($r)) ?>)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>
                                        <form method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Hapus aturan ini?');">
                                            <input type="hidden" name="action" value="delete_rule">
                                            <input type="hidden" name="id_rule" value="<?= $r['id_rule'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 3px 6px;" title="Hapus Rule">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: TAMBAH GEJALA / PARAMETER LAB                                   -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Gejala / Parameter Lab Baru
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/basis_pengetahuan.php">
            <input type="hidden" name="action" value="tambah_gejala">
            
            <div class="modal-body">
                <!-- Category Tab Switcher -->
                <div class="modal-tab-group">
                    <button type="button" class="modal-tab-btn active" id="tabBtnKlinis" onclick="switchTambahCategory('klinis')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Gejala Klinis (G)
                    </button>
                    <button type="button" class="modal-tab-btn" id="tabBtnLab" onclick="switchTambahCategory('lab')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.31"/><path d="M14 9.3V1.99"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/><path d="M5.52 16h12.96"/></svg>
                        Parameter Lab (L)
                    </button>
                </div>
                <input type="hidden" name="kategori" id="tambahKategori" value="klinis">

                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Kode Gejala *</label>
                        <input type="text" 
                               name="kode_gejala" 
                               id="tambahKode" 
                               class="form-control" 
                               required 
                               style="font-family: var(--font-mono); font-weight: 700; text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Gejala / Parameter Medis *</label>
                        <input type="text" 
                               name="nama_gejala" 
                               id="tambahNama" 
                               class="form-control" 
                               placeholder="Contoh: Perdarahan Mukosa Saluran Cerna" 
                               required>
                    </div>
                </div>

                <div class="form-group" id="groupKriteriaLab" style="display: none;">
                    <label class="form-label">Nilai Kritis / Kriteria Hasil Lab</label>
                    <input type="text" 
                           name="kriteria" 
                           id="tambahKriteria" 
                           class="form-control" 
                           placeholder="Contoh: < 100.000 /uL atau Positif (+)">
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px; display: block;">
                        Kriteria ambang batas medis untuk memicu konfirmasi diagnosis.
                    </span>
                </div>

                <div class="form-group">
                    <label class="form-label">Dasar Kriteria / Sumber Medis</label>
                    <textarea name="keterangan" 
                              id="tambahKeterangan" 
                              class="form-control" 
                              rows="2" 
                              placeholder="Contoh: Kriteria WHO 2009 — Warning Signs kegawatdaruratan"></textarea>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label class="form-label" style="margin-bottom: 0;">Bobot CF Pakar ($CF[h,e]$) *</label>
                        <span id="tambahCfBadge" style="font-family: var(--font-mono); font-weight: 800; color: var(--primary); font-size: 0.95rem;">0.80</span>
                    </div>
                    <input type="number" 
                           step="0.05" 
                           min="0" 
                           max="1" 
                           name="cf_pakar" 
                           id="tambahCf" 
                           value="0.80" 
                           class="form-control" 
                           style="font-family: var(--font-mono); font-weight: 700; font-size: 1rem; text-align: center;" 
                           oninput="document.getElementById('tambahCfBadge').textContent = parseFloat(this.value || 0).toFixed(2)"
                           required>
                    
                    <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Preset Pakar:</span>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.50)">0.50 (Sedang)</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.60)">0.60 (Cukup Tinggi)</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.70)">0.70 (Spesifik)</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.80)">0.80 (Tinggi)</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.90)">0.90 (Sangat Tinggi)</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('tambahCf', 0.95)">0.95 (Kritis)</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Gejala Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: EDIT GEJALA / PARAMETER LAB                                     -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Edit Data Basis Pengetahuan
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalEdit')">&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/basis_pengetahuan.php">
            <input type="hidden" name="action" value="edit_gejala">
            <input type="hidden" name="id_gejala" id="editId">
            
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" id="editKategori" class="form-control" onchange="toggleEditKriteria(this.value)">
                            <option value="klinis">Gejala Klinis</option>
                            <option value="lab">Parameter Lab</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Gejala *</label>
                        <input type="text" 
                               name="kode_gejala" 
                               id="editKode" 
                               class="form-control" 
                               required 
                               style="font-family: var(--font-mono); font-weight: 700; text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Gejala / Parameter Medis *</label>
                    <input type="text" 
                           name="nama_gejala" 
                           id="editNama" 
                           class="form-control" 
                           required>
                </div>

                <div class="form-group" id="groupEditKriteria">
                    <label class="form-label">Nilai Kritis / Kriteria Hasil Lab</label>
                    <input type="text" 
                           name="kriteria" 
                           id="editKriteria" 
                           class="form-control" 
                           placeholder="Contoh: < 100.000 /uL atau Positif (+)">
                </div>

                <div class="form-group">
                    <label class="form-label">Dasar Kriteria / Sumber Medis</label>
                    <textarea name="keterangan" 
                              id="editKeterangan" 
                              class="form-control" 
                              rows="2"></textarea>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label class="form-label" style="margin-bottom: 0;">Bobot CF Pakar ($CF[h,e]$) *</label>
                        <span id="editCfBadge" style="font-family: var(--font-mono); font-weight: 800; color: var(--primary); font-size: 0.95rem;">0.80</span>
                    </div>
                    <input type="number" 
                           step="0.05" 
                           min="0" 
                           max="1" 
                           name="cf_pakar" 
                           id="editCf" 
                           class="form-control" 
                           style="font-family: var(--font-mono); font-weight: 700; font-size: 1rem; text-align: center;" 
                           oninput="document.getElementById('editCfBadge').textContent = parseFloat(this.value || 0).toFixed(2)"
                           required>
                    
                    <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Preset Pakar:</span>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.50)">0.50</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.60)">0.60</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.70)">0.70</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.80)">0.80</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.85)">0.85</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.90)">0.90</button>
                        <button type="button" class="chip-btn" onclick="setCfValue('editCf', 0.95)">0.95</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: KONFIRMASI HAPUS GEJALA                                         -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="modalDelete">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header" style="background: var(--rose-light); border-bottom-color: var(--rose-border);">
            <div class="modal-title" style="color: var(--rose);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                Konfirmasi Hapus Data
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalDelete')">&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/basis_pengetahuan.php">
            <input type="hidden" name="action" value="hapus_gejala">
            <input type="hidden" name="id_gejala" id="deleteId">

            <div class="modal-body">
                <p style="margin-bottom: 12px; color: var(--text-primary); font-size: 0.92rem;">
                    Apakah Anda yakin ingin menghapus data gejala/parameter berikut dari basis pengetahuan?
                </p>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span class="item-code" id="deleteKodeText">G00</span>
                        <strong id="deleteNamaText" style="color: #0f172a;">-</strong>
                    </div>
                </div>
                <p style="font-size: 0.78rem; color: var(--rose); line-height: 1.4;">
                    <strong>Perhatian:</strong> Menghapus item ini juga akan membersihkan aturan pakar (tabel aturan) dan referensi riwayat pemeriksaan pasien terkait gejala ini.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDelete')">Batal</button>
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Ya, Hapus Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: TAMBAH / EDIT SKALA CF USER                                     -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="modalSkala">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header">
            <div class="modal-title" id="skalaModalTitle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Kelola Skala Keyakinan User
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalSkala')">&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/basis_pengetahuan.php">
            <input type="hidden" name="action" value="save_skala">
            <input type="hidden" name="id_skala" id="skalaId">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tingkat Keyakinan (Pilihan Klinis) *</label>
                    <input type="text" name="pilihan" id="skalaPilihan" class="form-control" required placeholder="Contoh: Sedikit yakin">
                </div>

                <div class="form-group">
                    <label class="form-label">Nilai CF User (0.0 - 1.0) *</label>
                    <input type="number" step="0.1" min="0" max="1" name="nilai_cf" id="skalaNilai" class="form-control" required style="font-family: var(--font-mono); font-weight: 700; text-align: center;">
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan / Indikasi Klinis</label>
                    <input type="text" name="keterangan" id="skalaKeterangan" class="form-control" placeholder="Contoh: Keyakinan rendah pada fase prodromal">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalSkala')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Skala</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 5: TAMBAH / EDIT RULE BASE WHO                                     -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="modalRule">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title" id="ruleModalTitle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Kelola Rule Base Derajat DBD WHO
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modalRule')">&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/basis_pengetahuan.php">
            <input type="hidden" name="action" value="save_rule">
            <input type="hidden" name="id_rule" id="ruleId">

            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Kode Rule *</label>
                        <input type="text" name="kode_rule" id="ruleKode" class="form-control" required placeholder="R1" style="font-family: var(--font-mono); font-weight: 700; text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kondisi (IF Logika Gejala) *</label>
                        <input type="text" name="kondisi" id="ruleKondisi" class="form-control" required placeholder="G01 AND G05 AND L01" style="font-family: var(--font-mono);">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kesimpulan / Derajat DBD (THEN) *</label>
                    <input type="text" name="kesimpulan" id="ruleKesimpulan" class="form-control" required placeholder="DBD Derajat I / II / III-IV / Suspek">
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Medis & Kriteria WHO</label>
                    <textarea name="catatan" id="ruleCatatan" class="form-control" rows="2" placeholder="Demam + perdarahan spontan + trombositopenia"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalRule')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Rule</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC FOR MODALS & REAL-TIME FILTERING                        -->
<!-- ========================================================================= -->
<script>
const suggestedNextG = <?= json_encode($suggestedNextG) ?>;
const suggestedNextL = <?= json_encode($suggestedNextL) ?>;

// Open Tambah Modal with pre-configured category
function openTambahModal(kategori = 'klinis') {
    switchTambahCategory(kategori);
    document.getElementById('tambahNama').value = '';
    document.getElementById('tambahKriteria').value = '';
    document.getElementById('tambahKeterangan').value = '';
    document.getElementById('tambahCf').value = '0.80';
    document.getElementById('tambahCfBadge').textContent = '0.80';
    openModal('modalTambah');
    document.getElementById('tambahNama').focus();
}

// Switch Category in Tambah Modal
function switchTambahCategory(kategori) {
    document.getElementById('tambahKategori').value = kategori;
    const tabK = document.getElementById('tabBtnKlinis');
    const tabL = document.getElementById('tabBtnLab');
    const groupKriteria = document.getElementById('groupKriteriaLab');
    const kodeInput = document.getElementById('tambahKode');

    if (kategori === 'klinis') {
        tabK.classList.add('active');
        tabL.classList.remove('active');
        groupKriteria.style.display = 'none';
        kodeInput.value = suggestedNextG;
    } else {
        tabL.classList.add('active');
        tabK.classList.remove('active');
        groupKriteria.style.display = 'block';
        kodeInput.value = suggestedNextL;
    }
}

// Open Edit Modal with row data
function openEditModal(data) {
    document.getElementById('editId').value = data.id_gejala;
    document.getElementById('editKode').value = data.kode_gejala;
    document.getElementById('editNama').value = data.nama_gejala;
    document.getElementById('editKategori').value = data.kategori;
    document.getElementById('editKriteria').value = data.kriteria || '';
    document.getElementById('editKeterangan').value = data.keterangan || '';
    const cf = parseFloat(data.cf_pakar || 0.8).toFixed(2);
    document.getElementById('editCf').value = cf;
    document.getElementById('editCfBadge').textContent = cf;

    toggleEditKriteria(data.kategori);
    openModal('modalEdit');
}

function toggleEditKriteria(kategori) {
    const grp = document.getElementById('groupEditKriteria');
    if (kategori === 'lab') {
        grp.style.display = 'block';
    } else {
        grp.style.display = 'none';
    }
}

// Confirm Delete Modal
function confirmDelete(id, kode, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteKodeText').textContent = kode;
    document.getElementById('deleteNamaText').textContent = nama;
    openModal('modalDelete');
}

// Skala CF User Modal
function openSkalaModal(data = null) {
    if (data) {
        document.getElementById('skalaModalTitle').textContent = 'Edit Skala Keyakinan User';
        document.getElementById('skalaId').value = data.id_skala;
        document.getElementById('skalaPilihan').value = data.pilihan;
        document.getElementById('skalaNilai').value = parseFloat(data.nilai_cf).toFixed(2);
        document.getElementById('skalaKeterangan').value = data.keterangan || '';
    } else {
        document.getElementById('skalaModalTitle').textContent = 'Tambah Skala Keyakinan User';
        document.getElementById('skalaId').value = '';
        document.getElementById('skalaPilihan').value = '';
        document.getElementById('skalaNilai').value = '0.50';
        document.getElementById('skalaKeterangan').value = '';
    }
    openModal('modalSkala');
}

// Rule Base WHO Modal
function openRuleModal(data = null) {
    if (data) {
        document.getElementById('ruleModalTitle').textContent = 'Edit Rule Base WHO';
        document.getElementById('ruleId').value = data.id_rule;
        document.getElementById('ruleKode').value = data.kode_rule;
        document.getElementById('ruleKondisi').value = data.kondisi;
        document.getElementById('ruleKesimpulan').value = data.kesimpulan;
        document.getElementById('ruleCatatan').value = data.catatan || '';
    } else {
        document.getElementById('ruleModalTitle').textContent = 'Tambah Rule Base WHO Baru';
        document.getElementById('ruleId').value = '';
        document.getElementById('ruleKode').value = '';
        document.getElementById('ruleKondisi').value = '';
        document.getElementById('ruleKesimpulan').value = '';
        document.getElementById('ruleCatatan').value = '';
    }
    openModal('modalRule');
}

// Helper: set preset CF value
function setCfValue(inputId, val) {
    const inp = document.getElementById(inputId);
    inp.value = parseFloat(val).toFixed(2);
    if (inputId === 'tambahCf') {
        document.getElementById('tambahCfBadge').textContent = parseFloat(val).toFixed(2);
    } else if (inputId === 'editCf') {
        document.getElementById('editCfBadge').textContent = parseFloat(val).toFixed(2);
    }
}

// Generic Modal Controls
function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) {
        m.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) {
        m.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modals on clicking overlay or pressing Esc
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeModal(overlay.id);
        }
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m.id));
    }
});

// Real-Time Table Filter
function filterTables() {
    const query = document.getElementById('kbSearchInput').value.toLowerCase().trim();
    let visibleKlinis = 0;
    let visibleLab = 0;

    document.querySelectorAll('#tableKlinis tbody tr.row-symptom').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (text.includes(query)) {
            row.style.display = '';
            visibleKlinis++;
        } else {
            row.style.display = 'none';
        }
    });

    document.querySelectorAll('#tableLab tbody tr.row-symptom').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (text.includes(query)) {
            row.style.display = '';
            visibleLab++;
        } else {
            row.style.display = 'none';
        }
    });

    const badgeK = document.getElementById('countKlinisBadge');
    const badgeL = document.getElementById('countLabBadge');
    if (badgeK) badgeK.textContent = visibleKlinis + ' Data';
    if (badgeL) badgeL.textContent = visibleLab + ' Data';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
