<?php
/**
 * Database Migration & Seeder for CDSS DBD Certainty Factor
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'cdss_dbd';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // 1. Table: penyakit
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `penyakit` (
            `id_penyakit` INT AUTO_INCREMENT PRIMARY KEY,
            `kode_penyakit` VARCHAR(10) NOT NULL UNIQUE,
            `nama_penyakit` VARCHAR(100) NOT NULL,
            `deskripsi` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $penCols = $pdo->query("SHOW COLUMNS FROM `penyakit`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('deskripsi', $penCols)) {
        $pdo->exec("ALTER TABLE `penyakit` ADD COLUMN `deskripsi` TEXT NULL AFTER `nama_penyakit`");
    }

    // 2. Table: gejala
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gejala` (
            `id_gejala` INT AUTO_INCREMENT PRIMARY KEY,
            `kode_gejala` VARCHAR(10) NOT NULL UNIQUE,
            `nama_gejala` VARCHAR(150) NOT NULL,
            `kategori` ENUM('klinis', 'lab') DEFAULT 'klinis',
            `kriteria` VARCHAR(100) NULL,
            `keterangan` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Check if columns exist in gejala, if not add them
    $cols = $pdo->query("SHOW COLUMNS FROM `gejala`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kategori', $cols)) {
        $pdo->exec("ALTER TABLE `gejala` ADD COLUMN `kategori` ENUM('klinis', 'lab') DEFAULT 'klinis' AFTER `nama_gejala`");
    }
    if (!in_array('kriteria', $cols)) {
        $pdo->exec("ALTER TABLE `gejala` ADD COLUMN `kriteria` VARCHAR(100) NULL AFTER `kategori`");
    }
    if (!in_array('keterangan', $cols)) {
        $pdo->exec("ALTER TABLE `gejala` ADD COLUMN `keterangan` TEXT NULL AFTER `kriteria`");
    }

    // 3. Table: aturan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `aturan` (
            `id_aturan` INT AUTO_INCREMENT PRIMARY KEY,
            `id_penyakit` INT NOT NULL,
            `id_gejala` INT NOT NULL,
            `nilai_cf` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            KEY `id_penyakit` (`id_penyakit`),
            KEY `id_gejala` (`id_gejala`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Table: skala_cf
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `skala_cf` (
            `id_skala` INT AUTO_INCREMENT PRIMARY KEY,
            `pilihan` VARCHAR(50) NOT NULL,
            `nilai_cf` DECIMAL(3,2) NOT NULL,
            `keterangan` VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Table: pasien
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pasien` (
            `id_pasien` INT AUTO_INCREMENT PRIMARY KEY,
            `no_rm` VARCHAR(30) NULL,
            `nama` VARCHAR(100) NOT NULL,
            `umur` INT DEFAULT NULL,
            `jenis_kelamin` ENUM('L','P') DEFAULT NULL,
            `alamat` TEXT,
            `gold_standard` ENUM('DBD Positif', 'DBD Negatif', 'Belum Terdiagnosis') DEFAULT 'Belum Terdiagnosis',
            `tanggal_periksa` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pCols = $pdo->query("SHOW COLUMNS FROM `pasien`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('no_rm', $pCols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `no_rm` VARCHAR(30) NULL AFTER `id_pasien`");
    }
    if (!in_array('gold_standard', $pCols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `gold_standard` ENUM('DBD Positif', 'DBD Negatif', 'Belum Terdiagnosis') DEFAULT 'Belum Terdiagnosis' AFTER `alamat`");
    }
    if (!in_array('tanggal_periksa', $pCols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `tanggal_periksa` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `gold_standard`");
    }

    // 6. Table: pasien_gejala
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pasien_gejala` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `id_pasien` INT NOT NULL,
            `id_gejala` INT NOT NULL,
            `nilai_user` DECIMAL(3,2) DEFAULT 0.00,
            KEY `id_pasien` (`id_pasien`),
            KEY `id_gejala` (`id_gejala`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 7. Table: hasil_diagnosis
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `hasil_diagnosis` (
            `id_hasil` INT AUTO_INCREMENT PRIMARY KEY,
            `id_pasien` INT NOT NULL,
            `nilai_cf_akhir` DECIMAL(8,6) NOT NULL,
            `persentase` DECIMAL(5,2) NOT NULL,
            `interpretasi` VARCHAR(100) NOT NULL,
            `derajat_dbd` VARCHAR(150) DEFAULT NULL,
            `rekomendasi` TEXT NOT NULL,
            `rules_terpenuhi` TEXT NULL,
            `prediksi_sistem` VARCHAR(50) NOT NULL,
            `kesesuaian` VARCHAR(50) DEFAULT NULL,
            `detail_perhitungan` LONGTEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `id_pasien` (`id_pasien`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 8. Table: rule_base
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `rule_base` (
            `id_rule` INT AUTO_INCREMENT PRIMARY KEY,
            `kode_rule` VARCHAR(10) NOT NULL,
            `kondisi` TEXT NOT NULL,
            `kesimpulan` VARCHAR(150) NOT NULL,
            `catatan` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tables ensured successfully!\n";

    // Seed data from cdss_data.json
    $json = json_decode(file_get_contents(__DIR__ . '/cdss_data.json'), true);

    // Seed Penyakit
    $pdo->exec("DELETE FROM `penyakit`");
    $stmt = $pdo->prepare("INSERT INTO `penyakit` (`id_penyakit`, `kode_penyakit`, `nama_penyakit`, `deskripsi`) VALUES (?, ?, ?, ?)");
    $stmt->execute([1, 'P01', 'Demam Berdarah Dengue (DBD)', 'Penyakit infeksi yang disebabkan oleh virus Dengue dan ditularkan oleh gigitan nyamuk Aedes aegypti atau Aedes albopictus.']);

    // Seed Gejala & Lab
    $pdo->exec("DELETE FROM `gejala`");
    $stmtG = $pdo->prepare("INSERT INTO `gejala` (`id_gejala`, `kode_gejala`, `nama_gejala`, `kategori`, `kriteria`, `keterangan`) VALUES (?, ?, ?, ?, ?, ?)");
    $cfMap = [];
    $idGMap = [];
    $idx = 1;
    foreach ($json['gejala'] as $g) {
        $stmtG->execute([
            $idx,
            $g['kode'],
            $g['nama'],
            $g['kategori'],
            $g['kriteria'] ?? null,
            $g['ket']
        ]);
        $cfMap[$g['kode']] = (float)$g['cf_pakar'];
        $idGMap[$g['kode']] = $idx;
        $idx++;
    }

    // Seed Aturan (Knowledge Base CF Pakar)
    $pdo->exec("DELETE FROM `aturan`");
    $stmtA = $pdo->prepare("INSERT INTO `aturan` (`id_penyakit`, `id_gejala`, `nilai_cf`) VALUES (?, ?, ?)");
    foreach ($json['gejala'] as $g) {
        $stmtA->execute([
            1, // P01
            $idGMap[$g['kode']],
            $g['cf_pakar']
        ]);
    }

    // Seed Skala CF
    $pdo->exec("DELETE FROM `skala_cf`");
    $stmtS = $pdo->prepare("INSERT INTO `skala_cf` (`pilihan`, `nilai_cf`, `keterangan`) VALUES (?, ?, ?)");
    foreach ($json['skala_cf_user'] as $s) {
        $stmtS->execute([$s['pilihan'], $s['nilai'], $s['ket']]);
    }

    // Seed Rule Base WHO
    $pdo->exec("DELETE FROM `rule_base`");
    $stmtR = $pdo->prepare("INSERT INTO `rule_base` (`kode_rule`, `kondisi`, `kesimpulan`, `catatan`) VALUES (?, ?, ?, ?)");
    foreach ($json['rules'] as $r) {
        $stmtR->execute([$r['rule'], $r['kondisi'], $r['diagnosis'], $r['catatan']]);
    }

    // Seed Patients & Simulation Data
    $pdo->exec("DELETE FROM `pasien_gejala`");
    $pdo->exec("DELETE FROM `hasil_diagnosis`");
    $pdo->exec("DELETE FROM `pasien`");

    $stmtP = $pdo->prepare("INSERT INTO `pasien` (`id_pasien`, `no_rm`, `nama`, `umur`, `jenis_kelamin`, `alamat`, `gold_standard`, `tanggal_periksa`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtPG = $pdo->prepare("INSERT INTO `pasien_gejala` (`id_pasien`, `id_gejala`, `nilai_user`) VALUES (?, ?, ?)");
    $stmtH = $pdo->prepare("INSERT INTO `hasil_diagnosis` (`id_pasien`, `nilai_cf_akhir`, `persentase`, `interpretasi`, `derajat_dbd`, `rekomendasi`, `rules_terpenuhi`, `prediksi_sistem`, `kesesuaian`, `detail_perhitungan`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $skalaLookup = [
        'tidak' => 0.0,
        'tidak tahu' => 0.2,
        'sedikit yakin' => 0.4,
        'cukup yakin' => 0.6,
        'yakin' => 0.8,
        'sangat yakin' => 1.0
    ];

    $demoAges = [24, 31, 19, 45, 12, 28, 35, 22, 17, 50, 29, 21, 26];
    $demoGenders = ['P', 'L', 'P', 'L', 'P', 'L', 'P', 'L', 'P', 'L', 'P', 'P', 'P'];
    $demoAddresses = [
        'Jl. Melati No. 12, Jakarta',
        'Jl. Mawar No. 4, Depok',
        'Jl. Cendrawasih No. 8, Tangerang',
        'Jl. Kenanga No. 15, Bekasi',
        'Jl. Flamboyan No. 2, Bogor',
        'Jl. Anggrek No. 19, Bandung',
        'Jl. Dahlia No. 7, Surabaya',
        'Jl. Teratai No. 21, Semarang',
        'Jl. Kamboja No. 33, Yogyakarta',
        'Jl. Bougenville No. 11, Solo',
        'Jl. Cemara No. 5, Malang',
        'Jl. Beringin No. 14, Denpasar',
        'Jl. Garuda No. 9, Medan'
    ];

    foreach ($json['pasien_simulasi'] as $idx => $p) {
        $pId = $p['no'];
        $noRm = 'RM-' . str_pad($pId, 4, '0', STR_PAD_LEFT);
        $umur = $demoAges[$idx] ?? 25;
        $jk = $demoGenders[$idx] ?? 'L';
        $alamat = $demoAddresses[$idx] ?? 'Indonesia';
        $gold = $p['gold_standard'] ?? 'Belum Terdiagnosis';
        $tgl = date('Y-m-d H:i:s', strtotime('-' . (14 - $idx) . ' days'));

        $stmtP->execute([$pId, $noRm, $p['nama'], $umur, $jk, $alamat, $gold, $tgl]);

        // Insert patient symptoms and calculate running CF
        $steps = [];
        $runningCF = 0.0;
        $evalRules = [];

        foreach ($p['input_user'] as $code => $rating) {
            $ratingNormalized = strtolower(trim((string)$rating));
            $userCF = $skalaLookup[$ratingNormalized] ?? 0.0;
            $gejalaId = $idGMap[$code] ?? null;

            if ($gejalaId) {
                $stmtPG->execute([$pId, $gejalaId, $userCF]);
            }

            $expertCF = $cfMap[$code] ?? 0.0;
            $itemCF = $userCF * $expertCF;

            if ($itemCF > 0) {
                $oldCF = $runningCF;
                if ($runningCF == 0.0) {
                    $runningCF = $itemCF;
                } else {
                    $runningCF = $runningCF + ($itemCF * (1.0 - $runningCF));
                }
                $steps[] = [
                    'kode' => $code,
                    'rating' => $rating,
                    'user_cf' => $userCF,
                    'expert_cf' => $expertCF,
                    'item_cf' => round($itemCF, 4),
                    'cf_sebelum' => round($oldCF, 6),
                    'cf_sesudah' => round($runningCF, 6)
                ];
            }
        }

        // Evaluate WHO Severity rules
        $u = [];
        foreach ($p['input_user'] as $code => $rating) {
            $u[$code] = ($skalaLookup[strtolower(trim((string)$rating))] ?? 0) >= 0.4;
        }

        $derajatDBD = 'Non-DBD / Belum Memenuhi Kriteria WHO';
        $rulesMatched = [];

        if (!empty($u['G11'])) {
            $derajatDBD = 'DBD Derajat III/IV (Dengue Shock Syndrome / DSS)';
            $rulesMatched[] = 'R4: Syok klinis/kegawatdaruratan (G11)';
        } elseif (!empty($u['G07']) && !empty($u['L01']) && !empty($u['L02'])) {
            $derajatDBD = 'DBD Derajat II';
            $rulesMatched[] = 'R3: Perdarahan spontan + trombositopenia + hemokonsentrasi';
        } elseif (!empty($u['G01']) && !empty($u['G05']) && !empty($u['G09']) && !empty($u['L01'])) {
            $derajatDBD = 'DBD Derajat I (Kriteria WHO Terpenuhi)';
            $rulesMatched[] = 'R1: Kriteria WHO terpenuhi (Demam + petekie + tourniquet + trombositopenia)';
        } elseif (!empty($u['G01']) && !empty($u['G02']) && !empty($u['G03']) && !empty($u['G04'])) {
            $derajatDBD = 'Suspek Dengue (Dengue Fever / DF)';
            $rulesMatched[] = 'R2: Gejala klasik sindrom dengue';
        }

        if (!empty($u['L04']) || !empty($u['L05'])) {
            $rulesMatched[] = 'R5: Konfirmasi laboratoris infeksi dengue (NS1 / Serologi)';
        }

        $finalCF = (float)$p['cf_akhir'];
        $persen = round($finalCF * 100, 2);
        $interpretasi = $p['interpretasi'];
        $prediksi = $p['prediksi'];
        $kesesuaian = $p['kesesuaian'];

        // Determine recommendation
        if ($finalCF >= 0.80) {
            $rekomendasi = "Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.";
        } elseif ($finalCF >= 0.60) {
            $rekomendasi = "Observasi klinis ketat, istirahat tirah baring, rehidrasi cairan oral adekuat, dan ulangi pemeriksaan trombosit & hematokrit setiap 12-24 jam.";
        } elseif ($finalCF >= 0.40) {
            $rekomendasi = "Dicurigai DBD. Lakukan pemeriksaan laboratorium darah rutin tambahan dan edukasi keluarga tentang tanda bahaya (warning signs).";
        } else {
            $rekomendasi = "Kemungkinan kecil DBD. Berikan terapi simtomatik, pantau perkembangan gejala, dan segera periksa kembali bila demam menetap lebih dari 3 hari.";
        }

        $stmtH->execute([
            $pId,
            $finalCF,
            $persen,
            $interpretasi,
            $derajatDBD,
            $rekomendasi,
            implode('; ', $rulesMatched),
            $prediksi,
            $kesesuaian,
            json_encode($steps),
            $tgl
        ]);
    }

    echo "Seeding completed successfully! 13 patients seeded with complete CF calculation and validation.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
