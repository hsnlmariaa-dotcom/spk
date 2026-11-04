<?php
$pageTitle = 'Panduan Teori & Protokol Klinis DBD';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                Panduan Teori & Dokumentasi Klinis CDSS DBD
            </div>
            <div class="page-subtitle">
                Penjelasan metodologi inferensi Certainty Factor (Shortliffe & Buchanan) dan protokol diagnosis klinis Demam Berdarah Dengue (WHO).
            </div>
        </div>
    </div>

    <!-- Section 1: Certainty Factor Theory -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                1. Metodologi Certainty Factor (CF)
            </div>
            <span class="badge badge-primary">Shortliffe & Buchanan (1975)</span>
        </div>

        <div style="font-size: 0.9rem; line-height: 1.7; color: var(--text-secondary);">
            <p style="margin-bottom: 1rem;">
                <strong>Certainty Factor (Faktor Kepastian)</strong> adalah metode penalaran klinis untuk mengelola ketidakpastian (*uncertainty management*) dalam sistem pakar medis. Metode ini merepresentasikan tingkat kepercayaan pakar medis dan keyakinan klinis pengguna terhadap suatu hipotesis diagnosis berdasarkan fakta atau gejala yang teramati.
            </p>

            <h4 style="color: var(--text-white); margin: 1.25rem 0 0.5rem;">A. Perhitungan CF per Item Evidence</h4>
            <p>
                Untuk setiap gejala klinis atau parameter laboratorium ke-$i$, nilai kepastian awal dihitung dengan mengalikan bobot keyakinan pengguna (*CF User*) dengan bobot kepastian pakar medis (*CF Pakar*):
            </p>
            <div class="formula-box">
                CF[h, e] = CF_user × CF_pakar
            </div>

            <h4 style="color: var(--text-white); margin: 1.25rem 0 0.5rem;">B. Rumus Kombinasi Dua atau Lebih Evidence (CF Combine)</h4>
            <p>
                Ketika terdapat beberapa gejala atau parameter laboratorium yang terkonfirmasi, nilai Certainty Factor digabungkan secara berurutan (*sequential combination*):
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.75rem;">
                <div style="padding: 14px; background: var(--emerald-light); border-left: 3px solid var(--emerald); border-radius: var(--radius-sm);">
                    <strong style="color: var(--emerald); font-size: 0.85rem;">Keduanya Bernilai Positif (CF1 > 0 dan CF2 > 0):</strong>
                    <div class="formula-box" style="margin-top: 6px;">
                        CF_kombinasi = CF1 + CF2 × (1 - CF1)
                    </div>
                </div>

                <div style="padding: 14px; background: var(--rose-light); border-left: 3px solid var(--rose); border-radius: var(--radius-sm);">
                    <strong style="color: var(--rose); font-size: 0.85rem;">Keduanya Bernilai Negatif (CF1 < 0 dan CF2 < 0):</strong>
                    <div class="formula-box" style="margin-top: 6px;">
                        CF_kombinasi = CF1 + CF2 × (1 + CF1)
                    </div>
                </div>
            </div>

            <div style="margin-top: 1rem; padding: 14px; background: var(--primary-light); border-left: 3px solid var(--primary); border-radius: var(--radius-sm);">
                <strong style="color: var(--primary); font-size: 0.85rem;">Berbeda Tanda (Satu Positif dan Satu Negatif):</strong>
                <div class="formula-box" style="margin-top: 6px;">
                    CF_kombinasi = (CF1 + CF2) / (1 - min(|CF1|, |CF2|))
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: WHO Dengue Guidelines -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                2. Pedoman Diagnosis Klinis DBD Berdasarkan Standar WHO
            </div>
            <span class="badge badge-warning">Pedoman WHO & Kemenkes RI</span>
        </div>

        <div style="font-size: 0.9rem; line-height: 1.7; color: var(--text-secondary);">
            <p style="margin-bottom: 1rem;">
                Menurut pedoman <em>World Health Organization (WHO)</em>, penegakan diagnosis Demam Berdarah Dengue (DBD) memerlukan keberadaan demam akut disertai manifestasi perdarahan dan tanda kebocoran plasma:
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <!-- Derajat I -->
                <div style="padding: 14px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong style="color: var(--text-white);">DBD Derajat I</strong>
                        <span class="badge badge-warning">Ringan</span>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-secondary);">
                        Demam mendadak tinggi 2-7 hari disertai gejala tidak khas dan satu-satunya manifestasi perdarahan adalah uji tourniquet (Rumple Leede) positif dan/atau petekie, serta trombositopenia (&lt;100.000/uL).
                    </p>
                </div>

                <!-- Derajat II -->
                <div style="padding: 14px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong style="color: var(--text-white);">DBD Derajat II</strong>
                        <span class="badge badge-danger">Sedang</span>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-secondary);">
                        Gejala Derajat I disertai <strong>perdarahan spontan</strong> pada kulit atau jaringan lain, seperti mimisan (epistaksis), perdarahan gusi, atau melena, disertai peningkatan hematokrit &ge;20%.
                    </p>
                </div>

                <!-- Derajat III & IV (DSS) -->
                <div style="padding: 14px; background: var(--rose-light); border: 1px solid var(--rose-border); border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong style="color: var(--rose);">DBD Derajat III & IV (DSS)</strong>
                        <span class="badge badge-danger">KEGAWATDARURATAN</span>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-secondary);">
                        <strong>Dengue Shock Syndrome (DSS)</strong>: Kegagalan sirkulasi darah akut. Ditemukan nadi cepat dan lemah, tekanan nadi menyempit (&le;20 mmHg) atau hipotensi, sianosis di sekitar mulut, akral dingin dan lembap. <em>Harus segera dirujuk ke ICU!</em>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Diagnostic Workflow Flowchart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                3. Alur Kerja Sistem CDSS (Workflow)
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.88rem; color: var(--text-secondary);">
            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
                <div>
                    <strong style="color: var(--text-white);">Input Rekam Medis & Anamnesis Gejala</strong>
                    <p style="font-size: 0.82rem; margin-top: 2px;">Dokter atau perawat menginput identitas pasien dan memilih skala keyakinan gejala klinis (G01 - G11).</p>
                </div>
            </div>

            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</div>
                <div>
                    <strong style="color: var(--text-white);">Konfirmasi Parameter Laboratorium Kritis</strong>
                    <p style="font-size: 0.82rem; margin-top: 2px;">Memasukkan hasil trombosit, hematokrit, leukosit, serta antigen NS1 atau serologi IgM/IgG jika telah diperiksa.</p>
                </div>
            </div>

            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</div>
                <div>
                    <strong style="color: var(--text-white);">Inferensi Certainty Factor & Evaluasi Warning Signs</strong>
                    <p style="font-size: 0.82rem; margin-top: 2px;">Sistem mengkalkulasi CF kombinasi berjalan secara real-time dan mengevaluasi aturan derajat WHO secara simultan.</p>
                </div>
            </div>

            <div style="display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">4</div>
                <div>
                    <strong style="color: var(--text-white);">Penerbitan Rekomendasi Medis & Cetak Laporan</strong>
                    <p style="font-size: 0.82rem; margin-top: 2px;">Hasil diagnosis, tingkat probabilitas, derajat keparahan, dan rekomendasi rawat inap / monitoring cairan dicetak dalam format resmi rekam medis rumah sakit.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
