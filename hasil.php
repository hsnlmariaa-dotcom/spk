<?php
$pageTitle = 'Hasil Diagnosis CDSS DBD';
require_once __DIR__ . '/includes/header.php';

$hasilId = !empty($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hasilId) {
    header("Location: " . BASE_URL . "/pasien.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT h.*, p.nama, p.no_rm, p.umur, p.jenis_kelamin, p.alamat, p.gold_standard, p.tanggal_periksa as tgl_daftar
    FROM hasil_diagnosis h
    JOIN pasien p ON h.id_pasien = p.id_pasien
    WHERE h.id_hasil = ?
");
$stmt->execute([$hasilId]);
$hasil = $stmt->fetch();

if (!$hasil) {
    echo "<div class='container' style='padding: 3rem 0;'><div class='alert-banner danger'>Data hasil diagnosis tidak ditemukan.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// Decode detail calculations
$steps = json_decode($hasil['detail_perhitungan'] ?? '[]', true) ?: [];

// Fetch symptom names for detailed display
$gejalaLookup = [];
$gRows = $pdo->query("SELECT kode_gejala, nama_gejala, kategori, kriteria FROM gejala")->fetchAll();
foreach ($gRows as $gr) {
    $gejalaLookup[$gr['kode_gejala']] = $gr;
}

// Determine visual colors
$cf = (float)$hasil['nilai_cf_akhir'];
$persen = (float)$hasil['persentase'];

if ($cf >= 0.80) {
    $themeColor = '#e11d48'; // Rose / Red
    $themeBadge = 'badge-danger';
    $themeBg = 'var(--rose-light)';
    $themeBorder = 'var(--rose-border)';
} elseif ($cf >= 0.60) {
    $themeColor = '#d97706'; // Amber / Orange
    $themeBadge = 'badge-warning';
    $themeBg = 'var(--amber-light)';
    $themeBorder = 'var(--amber-border)';
} elseif ($cf >= 0.40) {
    $themeColor = '#0284c7'; // Blue / Primary
    $themeBadge = 'badge-primary';
    $themeBg = 'var(--primary-light)';
    $themeBorder = 'var(--primary-border)';
} else {
    $themeColor = '#059669'; // Green / Emerald
    $themeBadge = 'badge-success';
    $themeBg = 'var(--emerald-light)';
    $themeBorder = 'var(--emerald-border)';
}
?>

<div class="container" style="padding-top: 2rem;">
    <!-- Print Only Letterhead / Kop Surat Rumah Sakit -->
    <div class="print-only" style="margin-bottom: 2rem; border-bottom: 3px double #000; padding-bottom: 12px; text-align: center;">
        <h2 style="font-size: 16pt; font-weight: bold; margin: 0; text-transform: uppercase;">
            INSTALASI REKAM MEDIS & LABORATORIUM KLINIS
        </h2>
        <h3 style="font-size: 13pt; margin: 4px 0;">
            SISTEM PENDUKUNG KEPUTUSAN KLINIS DEMAM BERDARAH DENGUE (CDSS-DBD)
        </h3>
        <p style="font-size: 9pt; margin: 0; color: #444;">
            Metode Inferensi Certainty Factor (Shortliffe & Buchanan) • Kriteria Diagnostik WHO • Standar Kemenkes RI
        </p>
    </div>

    <!-- Action Bar (No Print) -->
    <div class="no-print" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 12px;">
        <div>
            <a href="<?= BASE_URL ?>/pasien.php" class="btn btn-secondary btn-sm" style="margin-bottom: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Rekam Medis
            </a>
            <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--text-white); margin-top: 4px;">
                Hasil Pemeriksaan & Diagnosis Klinis
            </h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak Laporan Medis (Print)
            </button>
            <a href="<?= BASE_URL ?>/diagnosa.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Diagnosa Pasien Baru
            </a>
        </div>
    </div>

    <!-- Patient Header Summary Card -->
    <div class="card" style="margin-bottom: 1.5rem; background: var(--bg-surface);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;">
            <div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">No. Rekam Medis</div>
                <div style="font-size: 1.25rem; font-weight: 800; color: #38bdf8; font-family: var(--font-mono); margin-top: 2px;">
                    <?= htmlspecialchars($hasil['no_rm'] ?: 'RM-' . str_pad($hasil['id_pasien'], 4, '0', STR_PAD_LEFT)) ?>
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Nama Pasien</div>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-white); margin-top: 2px;">
                    <?= htmlspecialchars($hasil['nama']) ?>
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Umur & Gender</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-top: 2px;">
                    <?= $hasil['umur'] ? $hasil['umur'] . ' Tahun' : '-' ?> (<?= $hasil['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>)
                </div>
            </div>
            <div>
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Tanggal & Waktu Periksa</div>
                <div style="font-size: 1rem; font-weight: 600; color: var(--text-secondary); margin-top: 2px;">
                    <?= date('d M Y, H:i', strtotime($hasil['created_at'])) ?> WIB
                </div>
            </div>
        </div>

        <?php if (!empty($hasil['alamat'])): ?>
            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-secondary);">
                <strong>Alamat / Domisili:</strong> <?= htmlspecialchars($hasil['alamat']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Diagnostic Outcome Banner -->
    <div class="card" style="margin-bottom: 1.5rem; background: #ffffff; border: 1px solid <?= $themeBorder ?>; box-shadow: var(--shadow-sm); border-top: 4px solid <?= $themeColor ?>;">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 2rem; align-items: center;">
            <!-- Circular Percentage Display -->
            <div style="text-align: center; padding: 1.25rem 1.75rem; background: var(--bg-primary); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); font-weight: 700;">Tingkat Keyakinan</div>
                <div style="font-size: 3.2rem; font-weight: 900; color: <?= $themeColor ?>; letter-spacing: -0.03em; line-height: 1.1; margin: 4px 0;">
                    <?= number_format($persen, 1) ?>%
                </div>
                <div style="font-family: var(--font-mono); font-size: 0.82rem; color: var(--primary); font-weight: 700;">
                    CF: <?= number_format($cf, 6) ?>
                </div>
            </div>

            <!-- Conclusion Details -->
            <div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <span class="badge <?= $themeBadge ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                        <?= htmlspecialchars($hasil['interpretasi']) ?>
                    </span>
                    <span class="badge badge-secondary" style="font-size: 0.9rem; padding: 6px 14px;">
                        Prediksi: <?= htmlspecialchars($hasil['prediksi_sistem']) ?>
                    </span>
                    <?php if (!empty($hasil['gold_standard']) && $hasil['gold_standard'] !== 'Belum Terdiagnosis'): ?>
                        <span class="badge <?= $hasil['kesesuaian'] === 'Sesuai' ? 'badge-success' : 'badge-warning' ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                            Gold Standard: <?= htmlspecialchars($hasil['gold_standard']) ?> (<?= $hasil['kesesuaian'] ?>)
                        </span>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">
                    <?= htmlspecialchars($hasil['derajat_dbd']) ?>
                </h2>

                <?php if (!empty($hasil['rules_terpenuhi'])): ?>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">
                        <strong>Kriteria Terpenuhi:</strong> <?= htmlspecialchars($hasil['rules_terpenuhi']) ?>
                    </div>
                <?php endif; ?>

                <div style="padding: 14px 18px; background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid <?= $themeColor ?>; border-radius: var(--radius-sm); font-size: 0.9rem; color: var(--text-primary); line-height: 1.5;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 4px;">Rekomendasi Tindak Lanjut Medis:</strong>
                    <?= nl2br(htmlspecialchars($hasil['rekomendasi'])) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mathematical Deduction Step-by-Step Breakdown -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="14" x2="16" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/></svg>
                Detail Perhitungan & Rantai Inferensi Certainty Factor (Step-by-Step)
            </div>
            <span class="badge badge-primary"><?= count($steps) ?> Bukti / Evidence Terdeteksi</span>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
            Berikut adalah perincian matematis kombinasi nilai Certainty Factor untuk setiap gejala klinis dan parameter laboratorium yang dialami pasien berdasarkan formula:
            <code style="font-family: var(--font-mono); color: #38bdf8; background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">CF_kombinasi = CF_lama + CF_item × (1 - CF_lama)</code>.
        </p>

        <div class="table-responsive">
            <table class="table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 60px;">No.</th>
                        <th>Kode</th>
                        <th>Gejala / Parameter Laboratorium</th>
                        <th>Keyakinan Pasien</th>
                        <th>CF User</th>
                        <th>CF Pakar</th>
                        <th>CF Item (User × Pakar)</th>
                        <th>CF Sebelum</th>
                        <th>CF Sesudah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($steps)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Tidak ada gejala aktif yang dinilai positif pada pemeriksaan ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($steps as $idx => $s): ?>
                            <?php 
                                $gDetail = $gejalaLookup[$s['kode']] ?? null;
                                $isLab = substr($s['kode'], 0, 1) === 'L';
                            ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <span class="item-code <?= $isLab ? 'lab' : '' ?>"><?= $s['kode'] ?></span>
                                </td>
                                <td>
                                    <strong style="color: var(--text-white);"><?= htmlspecialchars($gDetail['nama_gejala'] ?? $s['kode']) ?></strong>
                                    <?php if (!empty($gDetail['kriteria'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--amber);"><?= htmlspecialchars($gDetail['kriteria']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--text-primary);">
                                        <?= htmlspecialchars($s['rating'] ?? $s['rating_text'] ?? '-') ?>
                                    </span>
                                </td>
                                <td style="font-family: var(--font-mono);"><?= number_format($s['user_cf'], 1) ?></td>
                                <td style="font-family: var(--font-mono);"><?= number_format($s['expert_cf'], 2) ?></td>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: #34d399;">
                                    <?= number_format($s['item_cf'], 4) ?>
                                </td>
                                <td style="font-family: var(--font-mono); color: var(--text-muted);">
                                    <?= number_format($s['cf_sebelum'] ?? $s['cfBefore'] ?? 0, 6) ?>
                                </td>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: #38bdf8;">
                                    <?= number_format($s['cf_sesudah'] ?? $s['cfAfter'] ?? 0, 6) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Doctor Signature Section for Official Print Report -->
    <div class="print-only" style="margin-top: 3rem; display: flex; justify-content: space-between; page-break-inside: avoid;">
        <div style="font-size: 10pt; color: #333; max-width: 320px;">
            <p><strong>Catatan Klinis:</strong></p>
            <p>Hasil sistem pendukung keputusan klinis (CDSS) ini bersifat konfirmatori dan alat bantu evaluasi cepat. Keputusan terapeutik dan manajemen klinis tetap mengacu pada penilaian dokter penanggung jawab pelayanan (DPJP).</p>
        </div>
        <div style="text-align: center; width: 220px; font-size: 10pt;">
            <div>Dicetak pada: <?= date('d F Y') ?></div>
            <div style="margin-top: 8px;">Dokter Pemeriksa,</div>
            <div style="height: 65px;"></div>
            <div style="border-bottom: 1px solid #000; font-weight: bold;">( dr. ..................................... )</div>
            <div style="font-size: 9pt; color: #555; margin-top: 2px;">SIP / NIP.</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
