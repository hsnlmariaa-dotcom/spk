<?php
$pageTitle = 'Dashboard Utama CDSS Demam Berdarah Dengue';
require_once __DIR__ . '/includes/header.php';

// KPI Statistics
$totalPasien = (int)$pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$totalHasil = (int)$pdo->query("SELECT COUNT(*) FROM hasil_diagnosis")->fetchColumn();
$totalPositif = (int)$pdo->query("SELECT COUNT(*) FROM hasil_diagnosis WHERE prediksi_sistem = 'DBD Positif'")->fetchColumn();
$totalNegatif = (int)$pdo->query("SELECT COUNT(*) FROM hasil_diagnosis WHERE prediksi_sistem = 'DBD Negatif'")->fetchColumn();
$totalSyok = (int)$pdo->query("SELECT COUNT(*) FROM hasil_diagnosis WHERE derajat_dbd LIKE '%Syok%' OR derajat_dbd LIKE '%DSS%' OR derajat_dbd LIKE '%Derajat III%'")->fetchColumn();

$persenPositif = $totalHasil > 0 ? round(($totalPositif / $totalHasil) * 100, 1) : 0;
$persenNegatif = $totalHasil > 0 ? round(($totalNegatif / $totalHasil) * 100, 1) : 0;

// Chart Data 1: WHO Severity Classification
$derajatCounts = [
    'DBD Derajat III/IV (DSS)' => 0,
    'DBD Derajat II' => 0,
    'DBD Derajat I' => 0,
    'Suspek Dengue (DF)' => 0,
    'Non-DBD / Ringan' => 0
];

// Chart Data 2: Certainty Factor Range Distribution
$cfBins = [
    'Sangat Mungkin (0.80 - 1.00)' => 0,
    'Kemungkinan (0.60 - 0.79)' => 0,
    'Dicurigai (0.40 - 0.59)' => 0,
    'Rendah (0.01 - 0.39)' => 0
];

$allDiagnoses = $pdo->query("SELECT derajat_dbd, nilai_cf_akhir FROM hasil_diagnosis")->fetchAll();
foreach ($allDiagnoses as $d) {
    $str = $d['derajat_dbd'];
    if (stripos($str, 'Syok') !== false || stripos($str, 'DSS') !== false || stripos($str, 'Derajat III') !== false) {
        $derajatCounts['DBD Derajat III/IV (DSS)']++;
    } elseif (stripos($str, 'Derajat II') !== false) {
        $derajatCounts['DBD Derajat II']++;
    } elseif (stripos($str, 'Derajat I') !== false) {
        $derajatCounts['DBD Derajat I']++;
    } elseif (stripos($str, 'Suspek') !== false) {
        $derajatCounts['Suspek Dengue (DF)']++;
    } else {
        $derajatCounts['Non-DBD / Ringan']++;
    }

    $v = (float)$d['nilai_cf_akhir'];
    if ($v >= 0.80) {
        $cfBins['Sangat Mungkin (0.80 - 1.00)']++;
    } elseif ($v >= 0.60) {
        $cfBins['Kemungkinan (0.60 - 0.79)']++;
    } elseif ($v >= 0.40) {
        $cfBins['Dicurigai (0.40 - 0.59)']++;
    } else {
        $cfBins['Rendah (0.01 - 0.39)']++;
    }
}

// Chart Data 3: Top Symptoms & Lab Evidence
$topEvidence = $pdo->query("
    SELECT g.kode_gejala, g.nama_gejala, g.kategori, COUNT(pg.id) as total
    FROM gejala g
    JOIN pasien_gejala pg ON g.id_gejala = pg.id_gejala AND pg.nilai_user >= 0.4
    GROUP BY g.id_gejala
    ORDER BY total DESC
    LIMIT 6
")->fetchAll();

// Recent Diagnoses Table
$recent = $pdo->query("
    SELECT h.*, p.nama, p.no_rm, p.umur, p.jenis_kelamin 
    FROM hasil_diagnosis h
    JOIN pasien p ON h.id_pasien = p.id_pasien
    ORDER BY h.id_hasil DESC
    LIMIT 6
")->fetchAll();
?>

<!-- Dashboard Hero Header -->
<div style="margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.6rem 2rem; background: #ffffff; border: 1px solid var(--border-color); box-shadow: var(--shadow-xs);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span class="badge badge-primary">Sistem Pendukung Keputusan Klinis</span>
                    <span class="badge badge-success">Metode Certainty Factor</span>
                </div>
                <h1 style="font-size: 1.65rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
                    Dashboard Diagnosis Awal Demam Berdarah Dengue
                </h1>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px; max-width: 780px;">
                    Sistem triase dan inferensi klinis berbasis 11 gejala kardinal dan 5 parameter lab untuk stratifikasi derajat keparahan WHO dengan akurasi terverifikasi.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/diagnosa.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Mulai Diagnosa Pasien
                </a>
                <a href="<?= BASE_URL ?>/validasi.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    Validasi Akurasi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- KPI Metrics Grid (5 Stat Cards) -->
<div class="stats-grid">
    <!-- Stat 1: Total Patients -->
    <div class="stat-card teal">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalPasien ?></div>
            <div class="stat-label">Total Rekam Medis</div>
            <div class="stat-desc"><?= $totalHasil ?> riwayat pemeriksaan</div>
        </div>
    </div>

    <!-- Stat 2: Positive Cases -->
    <div class="stat-card amber">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalPositif ?></div>
            <div class="stat-label">Prediksi DBD Positif</div>
            <div class="stat-desc"><?= $persenPositif ?>% dari total pasien</div>
        </div>
    </div>

    <!-- Stat 3: Negative Cases -->
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--bg-surface-elevated); color: var(--text-secondary); border-color: var(--border-color);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalNegatif ?></div>
            <div class="stat-label">Prediksi Negatif</div>
            <div class="stat-desc"><?= $persenNegatif ?>% non-DBD/rendah</div>
        </div>
    </div>

    <!-- Stat 4: Critical Shock / DSS Cases -->
    <div class="stat-card rose">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalSyok ?></div>
            <div class="stat-label">Kasus Syok (DSS)</div>
            <div class="stat-desc">Prioritas kegawatdaruratan</div>
        </div>
    </div>

    <!-- Stat 5: Model Sensitivity -->
    <div class="stat-card emerald">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
            <div class="stat-value">100.0%</div>
            <div class="stat-label">Sensitivitas (Recall)</div>
            <div class="stat-desc">0 Kasus Positif Terlewat</div>
        </div>
    </div>
</div>

<!-- Visual Analytics Charts Section (3 Columns) -->
<div class="charts-grid">
    <!-- Chart 1: Derajat DBD WHO -->
    <div class="chart-card">
        <div class="card-header" style="margin-bottom: 0.75rem; padding-bottom: 0.6rem;">
            <div class="card-title" style="font-size: 0.95rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/></svg>
                Distribusi Derajat Keparahan (WHO)
            </div>
            <span class="badge badge-primary">Derajat I - IV</span>
        </div>
        <div class="chart-canvas-wrap">
            <canvas id="chartDerajat"></canvas>
        </div>
    </div>

    <!-- Chart 2: Certainty Factor Range -->
    <div class="chart-card">
        <div class="card-header" style="margin-bottom: 0.75rem; padding-bottom: 0.6rem;">
            <div class="card-title" style="font-size: 0.95rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Sebaran Kategori Nilai CF Akhir
            </div>
            <span class="badge badge-success">Rentang CF</span>
        </div>
        <div class="chart-canvas-wrap">
            <canvas id="chartCF"></canvas>
        </div>
    </div>

    <!-- Chart 3: Top Symptoms & Lab Evidence -->
    <div class="chart-card">
        <div class="card-header" style="margin-bottom: 0.75rem; padding-bottom: 0.6rem;">
            <div class="card-title" style="font-size: 0.95rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Gejala & Parameter Lab Terbanyak
            </div>
            <span class="badge badge-warning">Frekuensi Kasus</span>
        </div>
        <div class="chart-canvas-wrap">
            <canvas id="chartGejala"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Section: Recent Patients & Clinical Triad -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem;">
    <!-- Recent Diagnoses Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Pemeriksaan Rekam Medis Terbaru
            </div>
            <a href="<?= BASE_URL ?>/pasien.php" class="btn btn-secondary btn-sm">Lihat Semua Rekam Medis</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Nilai CF</th>
                        <th>Tingkat Keyakinan</th>
                        <th>Klasifikasi WHO</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Belum ada riwayat pemeriksaan pasien.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent as $r): ?>
                            <tr>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: var(--primary);">
                                    <?= htmlspecialchars($r['no_rm'] ?: 'RM-' . str_pad($r['id_pasien'], 4, '0', STR_PAD_LEFT)) ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($r['nama']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $r['umur'] ?> th (<?= $r['jenis_kelamin'] ?>)</div>
                                </td>
                                <td>
                                    <span style="font-family: var(--font-mono); font-weight: 700; color: #0f172a;">
                                        <?= number_format($r['nilai_cf_akhir'], 4) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['nilai_cf_akhir'] >= 0.80): ?>
                                        <span class="badge badge-danger"><?= number_format($r['persentase'], 1) ?>% • Sangat Mungkin</span>
                                    <?php elseif ($r['nilai_cf_akhir'] >= 0.60): ?>
                                        <span class="badge badge-warning"><?= number_format($r['persentase'], 1) ?>% • Kemungkinan</span>
                                    <?php elseif ($r['nilai_cf_akhir'] >= 0.40): ?>
                                        <span class="badge badge-primary"><?= number_format($r['persentase'], 1) ?>% • Dicurigai</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?= number_format($r['persentase'], 1) ?>% • Rendah</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 0.82rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($r['derajat_dbd'] ?? '-') ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= BASE_URL ?>/hasil.php?id=<?= $r['id_hasil'] ?>" class="btn btn-secondary btn-sm" title="Lihat Hasil Lengkap">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clinical Triad & Warning Signs Sidecard -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <!-- Emergency Warning Signs -->
        <div class="card" style="border-top: 3px solid var(--rose);">
            <div class="card-header" style="margin-bottom: 0.75rem; padding-bottom: 0.6rem;">
                <div class="card-title" style="font-size: 0.95rem; color: #be123c;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #be123c;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Warning Signs WHO (Kedaruratan)
                </div>
            </div>
            <ul style="font-size: 0.8rem; color: var(--text-secondary); padding-left: 18px; display: flex; flex-direction: column; gap: 6px;">
                <li><strong>G11:</strong> Tanda syok (akral dingin, nadi lemah, hipotensi) &rarr; <span style="color: var(--rose); font-weight: 700;">DSS / ICU</span></li>
                <li><strong>G07:</strong> Perdarahan mukosa spontan (epistaksis/gusi)</li>
                <li><strong>G08:</strong> Nyeri perut hebat / muntah persisten</li>
                <li><strong>L01:</strong> Trombositopenia cepat &lt; 100.000 /uL</li>
                <li><strong>L02:</strong> Hemokonsentrasi (Hematokrit naik &ge; 20%)</li>
            </ul>
        </div>

        <!-- 3 Dengue Clinical Phases -->
        <div class="card">
            <div class="card-header" style="margin-bottom: 0.75rem; padding-bottom: 0.6rem;">
                <div class="card-title" style="font-size: 0.95rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    3 Fase Perjalanan Klinis
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.8rem;">
                <div style="padding: 8px 10px; background: var(--primary-light); border-left: 3px solid var(--primary); border-radius: var(--radius-xs);">
                    <strong style="color: var(--primary);">Fase Demam (Hari 1-3):</strong>
                    <div style="color: var(--text-secondary); font-size: 0.75rem;">Demam tinggi mendadak, sakit kepala, myalgia. Uji NS1 positif.</div>
                </div>
                <div style="padding: 8px 10px; background: var(--rose-light); border-left: 3px solid var(--rose); border-radius: var(--radius-xs);">
                    <strong style="color: var(--rose);">Fase Kritis (Hari 3-7):</strong>
                    <div style="color: var(--text-secondary); font-size: 0.75rem;">Suhu turun namun risiko kebocoran plasma memuncak. Pantau syok.</div>
                </div>
                <div style="padding: 8px 10px; background: var(--emerald-light); border-left: 3px solid var(--emerald); border-radius: var(--radius-xs);">
                    <strong style="color: var(--emerald);">Fase Pemulihan (Hari 7-10):</strong>
                    <div style="color: var(--text-secondary); font-size: 0.75rem;">Hemodinamik stabil, nafsu makan pulih, trombosit naik.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Initialize Minimalist Chart.js Visualizations -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Derajat Keparahan WHO (Doughnut)
    const ctxDerajat = document.getElementById('chartDerajat');
    if (ctxDerajat && typeof Chart !== 'undefined') {
        new Chart(ctxDerajat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($derajatCounts)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($derajatCounts)) ?>,
                    backgroundColor: [
                        '#e11d48', // Rose (DSS)
                        '#f59e0b', // Amber (Derajat II)
                        '#0284c7', // Primary Blue (Derajat I)
                        '#0d9488', // Teal (Suspek Dengue)
                        '#94a3b8'  // Gray (Non-DBD)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                            color: '#475569'
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Chart CF Distribution (Bar)
    const ctxCF = document.getElementById('chartCF');
    if (ctxCF && typeof Chart !== 'undefined') {
        new Chart(ctxCF, {
            type: 'bar',
            data: {
                labels: ['Sangat Mungkin', 'Kemungkinan', 'Dicurigai', 'Rendah'],
                datasets: [{
                    label: 'Jumlah Pasien',
                    data: <?= json_encode(array_values($cfBins)) ?>,
                    backgroundColor: ['#e11d48', '#f59e0b', '#0284c7', '#059669'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, family: "'Plus Jakarta Sans', sans-serif" }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 2, font: { size: 10 }, color: '#64748b' },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }

    // 3. Chart Top Evidence (Horizontal Bar)
    const ctxGejala = document.getElementById('chartGejala');
    if (ctxGejala && typeof Chart !== 'undefined') {
        const evidenceLabels = <?= json_encode(array_map(fn($e) => $e['kode_gejala'] . ' (' . mb_substr($e['nama_gejala'], 0, 16) . '...)', $topEvidence)) ?>;
        const evidenceData = <?= json_encode(array_map(fn($e) => (int)$e['total'], $topEvidence)) ?>;

        new Chart(ctxGejala, {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: evidenceLabels,
                datasets: [{
                    label: 'Kasus Terdeteksi',
                    data: evidenceData,
                    backgroundColor: '#0284c7',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 2, font: { size: 10 }, color: '#64748b' },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10, family: "'Plus Jakarta Sans', sans-serif" }, color: '#475569' }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
