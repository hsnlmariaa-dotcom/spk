<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cf_engine.php';
$pdo = getDB();

// Handle delete action before outputting HTML
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM hasil_diagnosis WHERE id_pasien = ?")->execute([$delId]);
        $pdo->prepare("DELETE FROM pasien_gejala WHERE id_pasien = ?")->execute([$delId]);
        $pdo->prepare("DELETE FROM pasien WHERE id_pasien = ?")->execute([$delId]);
        $pdo->commit();
        header("Location: " . BASE_URL . "/pasien.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal menghapus pasien: " . $e->getMessage();
    }
}

$pageTitle = 'Rekam Medis & Riwayat Pasien';
require_once __DIR__ . '/includes/header.php';

// Filter query
$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT p.*, h.id_hasil, h.nilai_cf_akhir, h.persentase, h.interpretasi, h.derajat_dbd, h.prediksi_sistem, h.kesesuaian, h.created_at as tgl_diagnosa
    FROM pasien p
    LEFT JOIN (
        SELECT h1.* FROM hasil_diagnosis h1
        INNER JOIN (
            SELECT id_pasien, MAX(id_hasil) as max_id FROM hasil_diagnosis GROUP BY id_pasien
        ) h2 ON h1.id_hasil = h2.max_id
    ) h ON p.id_pasien = h.id_pasien
";

if ($filter === 'positif') {
    $sql .= " WHERE h.prediksi_sistem = 'DBD Positif'";
} elseif ($filter === 'negatif') {
    $sql .= " WHERE h.prediksi_sistem = 'DBD Negatif'";
} elseif ($filter === 'syok') {
    $sql .= " WHERE h.derajat_dbd LIKE '%Syok%' OR h.derajat_dbd LIKE '%DSS%' OR h.derajat_dbd LIKE '%Derajat III%'";
}

$sql .= " ORDER BY p.id_pasien DESC";
$pasienList = $pdo->query($sql)->fetchAll();

// Counts
$totalAll = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$totalPos = $pdo->query("SELECT COUNT(*) FROM hasil_diagnosis WHERE prediksi_sistem = 'DBD Positif'")->fetchColumn();
$totalNeg = $pdo->query("SELECT COUNT(*) FROM hasil_diagnosis WHERE prediksi_sistem = 'DBD Negatif'")->fetchColumn();
$avgCF = $pdo->query("SELECT AVG(nilai_cf_akhir) FROM hasil_diagnosis")->fetchColumn() ?: 0.0;
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Rekam Medis & Riwayat Diagnosis Pasien
            </div>
            <div class="page-subtitle">
                Daftar lengkap riwayat pemeriksaan klinis, nilai Certainty Factor, dan diagnosis banding WHO.
            </div>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/diagnosa.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Pemeriksaan Baru
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert-banner" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399;">
            Data rekam medis pasien berhasil dihapus dari sistem.
        </div>
    <?php endif; ?>

    <!-- Summary Stats Bar -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card teal">
            <div>
                <div class="stat-value"><?= $totalAll ?></div>
                <div class="stat-label">Total Pasien</div>
            </div>
        </div>
        <div class="stat-card rose">
            <div>
                <div class="stat-value"><?= $totalPos ?></div>
                <div class="stat-label">Kasus Positif DBD</div>
            </div>
        </div>
        <div class="stat-card emerald">
            <div>
                <div class="stat-value"><?= $totalNeg ?></div>
                <div class="stat-label">Kasus Negatif</div>
            </div>
        </div>
        <div class="stat-card amber">
            <div>
                <div class="stat-value"><?= number_format($avgCF, 4) ?></div>
                <div class="stat-label">Rata-rata Nilai CF</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/pasien.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">Semua Pasien (<?= $totalAll ?>)</a>
                <a href="<?= BASE_URL ?>/pasien.php?filter=positif" class="btn btn-sm <?= $filter === 'positif' ? 'btn-primary' : 'btn-secondary' ?>">Positif DBD (<?= $totalPos ?>)</a>
                <a href="<?= BASE_URL ?>/pasien.php?filter=negatif" class="btn btn-sm <?= $filter === 'negatif' ? 'btn-primary' : 'btn-secondary' ?>">Negatif (<?= $totalNeg ?>)</a>
                <a href="<?= BASE_URL ?>/pasien.php?filter=syok" class="btn btn-sm <?= $filter === 'syok' ? 'btn-danger' : 'btn-secondary' ?>">Syok / DSS</a>
            </div>

            <div style="min-width: 260px;">
                <input type="text" id="tableSearchInput" class="form-control" placeholder="Cari nama pasien, No. RM..." style="padding: 7px 12px; font-size: 0.85rem;">
            </div>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table filterable-table">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Identitas Pasien</th>
                        <th>Tanggal Periksa</th>
                        <th>Nilai CF</th>
                        <th>Persentase</th>
                        <th>Interpretasi Sistem</th>
                        <th>Klasifikasi WHO</th>
                        <th>Gold Standard</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pasienList)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Tidak ada data pasien yang sesuai dengan filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pasienList as $p): ?>
                            <tr>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: #38bdf8;">
                                    <?= htmlspecialchars($p['no_rm'] ?: 'RM-' . str_pad($p['id_pasien'], 4, '0', STR_PAD_LEFT)) ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-white); font-size: 0.95rem;">
                                        <?= htmlspecialchars($p['nama']) ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        <?= $p['umur'] ? $p['umur'] . ' th' : '-' ?>, <?= $p['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                        <?php if (!empty($p['alamat'])): ?>
                                            • <?= htmlspecialchars($p['alamat']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-size: 0.82rem; color: var(--text-secondary);">
                                    <?= $p['tgl_diagnosa'] ? date('d/m/Y H:i', strtotime($p['tgl_diagnosa'])) : date('d/m/Y', strtotime($p['tanggal_periksa'])) ?>
                                </td>
                                <td>
                                    <?php if ($p['nilai_cf_akhir'] !== null): ?>
                                        <span style="font-family: var(--font-mono); font-weight: 700; color: #38bdf8;">
                                            <?= number_format($p['nilai_cf_akhir'], 4) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['persentase'] !== null): ?>
                                        <span style="font-weight: 800; color: <?= $p['nilai_cf_akhir'] >= 0.8 ? '#fb7185' : ($p['nilai_cf_akhir'] >= 0.6 ? '#fbbf24' : '#34d399') ?>;">
                                            <?= number_format($p['persentase'], 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['nilai_cf_akhir'] !== null): ?>
                                        <?php if ($p['nilai_cf_akhir'] >= 0.80): ?>
                                            <span class="badge badge-danger">Sangat Mungkin DBD</span>
                                        <?php elseif ($p['nilai_cf_akhir'] >= 0.60): ?>
                                            <span class="badge badge-warning">Kemungkinan DBD</span>
                                        <?php elseif ($p['nilai_cf_akhir'] >= 0.40): ?>
                                            <span class="badge badge-primary">Dicurigai DBD</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Kemungkinan Kecil</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum Diperiksa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($p['derajat_dbd'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($p['gold_standard']) && $p['gold_standard'] !== 'Belum Terdiagnosis'): ?>
                                        <span class="badge <?= $p['gold_standard'] === 'DBD Positif' ? 'badge-danger' : 'badge-secondary' ?>" style="font-size: 0.72rem;">
                                            <?= htmlspecialchars($p['gold_standard']) ?>
                                        </span>
                                        <?php if (!empty($p['kesesuaian'])): ?>
                                            <div style="font-size: 0.7rem; color: <?= $p['kesesuaian'] === 'Sesuai' ? '#34d399' : '#fbbf24' ?>; margin-top: 2px;">
                                                <?= $p['kesesuaian'] ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($p['id_hasil']): ?>
                                        <a href="<?= BASE_URL ?>/hasil.php?id=<?= $p['id_hasil'] ?>" class="btn btn-secondary btn-sm" title="Lihat Hasil & Rekomendasi">
                                            Detail & Cetak
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/pasien.php?delete=<?= $p['id_pasien'] ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data pasien ini?')" title="Hapus Data Pasien" style="color: #fb7185;">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
