<?php
/**
 * Certainty Factor (CF) Inference Engine & WHO Diagnostic Rule Evaluator
 * Clinical Decision Support System (CDSS) - Demam Berdarah Dengue
 */

class CFEngine {

    public static $skalaCF = [
        'tidak' => 0.0,
        'tidak tahu' => 0.2,
        'sedikit yakin' => 0.4,
        'cukup yakin' => 0.6,
        'yakin' => 0.8,
        'sangat yakin' => 1.0
    ];

    /**
     * Map scale text to numeric CF user value
     */
    public static function parseUserCF($val) {
        if (is_numeric($val)) {
            return (float)$val;
        }
        $key = strtolower(trim((string)$val));
        return self::$skalaCF[$key] ?? 0.0;
    }

    /**
     * Main calculation function
     * @param array $inputs Associative array [ 'G01' => 'Yakin' or 0.8, ... ]
     * @param array|null $expertCFMap Associative array [ 'G01' => 0.8, ... ]
     * @param array|null $gejalaDetails Associative array [ 'G01' => ['nama' => '...', 'kategori' => '...'], ... ]
     * @return array Calculation results
     */
    public static function diagnose($inputs, $expertCFMap = null, $gejalaDetails = null) {
        if ($expertCFMap === null) {
            $expertCFMap = self::getDefaultExpertCF();
        }

        $steps = [];
        $runningCF = 0.0;
        $activeEvidenceCount = 0;
        $warningSigns = [];

        // Check warning signs early
        $criticalCodes = [
            'G11' => 'Tanda Syok (Kegawatdaruratan / DSS)',
            'G07' => 'Perdarahan Spontan (Epistaksis / Gusi)',
            'G08' => 'Nyeri Ulu Hati / Abdomen Berat (Warning Sign WHO)',
            'L01' => 'Trombositopenia Berat (< 100.000 /uL)',
            'L02' => 'Hemokonsentrasi Kebocoran Plasma (Hematokrit naik >= 20%)'
        ];

        foreach ($inputs as $code => $rating) {
            $userCF = self::parseUserCF($rating);
            $expertCF = $expertCFMap[$code] ?? 0.0;
            $itemCF = $userCF * $expertCF;

            if ($userCF >= 0.4 && isset($criticalCodes[$code])) {
                $warningSigns[] = [
                    'kode' => $code,
                    'pesan' => $criticalCodes[$code]
                ];
            }

            if ($itemCF > 0) {
                $activeEvidenceCount++;
                $oldCF = $runningCF;
                if ($runningCF == 0.0) {
                    $runningCF = $itemCF;
                    $formulaStr = "CF1 = CF_user (" . number_format($userCF, 1) . ") × CF_pakar (" . number_format($expertCF, 2) . ") = " . number_format($itemCF, 4);
                } else {
                    if ($oldCF > 0 && $itemCF > 0) {
                        $runningCF = $oldCF + ($itemCF * (1.0 - $oldCF));
                        $formulaStr = "CF_kombinasi = " . number_format($oldCF, 4) . " + " . number_format($itemCF, 4) . " × (1 - " . number_format($oldCF, 4) . ") = " . number_format($runningCF, 6);
                    } elseif ($oldCF < 0 && $itemCF < 0) {
                        $runningCF = $oldCF + ($itemCF * (1.0 + $oldCF));
                        $formulaStr = "CF_kombinasi = " . number_format($oldCF, 4) . " + " . number_format($itemCF, 4) . " × (1 + " . number_format($oldCF, 4) . ") = " . number_format($runningCF, 6);
                    } else {
                        $minAbs = min(abs($oldCF), abs($itemCF));
                        $runningCF = ($oldCF + $itemCF) / (1.0 - $minAbs);
                        $formulaStr = "CF_kombinasi = (" . number_format($oldCF, 4) . " + " . number_format($itemCF, 4) . ") / (1 - " . number_format($minAbs, 4) . ") = " . number_format($runningCF, 6);
                    }
                }

                $gName = $gejalaDetails[$code]['nama_gejala'] ?? $code;
                $gKat = $gejalaDetails[$code]['kategori'] ?? (substr($code, 0, 1) === 'L' ? 'lab' : 'klinis');

                $steps[] = [
                    'kode' => $code,
                    'nama' => $gName,
                    'kategori' => $gKat,
                    'rating_text' => is_numeric($rating) ? self::getRatingText((float)$rating) : ucfirst($rating),
                    'user_cf' => (float)$userCF,
                    'expert_cf' => (float)$expertCF,
                    'item_cf' => round($itemCF, 4),
                    'cf_sebelum' => round($oldCF, 6),
                    'cf_sesudah' => round($runningCF, 6),
                    'formula' => $formulaStr
                ];
            }
        }

        // Final CF and percentage
        $finalCF = round($runningCF, 6);
        $percentage = round($finalCF * 100, 2);

        // Evaluate WHO diagnostic rules
        $rulesResult = self::evaluateWHORules($inputs);

        // Clinical interpretation and recommendations
        $interp = self::getInterpretation($finalCF, $rulesResult['derajat_dbd'], $warningSigns);

        // Prediction for validation (threshold >= 0.6 = DBD Positif)
        $prediksi = ($finalCF >= 0.60) ? 'DBD Positif' : 'DBD Negatif';

        return [
            'final_cf' => $finalCF,
            'persentase' => $percentage,
            'interpretasi' => $interp['label'],
            'kategori_resiko' => $interp['resiko'],
            'badge_color' => $interp['badge_color'],
            'alert_class' => $interp['alert_class'],
            'rekomendasi' => $interp['rekomendasi'],
            'tindakan_klinis' => $interp['tindakan_klinis'],
            'derajat_dbd' => $rulesResult['derajat_dbd'],
            'derajat_deskripsi' => $rulesResult['deskripsi'],
            'rules_terpenuhi' => $rulesResult['rules_terpenuhi'],
            'warning_signs' => $warningSigns,
            'has_shock' => !empty($rulesResult['has_shock']),
            'prediksi' => $prediksi,
            'active_evidence_count' => $activeEvidenceCount,
            'steps' => $steps
        ];
    }

    /**
     * Map numeric CF User back to text label
     */
    public static function getRatingText($val) {
        if ($val >= 0.95) return 'Sangat yakin';
        if ($val >= 0.75) return 'Yakin';
        if ($val >= 0.55) return 'Cukup yakin';
        if ($val >= 0.35) return 'Sedikit yakin';
        if ($val >= 0.15) return 'Tidak tahu';
        return 'Tidak';
    }

    /**
     * Evaluate Rule Base WHO
     */
    public static function evaluateWHORules($inputs) {
        $u = [];
        foreach ($inputs as $code => $rating) {
            $cf = self::parseUserCF($rating);
            $u[$code] = ($cf >= 0.4); // Considered present if rating >= 'Sedikit yakin'
        }

        $rulesMatched = [];
        $derajatDBD = 'Non-DBD / Suspek Ringan';
        $deskripsi = 'Belum memenuhi kriteria klinis komprehensif WHO untuk DBD.';
        $hasShock = false;

        // R4: Syok klinis (G11) -> Derajat III/IV (DSS)
        if (!empty($u['G11'])) {
            $derajatDBD = 'DBD Derajat III / IV (Dengue Shock Syndrome - DSS)';
            $deskripsi = 'Ditemukan kegagalan sirkulasi, nadi cepat dan lemah, tekanan nadi menyempit (≤20 mmHg) atau hipotensi, akral dingin dan gelisah. Merupakan KEGAWATDARURATAN MEDIS!';
            $rulesMatched[] = 'R4: Tanda syok klinis (G11) terpenuhi';
            $hasShock = true;
        }
        // R3: Perdarahan spontan (G07) + Trombositopenia (L01) + Hemokonsentrasi (L02)
        elseif (!empty($u['G07']) && !empty($u['L01']) && !empty($u['L02'])) {
            $derajatDBD = 'DBD Derajat II';
            $deskripsi = 'Disertai perdarahan spontan (seperti epistaksis, perdarahan gusi, atau melena) disertai trombositopenia (<100.000/uL) dan hemokonsentrasi kebocoran plasma.';
            $rulesMatched[] = 'R3: Perdarahan spontan + trombositopenia + hemokonsentrasi terpenuhi';
        }
        // R1: Kriteria WHO (G01 + G05 + G09 + L01)
        elseif (!empty($u['G01']) && !empty($u['G05']) && !empty($u['G09']) && !empty($u['L01'])) {
            $derajatDBD = 'DBD Derajat I (Kriteria WHO Terpenuhi)';
            $deskripsi = 'Demam akut disertai manifestasi perdarahan uji torniket positif dan/atau petekie, serta bukti laboratorium trombositopenia (<100.000/uL).';
            $rulesMatched[] = 'R1: Kriteria diagnostik kardinal WHO terpenuhi';
        }
        // R2: Suspek Dengue klasik (G01 + G02 + G03 + G04)
        elseif (!empty($u['G01']) && !empty($u['G02']) && !empty($u['G03']) && !empty($u['G04'])) {
            $derajatDBD = 'Suspek Demam Dengue (Dengue Fever / DF)';
            $deskripsi = 'Gejala sindrom dengue klasik (demam tinggi mendadak, sakit kepala berat, nyeri retro-orbital, myalgia) tanpa tanda kebocoran plasma nyata.';
            $rulesMatched[] = 'R2: Gejala klasik sindrom dengue terpenuhi';
        }

        // R5: Konfirmasi Lab Spesifik
        if (!empty($u['L04']) && !empty($u['L05'])) {
            $rulesMatched[] = 'R5: Konfirmasi laboratoris ganda (Antigen NS1 & Serologi Dengue Positif)';
        } elseif (!empty($u['L04'])) {
            $rulesMatched[] = 'R5: Konfirmasi laboratoris dini (Antigen NS1 Dengue Positif)';
        } elseif (!empty($u['L05'])) {
            $rulesMatched[] = 'R5: Konfirmasi laboratoris serologis (IgM / IgG Dengue Positif)';
        }

        return [
            'derajat_dbd' => $derajatDBD,
            'deskripsi' => $deskripsi,
            'rules_terpenuhi' => $rulesMatched,
            'has_shock' => $hasShock
        ];
    }

    /**
     * Map CF to clinical interpretation & action plan
     */
    public static function getInterpretation($finalCF, $derajatDBD, $warningSigns = []) {
        if ($finalCF >= 0.80) {
            $label = 'Sangat mungkin DBD';
            $resiko = 'Sangat Tinggi (High Risk)';
            $badgeColor = '#ef4444'; // Red
            $alertClass = 'alert-danger';
            $rekomendasi = 'Rujuk segera ke fasilitas kesehatan tingkat lanjut / pertimbangkan rawat inap (opname). Lakukan konfirmasi laboratorium lanjutan (Antigen NS1 / Serologi IgM-IgG), pemasangan infus cairan kristaloid isotonik, dan monitoring tanda-tanda vital serta urin output secara ketat.';
            $tindakan = [
                'Pertimbangkan rawat inap di ruang perawatan akut / intensif bila ada warning signs.',
                'Pemberian cairan intravena (Ringer Laktat / Asering / NaCl 0.9%) dengan tetesan terpantau.',
                'Pemeriksaan darah lengkap berkala setiap 6 - 12 jam (trombosit, hematokrit, leukosit).',
                'Edukasi tirah baring total dan monitor intake-output cairan ketat.',
                'Hindari obat antipiretik golongan NSAID (aspirin, ibuprofen) karena risiko memicu perdarahan lambung.'
            ];
        } elseif ($finalCF >= 0.60) {
            $label = 'Kemungkinan DBD';
            $resiko = 'Tinggi (Moderate-High Risk)';
            $badgeColor = '#f59e0b'; // Amber / Orange
            $alertClass = 'alert-warning';
            $rekomendasi = 'Observasi ketat kondisi klinis pasien. Ulangi pemeriksaan laboratorium hematologi berkala setiap 12-24 jam. Instruksikan rehidrasi cairan oral adekuat dan awasi munculnya tanda bahaya (warning signs).';
            $tindakan = [
                'Rehidrasi oral agresif (air putih, jus buah, oralit, susu) minimal 2 - 3 liter/hari.',
                'Kontrol darah rutin serial (Hematokrit & Trombosit) 24 jam kemudian.',
                'Kompres hangat dan berikan antipiretik parasetamol bila demam tinggi.',
                'Edukasi pasien/keluarga untuk segera ke IGD bila muncul muntah persisten, nyeri perut hebat, atau akral dingin.'
            ];
        } elseif ($finalCF >= 0.40) {
            $label = 'Dicurigai DBD';
            $resiko = 'Sedang (Moderate Risk)';
            $badgeColor = '#3b82f6'; // Blue
            $alertClass = 'alert-info';
            $rekomendasi = 'Dicurigai terinfeksi virus dengue. Lakukan pemeriksaan laboratorium penunjang darah lengkap dan edukasi pasien tentang pemantauan tanda bahaya secara mandiri di rumah.';
            $tindakan = [
                'Pemeriksaan skrining laboratorium darah lengkap (DL).',
                'Istirahat yang cukup (bed rest) dan nutrisi bergizi seimbang.',
                'Pemberian cairan minum yang cukup.',
                'Evaluasi ulang dalam 24-48 jam jika demam tidak kunjung reda pada hari ke-3 atau ke-4.'
            ];
        } elseif ($finalCF > 0.00) {
            $label = 'Kemungkinan kecil DBD';
            $resiko = 'Rendah (Low Risk)';
            $badgeColor = '#10b981'; // Green
            $alertClass = 'alert-success';
            $rekomendasi = 'Kemungkinan kecil DBD. Berikan terapi simtomatik untuk meredakan keluhan dan evaluasi diagnosis banding infeksi virus lainnya. Pantau perkembangan gejala bila kondisi memburuk.';
            $tindakan = [
                'Terapi simtomatik (antipiretik/analgetik parasetamol).',
                'Edukasi pola hidup bersih sehat dan istirahat.',
                'Kontrol kembali bila demam menetap lebih dari 3 hari.'
            ];
        } else {
            $label = 'Tidak ada indikasi (belum ada data)';
            $resiko = 'Tidak Terdefinisi';
            $badgeColor = '#64748b'; // Slate
            $alertClass = 'alert-secondary';
            $rekomendasi = 'Lengkapi pengisian data gejala klinis dan parameter laboratorium pada formulir diagnosis untuk mendapatkan hasil inferensi Certainty Factor.';
            $tindakan = [
                'Lakukan anamnesis klinis lengkap dan pemeriksaan fisik penunjang.'
            ];
        }

        // Additional shock priority warning
        if (!empty($warningSigns)) {
            $rekomendasi .= " PERHATIAN: Ditemukan " . count($warningSigns) . " tanda bahaya klinis (warning signs). Lakukan intervensi medis prioritas!";
        }

        return [
            'label' => $label,
            'resiko' => $resiko,
            'badge_color' => $badgeColor,
            'alert_class' => $alertClass,
            'rekomendasi' => $rekomendasi,
            'tindakan_klinis' => $tindakan
        ];
    }

    /**
     * Synchronize database knowledge base with cdss_data.json
     * Ensures file export stays identical to the database at all times.
     */
    public static function syncKnowledgeBaseToJson($pdo, $jsonPath = null) {
        if ($jsonPath === null) {
            $jsonPath = dirname(__DIR__) . '/cdss_data.json';
        }

        try {
            // 1. Fetch diseases
            $penyakitRows = $pdo->query("SELECT kode_penyakit as kode, nama_penyakit as nama, deskripsi FROM penyakit ORDER BY id_penyakit ASC")->fetchAll(PDO::FETCH_ASSOC);

            // 2. Fetch symptoms and expert CF
            $gejalaRows = $pdo->query("
                SELECT g.kode_gejala as kode, g.nama_gejala as nama, 
                       COALESCE(a.nilai_cf, 0.0) as cf_pakar, 
                       g.keterangan as ket, g.kategori, g.kriteria
                FROM gejala g
                LEFT JOIN aturan a ON g.id_gejala = a.id_gejala AND a.id_penyakit = 1
                ORDER BY g.id_gejala ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Clean format for json
            $cleanGejala = [];
            foreach ($gejalaRows as $g) {
                $item = [
                    'kode' => $g['kode'],
                    'nama' => $g['nama'],
                ];
                if (!empty($g['kriteria'])) {
                    $item['kriteria'] = $g['kriteria'];
                }
                $item['cf_pakar'] = (float)$g['cf_pakar'];
                $item['ket'] = $g['ket'] ?? '';
                $item['kategori'] = $g['kategori'];
                $cleanGejala[] = $item;
            }

            // 3. Fetch scale
            $skalaRows = $pdo->query("SELECT pilihan, nilai_cf, keterangan FROM skala_cf ORDER BY id_skala ASC")->fetchAll(PDO::FETCH_ASSOC);
            $cleanSkala = [];
            foreach ($skalaRows as $s) {
                $cleanSkala[] = [
                    'pilihan' => $s['pilihan'],
                    'nilai_cf' => (float)$s['nilai_cf'],
                    'keterangan' => $s['keterangan'] ?? ''
                ];
            }

            // 4. Fetch WHO rules
            $ruleRows = $pdo->query("SELECT kode_rule, kondisi, kesimpulan, catatan FROM rule_base ORDER BY id_rule ASC")->fetchAll(PDO::FETCH_ASSOC);
            $cleanRules = [];
            foreach ($ruleRows as $r) {
                $cleanRules[] = [
                    'kode_rule' => $r['kode_rule'],
                    'kondisi' => $r['kondisi'],
                    'kesimpulan' => $r['kesimpulan'],
                    'catatan' => $r['catatan'] ?? ''
                ];
            }

            // 5. Preserve simulation patients if existing file has them
            $existingPatients = [];
            if (file_exists($jsonPath)) {
                $existingContent = file_get_contents($jsonPath);
                $decoded = json_decode($existingContent, true);
                if (isset($decoded['pasien_simulasi'])) {
                    $existingPatients = $decoded['pasien_simulasi'];
                }
            }

            $jsonData = [
                'penyakit' => $penyakitRows,
                'gejala' => $cleanGejala,
                'skala_cf_user' => $cleanSkala,
                'rule_base' => $cleanRules,
                'pasien_simulasi' => $existingPatients,
                'last_updated' => date('Y-m-d H:i:s')
            ];

            file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return true;
        } catch (Exception $e) {
            error_log("Failed to sync cdss_data.json: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Default fallback expert CFs (identical to Excel knowledge base)
     */
    public static function getDefaultExpertCF() {
        return [
            'G01' => 0.80,
            'G02' => 0.60,
            'G03' => 0.60,
            'G04' => 0.70,
            'G05' => 0.90,
            'G06' => 0.50,
            'G07' => 0.85,
            'G08' => 0.70,
            'G09' => 0.85,
            'G10' => 0.60,
            'G11' => 0.95,
            'L01' => 0.90,
            'L02' => 0.90,
            'L03' => 0.60,
            'L04' => 0.95,
            'L05' => 0.90
        ];
    }
}
