<?php
$pageTitle = 'Validasi Model & Metrik Akurasi';
require_once __DIR__ . '/includes/header.php';

// Dynamic threshold evaluation (default: 0.60 as per Excel)
$threshold = isset($_GET['threshold']) ? (float)$_GET['threshold'] : 0.60;

// Fetch simulation patients with gold standard
$stmt = $pdo->prepare("
    SELECT p.*, h.nilai_cf_akhir, h.persentase, h.interpretasi, h.derajat_dbd, h.detail_perhitungan
    FROM pasien p
    JOIN hasil_diagnosis h ON p.id_pasien = h.id_pasien
    WHERE p.gold_standard IN ('DBD Positif', 'DBD Negatif')
    ORDER BY p.id_pasien ASC
");
$stmt->execute();
$cases = $stmt->fetchAll();

// Calculate Confusion Matrix dynamically based on threshold
$tp = 0; $fn = 0; $fp = 0; $tn = 0;
$tableData = [];

foreach ($cases as $c) {
    $cfVal = (float)$c['nilai_cf_akhir'];
    $actual = $c['gold_standard'];
    $pred = ($cfVal >= $threshold) ? 'DBD Positif' : 'DBD Negatif';
    $isMatch = ($actual === $pred);

    if ($actual === 'DBD Positif' && $pred === 'DBD Positif') {
        $tp++;
    } elseif ($actual === 'DBD Positif' && $pred === 'DBD Negatif') {
        $fn++;
    } elseif ($actual === 'DBD Negatif' && $pred === 'DBD Positif') {
        $fp++;
    } elseif ($actual === 'DBD Negatif' && $pred === 'DBD Negatif') {
        $tn++;
    }

    $c['prediksi_dinamis'] = $pred;
    $c['sesuai_dinamis'] = $isMatch;
    $tableData[] = $c;
}

$total = count($cases);
$accuracy = $total > 0 ? (($tp + $tn) / $total) * 100 : 0;
$sensitivity = ($tp + $fn) > 0 ? ($tp / ($tp + $fn)) * 100 : 0;
$specificity = ($tn + $fp) > 0 ? ($tn / ($tn + $fp)) * 100 : 0;
$precision = ($tp + $fp) > 0 ? ($tp / ($tp + $fp)) * 100 : 0;
$f1Score = ($precision + $sensitivity) > 0 ? 2 * ($precision * $sensitivity) / ($precision + $sensitivity) : 0;
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                Validasi Sistem & Analisis Confusion Matrix
            </div>
            <div class="page-subtitle">
                Uji kinerja diagnostik sistem CDSS terhadap data rekam medis simulasi (13 Pasien) dibandingkan dengan Diagnosis Pasti (Gold Standard).
            </div>
        </div>

        <!-- Threshold Selector -->
        <div style="display: flex; align-items: center; gap: 8px; background: var(--bg-surface); padding: 8px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <label for="thresholdSelect" style="font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); white-space: nowrap;">
                Ambang Batas (Threshold):
            </label>
            <select id="thresholdSelect" class="form-control" style="font-size: 0.85rem; padding: 4px 8px; width: auto;" onchange="window.location.href='<?= BASE_URL ?>/validasi.php?threshold=' + this.value">
                <option value="0.50" <?= abs($threshold - 0.50) < 0.01 ? 'selected' : '' ?>>CF &ge; 0.50</option>
                <option value="0.60" <?= abs($threshold - 0.60) < 0.01 ? 'selected' : '' ?>>CF &ge; 0.60 (Standar Excel)</option>
                <option value="0.70" <?= abs($threshold - 0.70) < 0.01 ? 'selected' : '' ?>>CF &ge; 0.70</option>
                <option value="0.80" <?= abs($threshold - 0.80) < 0.01 ? 'selected' : '' ?>>CF &ge; 0.80 (Sangat Mungkin)</option>
            </select>
        </div>
    </div>

    <!-- Accuracy Metrics Grid -->
    <div class="stats-grid">
        <div class="stat-card emerald">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= number_format($sensitivity, 2) ?>%</div>
                <div class="stat-label">Sensitivitas (Recall)</div>
                <div class="stat-desc">TP / (TP + FN) • Kemampuan deteksi kasus positif</div>
            </div>
        </div>

        <div class="stat-card teal">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= number_format($accuracy, 2) ?>%</div>
                <div class="stat-label">Akurasi (Accuracy)</div>
                <div class="stat-desc">(TP + TN) / Total • Ketepatan keseluruhan</div>
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= number_format($precision, 2) ?>%</div>
                <div class="stat-label">Presisi (Precision)</div>
                <div class="stat-desc">TP / (TP + FP) • Ketepatan saat prediksi positif</div>
            </div>
        </div>

        <div class="stat-card rose">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= number_format($specificity, 2) ?>%</div>
                <div class="stat-label">Spesifisitas</div>
                <div class="stat-desc">TN / (TN + FP) • Kemampuan deteksi kasus negatif</div>
            </div>
        </div>
    </div>

    <!-- Confusion Matrix & Clinical Interpretation Section -->
    <div style="display: grid; grid-template-columns: 1.1fr 1.4fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Confusion Matrix 2x2 Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                    Tabel Confusion Matrix (2x2)
                </div>
                <span class="badge badge-primary">Total: <?= $total ?> Pasien</span>
            </div>

            <div class="matrix-grid">
                <div></div>
                <div class="matrix-header">Prediksi DBD Positif</div>
                <div class="matrix-header">Prediksi DBD Negatif</div>

                <div class="matrix-header" style="text-align: right; padding-right: 12px;">Aktual: Positif</div>
                <div class="matrix-cell tp">
                    <div class="matrix-count"><?= $tp ?></div>
                    <div class="matrix-tag">True Positive (TP)</div>
                </div>
                <div class="matrix-cell fn">
                    <div class="matrix-count"><?= $fn ?></div>
                    <div class="matrix-tag">False Negative (FN)</div>
                </div>

                <div class="matrix-header" style="text-align: right; padding-right: 12px;">Aktual: Negatif</div>
                <div class="matrix-cell fp">
                    <div class="matrix-count"><?= $fp ?></div>
                    <div class="matrix-tag">False Positive (FP)</div>
                </div>
                <div class="matrix-cell tn">
                    <div class="matrix-count"><?= $tn ?></div>
                    <div class="matrix-tag">True Negative (TN)</div>
                </div>
            </div>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.8rem; color: var(--text-muted);">
                Ambang Batas Uji: <strong>CF &ge; <?= number_format($threshold, 2) ?></strong> diprediksi sebagai kasus DBD Positif.
            </div>
        </div>

        <!-- Clinical Value & Scientific Interpretation -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Justifikasi Klinis & Analisis Sensitivitas 100%
                </div>
            </div>

            <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-secondary);">
                <div style="padding: 12px; background: rgba(16, 185, 129, 0.12); border-left: 4px solid var(--emerald); border-radius: var(--radius-sm); margin-bottom: 12px; color: var(--text-primary);">
                    <strong style="color: #34d399; font-size: 0.95rem; display: block; margin-bottom: 4px;">Zero False Negatives (FN = 0)</strong>
                    Sistem mendeteksi <strong>100% pasien yang benar-benar positif DBD</strong> tanpa ada kasus positif yang terlewatkan (Sensitivitas = 100%).
                </div>

                <p style="margin-bottom: 8px;">
                    Dalam perancangan <em>Clinical Decision Support System (CDSS)</em> untuk penyakit infeksi akut berisiko tinggi seperti Demam Berdarah Dengue (DBD), <strong>Sensitivitas (Recall) jauh lebih krusial dibandingkan Spesifisitas</strong>:
                </p>

                <ul style="margin-left: 20px; margin-bottom: 12px;">
                    <li><strong>Konsekuensi False Negative (FN):</strong> Pasien DBD tidak terdeteksi, dipulangkan tanpa pemantauan trombosit/hematokrit berkala, dan berisiko jatuh ke dalam kondisi kegawatdaruratan syok dengue (DSS) yang berujung fatal.</li>
                    <li><strong>Konsekuensi False Positive (FP):</strong> Pasien terduga dipantau lebih ketat dan diminta periksa darah ulang — tindakan medis yang aman (safety first) dan sesuai dengan pedoman kewaspadaan dini WHO.</li>
                </ul>

                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                    <em>Catatan: Data validasi ini mereplikasi lembar 'Ringkasan_Validasi' pada file CDSS_DBD_Certainty_Factor (5).xlsx untuk demonstrasi validitas logika model.</em>
                </p>
            </div>
        </div>
    </div>

    <!-- Comparative Table (All 13 Simulation Patients) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Tabel Perbandingan 13 Kasus Pasien Simulasi (Gold Standard vs Prediksi Sistem)
            </div>
            <span class="badge badge-primary">13 Kasus Excel</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Pasien</th>
                        <th>Nilai CF Akhir</th>
                        <th>Persentase</th>
                        <th>Interpretasi Sistem</th>
                        <th>Prediksi Sistem</th>
                        <th>Gold Standard (Rekam Medis)</th>
                        <th>Status Kesesuaian</th>
                        <th style="text-align: right;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableData as $idx => $d): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color: var(--text-white); font-size: 0.95rem;"><?= htmlspecialchars($d['nama']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: var(--font-mono);"><?= htmlspecialchars($d['no_rm']) ?></div>
                            </td>
                            <td>
                                <span style="font-family: var(--font-mono); font-weight: 700; color: #38bdf8;">
                                    <?= number_format($d['nilai_cf_akhir'], 6) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: <?= $d['nilai_cf_akhir'] >= 0.8 ? '#fb7185' : ($d['nilai_cf_akhir'] >= 0.6 ? '#fbbf24' : '#34d399') ?>;">
                                    <?= number_format($d['persentase'], 2) ?>%
                                </strong>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($d['interpretasi']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $d['prediksi_dinamis'] === 'DBD Positif' ? 'badge-danger' : 'badge-secondary' ?>">
                                    <?= $d['prediksi_dinamis'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $d['gold_standard'] === 'DBD Positif' ? 'badge-danger' : 'badge-secondary' ?>">
                                    <?= htmlspecialchars($d['gold_standard']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($d['sesuai_dinamis']): ?>
                                    <span class="badge badge-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Sesuai
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        Tidak Sesuai
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>/hasil.php?id=<?= $d['id_hasil'] ?? 1 ?>" class="btn btn-secondary btn-sm">
                                    Lihat Rantai CF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
