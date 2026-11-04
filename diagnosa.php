<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cf_engine.php';
$pdo = getDB();

// Fetch all symptoms and lab parameters
$gejalaList = $pdo->query("
    SELECT g.*, a.nilai_cf as cf_pakar 
    FROM gejala g
    LEFT JOIN aturan a ON g.id_gejala = a.id_gejala AND a.id_penyakit = 1
    ORDER BY g.id_gejala ASC
")->fetchAll();

$gejalaKlinis = array_filter($gejalaList, fn($g) => $g['kategori'] === 'klinis');
$parameterLab = array_filter($gejalaList, fn($g) => $g['kategori'] === 'lab');

// Fetch registered patients for quick selection
$pasienList = $pdo->query("SELECT id_pasien, no_rm, nama, umur, jenis_kelamin FROM pasien ORDER BY id_pasien DESC LIMIT 50")->fetchAll();

// Handle form submission before outputting HTML
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $noRm = trim($_POST['no_rm'] ?? '');
    $umur = !empty($_POST['umur']) ? (int)$_POST['umur'] : null;
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $alamat = trim($_POST['alamat'] ?? '');
    $existingPasienId = !empty($_POST['existing_pasien_id']) ? (int)$_POST['existing_pasien_id'] : null;
    $gejalaInput = $_POST['gejala'] ?? [];

    if (empty($nama) && empty($existingPasienId)) {
        $errorMessage = 'Mohon isi nama pasien atau pilih pasien yang sudah terdaftar.';
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($existingPasienId)) {
                $pasienId = $existingPasienId;
                if (!empty($nama)) {
                    $stmtUpdate = $pdo->prepare("UPDATE pasien SET nama = ?, umur = ?, jenis_kelamin = ?, alamat = ? WHERE id_pasien = ?");
                    $stmtUpdate->execute([$nama, $umur, $jk, $alamat, $pasienId]);
                }
            } else {
                if (empty($noRm)) {
                    $maxId = (int)$pdo->query("SELECT MAX(id_pasien) FROM pasien")->fetchColumn();
                    $noRm = 'RM-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
                }
                $stmtInsertP = $pdo->prepare("INSERT INTO pasien (no_rm, nama, umur, jenis_kelamin, alamat, tanggal_periksa) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmtInsertP->execute([$noRm, $nama, $umur, $jk, $alamat]);
                $pasienId = $pdo->lastInsertId();
            }

            // Build expert CF map and metadata
            $expertMap = [];
            $metaMap = [];
            foreach ($gejalaList as $g) {
                $expertMap[$g['kode_gejala']] = (float)$g['cf_pakar'];
                $metaMap[$g['kode_gejala']] = [
                    'nama_gejala' => $g['nama_gejala'],
                    'kategori' => $g['kategori']
                ];
            }

            // Run CF Inference Engine
            $res = CFEngine::diagnose($gejalaInput, $expertMap, $metaMap);

            // Save patient symptoms
            $stmtDelPG = $pdo->prepare("DELETE FROM pasien_gejala WHERE id_pasien = ?");
            $stmtDelPG->execute([$pasienId]);

            $stmtPG = $pdo->prepare("INSERT INTO pasien_gejala (id_pasien, id_gejala, nilai_user) VALUES (?, ?, ?)");
            $gIdLookup = [];
            foreach ($gejalaList as $g) {
                $gIdLookup[$g['kode_gejala']] = $g['id_gejala'];
            }

            foreach ($gejalaInput as $code => $val) {
                $userCF = CFEngine::parseUserCF($val);
                if (isset($gIdLookup[$code])) {
                    $stmtPG->execute([$pasienId, $gIdLookup[$code], $userCF]);
                }
            }

            // Save Hasil Diagnosis
            $stmtH = $pdo->prepare("
                INSERT INTO hasil_diagnosis 
                (id_pasien, nilai_cf_akhir, persentase, interpretasi, derajat_dbd, rekomendasi, rules_terpenuhi, prediksi_sistem, detail_perhitungan, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtH->execute([
                $pasienId,
                $res['final_cf'],
                $res['persentase'],
                $res['interpretasi'],
                $res['derajat_dbd'],
                $res['rekomendasi'],
                implode('; ', $res['rules_terpenuhi']),
                $res['prediksi'],
                json_encode($res['steps'])
            ]);

            $hasilId = $pdo->lastInsertId();
            $pdo->commit();

            header("Location: " . BASE_URL . "/hasil.php?id=" . $hasilId);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = 'Gagal menyimpan diagnosis: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Form Diagnosa CDSS Demam Berdarah Dengue';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                Formulir Pemeriksaan & Kalkulator CDSS DBD
            </div>
            <div class="page-subtitle">
                Pilih tingkat keyakinan klinis untuk setiap gejala dan parameter lab. Sistem menghitung Certainty Factor secara real-time.
            </div>
        </div>

        <!-- Quick Scenario Presets -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('syok')" title="Simulasi Pasien Dengue Shock Syndrome (DSS)">
                Preset: DBD Syok (DSS)
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('derajat2')" title="Simulasi Pasien Derajat II Perdarahan Spontan">
                Preset: DBD Derajat II
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('klasik')" title="Simulasi Suspek Dengue Gejala Klasik">
                Preset: Suspek Klasik
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('negatif')" title="Reset ke Nilai Kosong/Negatif">
                Reset / Negatif
            </button>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert-banner danger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><?= htmlspecialchars($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <!-- Form & Live Inference Grid -->
    <form method="POST" id="diagnosisForm">
        <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem; align-items: start;">
            <!-- Left Column: Patient Data & Symptoms Form -->
            <div>
                <!-- Card 1: Data Pasien -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <div class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Identitas Pasien
                        </div>
                        <div>
                            <select id="selectExistingPasien" class="form-control" style="font-size: 0.8rem; padding: 6px 10px;" onchange="loadExistingPatient(this)">
                                <option value="">-- Atau Pilih Pasien Terdaftar --</option>
                                <?php foreach ($pasienList as $p): ?>
                                    <option value="<?= $p['id_pasien'] ?>" 
                                            data-nama="<?= htmlspecialchars($p['nama']) ?>" 
                                            data-rm="<?= htmlspecialchars($p['no_rm']) ?>" 
                                            data-umur="<?= $p['umur'] ?>" 
                                            data-jk="<?= $p['jenis_kelamin'] ?>">
                                        <?= htmlspecialchars($p['no_rm']) ?> - <?= htmlspecialchars($p['nama']) ?> (<?= $p['umur'] ?> th)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="existing_pasien_id" id="existing_pasien_id" value="">

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">No. Rekam Medis (RM)</label>
                            <input type="text" name="no_rm" id="input_no_rm" class="form-control" placeholder="Contoh: RM-0014" value="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap Pasien *</label>
                            <input type="text" name="nama" id="input_nama" class="form-control" placeholder="Nama pasien" required value="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Umur (Tahun)</label>
                            <input type="number" name="umur" id="input_umur" class="form-control" min="0" max="120" placeholder="Contoh: 24" value="">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="input_jk" class="form-control">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat / Domisili</label>
                            <input type="text" name="alamat" id="input_alamat" class="form-control" placeholder="Alamat tinggal pasien" value="">
                        </div>
                    </div>
                </div>

                <!-- Card 2: Gejala Klinis (G01 - G11) -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <div class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            Bagian A: Gejala Klinis Anamnesis (G01 - G11)
                        </div>
                        <span class="badge badge-primary">11 Gejala Klinis</span>
                    </div>

                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                        Evaluasi manifestasi klinis yang dialami pasien. Pilih tingkat keyakinan klinis dari <em>Tidak</em> hingga <em>Sangat Yakin</em>.
                    </p>

                    <?php foreach ($gejalaKlinis as $g): ?>
                        <?php 
                            $isShock = ($g['kode_gejala'] === 'G11');
                            $isBleed = ($g['kode_gejala'] === 'G07');
                            $isWarning = ($g['kode_gejala'] === 'G08');
                        ?>
                        <div class="diagnostic-item <?= ($isShock || $isBleed || $isWarning) ? 'critical-border' : '' ?>">
                            <div class="diagnostic-header">
                                <div>
                                    <div class="item-badge-wrap">
                                        <span class="item-code"><?= $g['kode_gejala'] ?></span>
                                        <span class="item-name"><?= htmlspecialchars($g['nama_gejala']) ?></span>
                                        <?php if ($isShock): ?>
                                            <span class="badge badge-danger">TANDA SYOK / DSS</span>
                                        <?php elseif ($isBleed): ?>
                                            <span class="badge badge-danger">PERDARAHAN</span>
                                        <?php elseif ($isWarning): ?>
                                            <span class="badge badge-warning">WARNING SIGN WHO</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($g['keterangan'])): ?>
                                        <div class="item-desc"><?= htmlspecialchars($g['keterangan']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-cf-expert">
                                    CF Pakar: <strong><?= number_format($g['cf_pakar'], 2) ?></strong>
                                </div>
                            </div>

                            <div class="rating-group">
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_0" value="0.0" checked>
                                    <label for="<?= $g['kode_gejala'] ?>_0">
                                        Tidak
                                        <span class="cf-val">0.0</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_02" value="0.2">
                                    <label for="<?= $g['kode_gejala'] ?>_02">
                                        Tidak Tahu
                                        <span class="cf-val">0.2</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_04" value="0.4">
                                    <label for="<?= $g['kode_gejala'] ?>_04">
                                        Sedikit Yakin
                                        <span class="cf-val">0.4</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_06" value="0.6">
                                    <label for="<?= $g['kode_gejala'] ?>_06">
                                        Cukup Yakin
                                        <span class="cf-val">0.6</span>
                                    </label>
                                </div>
                                <div class="rating-chip <?= ($isShock || $isBleed) ? 'critical' : '' ?>">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_08" value="0.8">
                                    <label for="<?= $g['kode_gejala'] ?>_08">
                                        Yakin
                                        <span class="cf-val">0.8</span>
                                    </label>
                                </div>
                                <div class="rating-chip <?= ($isShock || $isBleed) ? 'critical' : '' ?>">
                                    <input type="radio" name="gejala[<?= $g['kode_gejala'] ?>]" id="<?= $g['kode_gejala'] ?>_10" value="1.0">
                                    <label for="<?= $g['kode_gejala'] ?>_10">
                                        Sangat Yakin
                                        <span class="cf-val">1.0</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Card 3: Parameter Laboratorium (L01 - L05) -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.31"/><path d="M14 9.3V1.99"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/><path d="M5.52 16h12.96"/></svg>
                            Bagian B: Parameter Laboratorium (L01 - L05)
                        </div>
                        <span class="badge badge-success">5 Parameter Kritis</span>
                    </div>

                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                        Hasil pemeriksaan darah rutin dan uji serologis spesifik. Bila belum diperiksa, biarkan pada opsi <em>Tidak</em> (0.0).
                    </p>

                    <?php foreach ($parameterLab as $l): ?>
                        <div class="diagnostic-item">
                            <div class="diagnostic-header">
                                <div>
                                    <div class="item-badge-wrap">
                                        <span class="item-code lab"><?= $l['kode_gejala'] ?></span>
                                        <span class="item-name"><?= htmlspecialchars($l['nama_gejala']) ?></span>
                                        <?php if (!empty($l['kriteria'])): ?>
                                            <span class="badge badge-warning"><?= htmlspecialchars($l['kriteria']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($l['keterangan'])): ?>
                                        <div class="item-desc"><?= htmlspecialchars($l['keterangan']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-cf-expert">
                                    CF Pakar: <strong><?= number_format($l['cf_pakar'], 2) ?></strong>
                                </div>
                            </div>

                            <div class="rating-group">
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_0" value="0.0" checked>
                                    <label for="<?= $l['kode_gejala'] ?>_0">
                                        Tidak / Belum
                                        <span class="cf-val">0.0</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_02" value="0.2">
                                    <label for="<?= $l['kode_gejala'] ?>_02">
                                        Ragu / Batas
                                        <span class="cf-val">0.2</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_04" value="0.4">
                                    <label for="<?= $l['kode_gejala'] ?>_04">
                                        Sedikit Yakin
                                        <span class="cf-val">0.4</span>
                                    </label>
                                </div>
                                <div class="rating-chip">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_06" value="0.6">
                                    <label for="<?= $l['kode_gejala'] ?>_06">
                                        Cukup Yakin
                                        <span class="cf-val">0.6</span>
                                    </label>
                                </div>
                                <div class="rating-chip critical">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_08" value="0.8">
                                    <label for="<?= $l['kode_gejala'] ?>_08">
                                        Yakin Positif
                                        <span class="cf-val">0.8</span>
                                    </label>
                                </div>
                                <div class="rating-chip critical">
                                    <input type="radio" name="gejala[<?= $l['kode_gejala'] ?>]" id="<?= $l['kode_gejala'] ?>_10" value="1.0">
                                    <label for="<?= $l['kode_gejala'] ?>_10">
                                        Pasti Positif
                                        <span class="cf-val">1.0</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Sticky Live Inference Widget -->
            <div class="sticky-panel">
                <!-- Gauge Card -->
                <div class="meter-box">
                    <span class="badge badge-primary" style="margin-bottom: 12px;">Live Inference Engine</span>
                    
                    <div style="position: relative; width: 170px; height: 170px; margin-bottom: 14px;">
                        <svg class="gauge-svg" viewBox="0 0 160 160">
                            <circle class="gauge-bg" cx="80" cy="80" r="70"></circle>
                            <circle class="gauge-progress" id="gaugeProgress" cx="80" cy="80" r="70"></circle>
                        </svg>
                        <div class="gauge-text-wrap">
                            <div class="gauge-percent" id="gaugePercent">0.0%</div>
                            <div class="gauge-cf-raw" id="gaugeRaw">Nilai CF: 0.0000</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <span id="interpretationBadge" class="badge badge-secondary" style="font-size: 0.88rem; padding: 6px 14px;">
                            Belum Ada Gejala Dinilai
                        </span>
                    </div>

                    <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4; margin-bottom: 16px; min-height: 40px;" id="quickActionText">
                        Pilih gejala klinis pada form untuk memulai perhitungan Certainty Factor secara langsung.
                    </div>

                    <!-- WHO Severity Live Badge -->
                    <div style="width: 100%; padding: 12px; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 16px; text-align: left;">
                        <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); font-weight: 700; margin-bottom: 4px;">
                            Klasifikasi WHO:
                        </div>
                        <div id="whoResultBadge" class="badge badge-secondary" style="font-size: 0.78rem;">
                            Non-DBD / Belum Ada Bukti
                        </div>
                    </div>

                    <!-- Emergency Warning Banner -->
                    <div class="alert-banner danger" id="liveWarningBanner" style="display: none; width: 100%; text-align: left; margin-bottom: 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div>
                            <strong style="color: var(--rose); display: block; font-size: 0.82rem;">WARNING SIGNS TERDETEKSI:</strong>
                            <ul id="warningList" style="margin: 4px 0 0 14px; font-size: 0.78rem; padding: 0;"></ul>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Simpan & Cetak Hasil Diagnosa
                    </button>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                        Data tersimpan ke Rekam Medis & Database CDSS
                    </div>
                </div>

                <!-- Live Mathematical Trace Accordion -->
                <div class="card" style="margin-top: 1rem; padding: 1.1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <strong style="font-size: 0.85rem; color: var(--text-white);">Bukti Gejala Aktif</strong>
                        <span class="badge badge-primary" id="activeEvidenceCount">0</span>
                    </div>

                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table" style="font-size: 0.78rem;">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>User</th>
                                    <th>Pakar</th>
                                    <th>Item</th>
                                    <th>Lama</th>
                                    <th>Kombinasi</th>
                                </tr>
                            </thead>
                            <tbody id="liveStepsBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1rem;">
                                        Belum ada gejala yang dipilih.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Preset Scenarios for demonstration & testing
function applyPreset(type) {
    const form = document.getElementById('diagnosisForm');
    if (!form) return;

    // Reset all to 0.0 first
    form.querySelectorAll('input[type="radio"][value="0.0"]').forEach(r => r.checked = true);

    const check = (code, val) => {
        const target = form.querySelector(`input[name="gejala[${code}]"][value="${val}"]`);
        if (target) target.checked = true;
    };

    if (type === 'syok') {
        document.getElementById('input_nama').value = 'Pasien Simulasi (Syok / DSS)';
        document.getElementById('input_umur').value = 19;
        document.getElementById('input_jk').value = 'P';
        check('G01', '1.0'); // Demam tinggi
        check('G05', '0.8'); // Ruam petekie
        check('G07', '1.0'); // Perdarahan gusi
        check('G08', '0.8'); // Nyeri abdomen
        check('G09', '0.8'); // Tourniquet +
        check('G11', '1.0'); // Tanda syok (DSS)
        check('L01', '1.0'); // Trombosit < 100k
        check('L02', '1.0'); // Hematokrit >= 20%
        check('L04', '1.0'); // NS1 positif
    } else if (type === 'derajat2') {
        document.getElementById('input_nama').value = 'Pasien Simulasi (Derajat II)';
        document.getElementById('input_umur').value = 32;
        document.getElementById('input_jk').value = 'L';
        check('G01', '0.8'); // Demam tinggi
        check('G02', '0.6'); // Nyeri kepala
        check('G04', '0.8'); // Nyeri sendi
        check('G05', '0.8'); // Ruam
        check('G07', '0.8'); // Perdarahan gusi/hidung
        check('L01', '1.0'); // Trombositopenia
        check('L02', '0.8'); // Hemokonsentrasi
        check('L04', '0.8'); // NS1
    } else if (type === 'klasik') {
        document.getElementById('input_nama').value = 'Pasien Simulasi (Suspek Dengue)';
        document.getElementById('input_umur').value = 26;
        document.getElementById('input_jk').value = 'L';
        check('G01', '0.8'); // Demam
        check('G02', '0.8'); // Nyeri kepala hebat
        check('G03', '0.6'); // Retro-orbital
        check('G04', '0.8'); // Myalgia/artralgia
        check('G06', '0.6'); // Mual
    } else if (type === 'negatif') {
        document.getElementById('input_nama').value = '';
        document.getElementById('input_no_rm').value = '';
        document.getElementById('input_umur').value = '';
        document.getElementById('existing_pasien_id').value = '';
    }

    runLiveInference();
}

function loadExistingPatient(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    document.getElementById('existing_pasien_id').value = opt.value;
    document.getElementById('input_nama').value = opt.getAttribute('data-nama') || '';
    document.getElementById('input_no_rm').value = opt.getAttribute('data-rm') || '';
    document.getElementById('input_umur').value = opt.getAttribute('data-umur') || '';
    document.getElementById('input_jk').value = opt.getAttribute('data-jk') || 'L';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
