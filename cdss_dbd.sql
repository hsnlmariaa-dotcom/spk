-- ==============================================================================
-- CDSS-DBD MySQL Database Dump
-- Clinical Decision Support System - Demam Berdarah Dengue (Certainty Factor)
-- Fully compatible with: MySQL 5.7/8.0+, MariaDB, TiDB Cloud Serverless
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ------------------------------------------------------------------------------
-- 1. Table structure & data: `penyakit`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `penyakit`;
CREATE TABLE `penyakit` (
  `id_penyakit` int NOT NULL AUTO_INCREMENT,
  `kode_penyakit` varchar(10) NOT NULL,
  `nama_penyakit` varchar(100) NOT NULL,
  `deskripsi` text,
  PRIMARY KEY (`id_penyakit`),
  UNIQUE KEY `kode_penyakit` (`kode_penyakit`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `penyakit` (`id_penyakit`, `kode_penyakit`, `nama_penyakit`, `deskripsi`) VALUES
(1, 'P01', 'Demam Berdarah Dengue (DBD)', 'Penyakit infeksi yang disebabkan oleh virus Dengue dan ditularkan oleh gigitan nyamuk Aedes aegypti atau Aedes albopictus.');

-- ------------------------------------------------------------------------------
-- 2. Table structure & data: `gejala`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `gejala`;
CREATE TABLE `gejala` (
  `id_gejala` int NOT NULL AUTO_INCREMENT,
  `kode_gejala` varchar(10) NOT NULL,
  `nama_gejala` varchar(150) NOT NULL,
  `kategori` enum('klinis','lab') DEFAULT 'klinis',
  `kriteria` varchar(100) DEFAULT NULL,
  `keterangan` text,
  PRIMARY KEY (`id_gejala`),
  UNIQUE KEY `kode_gejala` (`kode_gejala`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `gejala` (`id_gejala`, `kode_gejala`, `nama_gejala`, `kategori`, `kriteria`, `keterangan`) VALUES
(1, 'G01', 'Demam tinggi mendadak, 2-7 hari', 'klinis', NULL, 'Kriteria WHO — gejala kardinal DBD'),
(2, 'G02', 'Nyeri kepala hebat', 'klinis', NULL, 'Gejala penyerta umum sindrom dengue'),
(3, 'G03', 'Nyeri retro-orbital (di belakang mata)', 'klinis', NULL, 'Gejala penyerta umum sindrom dengue'),
(4, 'G04', 'Nyeri otot dan sendi (myalgia/artralgia)', 'klinis', NULL, 'Sering disebut \'breakbone fever\''),
(5, 'G05', 'Ruam kulit / petekie', 'klinis', NULL, 'Tanda perdarahan kulit — spesifisitas tinggi'),
(6, 'G06', 'Mual / muntah', 'klinis', NULL, 'Gejala penyerta, spesifisitas rendah'),
(7, 'G07', 'Perdarahan gusi / hidung (epistaksis)', 'klinis', NULL, 'Tanda perdarahan — kecurigaan derajat II+'),
(8, 'G08', 'Nyeri ulu hati / abdomen', 'klinis', NULL, 'Warning sign WHO'),
(9, 'G09', 'Uji tourniquet (Rumple Leede) positif', 'klinis', NULL, 'Pemeriksaan fisik penunjang WHO'),
(10, 'G10', 'Hepatomegali (pembesaran hati)', 'klinis', NULL, 'Tanda klinis penyerta'),
(11, 'G11', 'Tanda syok (akral dingin, nadi lemah, hipotensi)', 'klinis', NULL, 'Warning sign berat — DSS (derajat III/IV)'),
(12, 'L01', 'Trombosit (< 100.000 /uL)', 'lab', '< 100.000 /uL', 'Trombositopenia — kriteria WHO'),
(13, 'L02', 'Hematokrit (Naik >= 20% dari baseline)', 'lab', 'Naik >= 20% dari baseline', 'Indikasi kebocoran plasma'),
(14, 'L03', 'Leukosit (Leukopenia (< 4.000 /uL))', 'lab', 'Leukopenia (< 4.000 /uL)', 'Umum pada fase demam dengue'),
(15, 'L04', 'Antigen NS1 (Positif)', 'lab', 'Positif', 'Deteksi dini, akurat hari 1-5 demam'),
(16, 'L05', 'IgM / IgG Dengue (serologi) (Positif)', 'lab', 'Positif', 'Konfirmasi setelah hari ke-5');

-- ------------------------------------------------------------------------------
-- 3. Table structure & data: `aturan`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `aturan`;
CREATE TABLE `aturan` (
  `id_aturan` int NOT NULL AUTO_INCREMENT,
  `id_penyakit` int NOT NULL,
  `id_gejala` int NOT NULL,
  `nilai_cf` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id_aturan`),
  KEY `id_penyakit` (`id_penyakit`),
  KEY `id_gejala` (`id_gejala`),
  CONSTRAINT `aturan_ibfk_1` FOREIGN KEY (`id_penyakit`) REFERENCES `penyakit` (`id_penyakit`),
  CONSTRAINT `aturan_ibfk_2` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `aturan` (`id_aturan`, `id_penyakit`, `id_gejala`, `nilai_cf`) VALUES
(1, 1, 1, 0.80),
(2, 1, 2, 0.60),
(3, 1, 3, 0.60),
(4, 1, 4, 0.70),
(5, 1, 5, 0.90),
(6, 1, 6, 0.50),
(7, 1, 7, 0.85),
(8, 1, 8, 0.70),
(9, 1, 9, 0.85),
(10, 1, 10, 0.60),
(11, 1, 11, 0.95),
(12, 1, 12, 0.90),
(13, 1, 13, 0.90),
(14, 1, 14, 0.60),
(15, 1, 15, 0.95),
(16, 1, 16, 0.90);

-- ------------------------------------------------------------------------------
-- 4. Table structure & data: `skala_cf`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `skala_cf`;
CREATE TABLE `skala_cf` (
  `id_skala` int NOT NULL AUTO_INCREMENT,
  `pilihan` varchar(50) NOT NULL,
  `nilai_cf` decimal(3,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_skala`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO `skala_cf` (`id_skala`, `pilihan`, `nilai_cf`, `keterangan`) VALUES
(1, 'Tidak', 0.00, 'Gejala tidak dialami / parameter tidak diperiksa'),
(2, 'Tidak tahu', 0.20, 'Pasien ragu / tidak yakin'),
(3, 'Sedikit yakin', 0.40, 'Keyakinan rendah'),
(4, 'Cukup yakin', 0.60, 'Keyakinan sedang'),
(5, 'Yakin', 0.80, 'Keyakinan tinggi'),
(6, 'Sangat yakin', 1.00, 'Keyakinan penuh / hasil lab pasti');

-- ------------------------------------------------------------------------------
-- 5. Table structure & data: `rule_base`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `rule_base`;
CREATE TABLE `rule_base` (
  `id_rule` int NOT NULL AUTO_INCREMENT,
  `kode_rule` varchar(10) NOT NULL,
  `kondisi` text NOT NULL,
  `kesimpulan` varchar(150) NOT NULL,
  `catatan` text,
  PRIMARY KEY (`id_rule`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `rule_base` (`id_rule`, `kode_rule`, `kondisi`, `kesimpulan`, `catatan`) VALUES
(1, 'R1', 'G01 AND G05 AND G09 AND L01', 'DBD (kriteria WHO terpenuhi)', 'Demam + petekie + tourniquet(+) + trombositopenia'),
(2, 'R2', 'G01 AND G02 AND G03 AND G04', 'Suspek Dengue (belum tentu DBD)', 'Gejala klasik tanpa tanda perdarahan/kebocoran plasma'),
(3, 'R3', 'G07 AND L01 AND L02', 'DBD Derajat II', 'Ada perdarahan spontan + trombositopenia + hemokonsentrasi'),
(4, 'R4', 'G11', 'DBD Derajat III/IV (Dengue Shock Syndrome)', 'Kegawatdaruratan - rujuk segera ke fasilitas lebih tinggi'),
(5, 'R5', 'L04 OR L05', 'Konfirmasi laboratoris infeksi dengue', 'NS1 untuk deteksi dini; IgM/IgG untuk konfirmasi lanjutan');

-- ------------------------------------------------------------------------------
-- 6. Table structure & data: `pasien`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `pasien`;
CREATE TABLE `pasien` (
  `id_pasien` int NOT NULL AUTO_INCREMENT,
  `no_rm` varchar(30) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `umur` int DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text,
  `gold_standard` enum('DBD Positif','DBD Negatif','Belum Terdiagnosis') DEFAULT 'Belum Terdiagnosis',
  `tanggal_periksa` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `pasien` (`id_pasien`, `no_rm`, `nama`, `umur`, `jenis_kelamin`, `alamat`, `gold_standard`, `tanggal_periksa`) VALUES
(1, 'RM-0001', 'Ani', 24, 'P', 'Jl. Melati No. 12, Jakarta', 'DBD Positif', '2026-09-17 08:40:01'),
(2, 'RM-0002', 'Budi', 31, 'L', 'Jl. Mawar No. 4, Depok', 'DBD Negatif', '2026-09-18 08:40:01'),
(3, 'RM-0003', 'Citra', 19, 'P', 'Jl. Cendrawasih No. 8, Tangerang', 'DBD Positif', '2026-09-19 08:40:01'),
(4, 'RM-0004', 'Dedi', 45, 'L', 'Jl. Kenanga No. 15, Bekasi', 'DBD Negatif', '2026-09-20 08:40:01'),
(5, 'RM-0005', 'Eka', 12, 'P', 'Jl. Flamboyan No. 2, Bogor', 'DBD Positif', '2026-09-21 08:40:01'),
(6, 'RM-0006', 'Fajar', 28, 'L', 'Jl. Anggrek No. 19, Bandung', 'DBD Negatif', '2026-09-22 08:40:01'),
(7, 'RM-0007', 'Gita', 35, 'P', 'Jl. Dahlia No. 7, Surabaya', 'DBD Positif', '2026-09-23 08:40:01'),
(8, 'RM-0008', 'Hadi', 22, 'L', 'Jl. Teratai No. 21, Semarang', 'DBD Negatif', '2026-09-24 08:40:01'),
(9, 'RM-0009', 'Indah', 17, 'P', 'Jl. Kamboja No. 33, Yogyakarta', 'DBD Positif', '2026-09-25 08:40:01'),
(10, 'RM-0010', 'Joko', 50, 'L', 'Jl. Bougenville No. 11, Solo', 'DBD Negatif', '2026-09-26 08:40:01'),
(11, 'RM-0011', 'Husnul', 29, 'P', 'Jl. Cemara No. 5, Malang', 'DBD Negatif', '2026-09-27 08:40:01'),
(12, 'RM-0012', 'nabila', 21, 'P', 'Jl. Beringin No. 14, Denpasar', 'DBD Positif', '2026-09-28 08:40:01'),
(13, 'RM-0013', 'manda', 26, 'P', 'Jl. Garuda No. 9, Medan', 'DBD Positif', '2026-09-29 08:40:01'),
(14, 'RM-0014', 'Rian Pratama', 27, 'L', 'Jl. Kesehatan No. 45', 'Belum Terdiagnosis', '2026-10-01 15:50:26'),
(15, 'RM-0099', 'Rian Pratama Test', 28, 'L', 'Jl. Diponegoro No. 10', 'Belum Terdiagnosis', '2026-10-01 15:53:02'),
(16, 'RM-P01', 'husnul', 20, 'P', 'meunasah dayah', 'Belum Terdiagnosis', '2026-10-01 15:55:38'),
(17, 'RM-P01', 'husnul', 20, 'L', 'meunasah dayah', 'Belum Terdiagnosis', '2026-10-01 16:06:45');

-- ------------------------------------------------------------------------------
-- 7. Table structure & data: `pasien_gejala`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `pasien_gejala`;
CREATE TABLE `pasien_gejala` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pasien` int NOT NULL,
  `id_gejala` int NOT NULL,
  `nilai_user` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pasien` (`id_pasien`),
  KEY `id_gejala` (`id_gejala`),
  CONSTRAINT `pasien_gejala_ibfk_1` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`),
  CONSTRAINT `pasien_gejala_ibfk_2` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`)
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8mb4;

INSERT INTO `pasien_gejala` (`id`, `id_pasien`, `id_gejala`, `nilai_user`) VALUES
(1,1,1,0.80),(2,1,2,0.60),(3,1,3,0.60),(4,1,4,0.80),(5,1,5,1.00),(6,1,6,0.40),(7,1,7,0.00),(8,1,8,0.00),(9,1,9,0.80),(10,1,10,0.00),(11,1,11,0.00),(12,1,12,1.00),(13,1,13,0.80),(14,1,14,0.60),(15,1,15,0.00),(16,1,16,0.00),
(17,2,1,0.60),(18,2,2,0.40),(19,2,3,0.00),(20,2,4,0.40),(21,2,5,0.00),(22,2,6,0.40),(23,2,7,0.00),(24,2,8,0.00),(25,2,9,0.00),(26,2,10,0.00),(27,2,11,0.00),(28,2,12,0.00),(29,2,13,0.00),(30,2,14,0.00),(31,2,15,0.00),(32,2,16,0.00),
(33,3,1,1.00),(34,3,2,0.80),(35,3,3,0.20),(36,3,4,0.80),(37,3,5,0.80),(38,3,6,0.80),(39,3,7,1.00),(40,3,8,0.80),(41,3,9,0.80),(42,3,10,0.60),(43,3,11,1.00),(44,3,12,1.00),(45,3,13,1.00),(46,3,14,0.80),(47,3,15,0.00),(48,3,16,0.00),
(49,4,1,0.40),(50,4,2,0.60),(51,4,3,0.00),(52,4,4,0.00),(53,4,5,0.00),(54,4,6,0.60),(55,4,7,0.00),(56,4,8,0.00),(57,4,9,0.00),(58,4,10,0.00),(59,4,11,0.00),(60,4,12,0.00),(61,4,13,0.00),(62,4,14,0.00),(63,4,15,0.00),(64,4,16,0.00),
(65,5,1,0.80),(66,5,2,0.60),(67,5,3,0.60),(68,5,4,0.60),(69,5,5,0.40),(70,5,6,0.40),(71,5,7,0.00),(72,5,8,0.40),(73,5,9,0.60),(74,5,10,0.00),(75,5,11,0.00),(76,5,12,0.60),(77,5,13,0.40),(78,5,14,0.40),(79,5,15,1.00),(80,5,16,0.20),
(81,6,1,0.00),(82,6,2,0.80),(83,6,3,0.40),(84,6,4,0.00),(85,6,5,0.00),(86,6,6,0.60),(87,6,7,0.00),(88,6,8,0.00),(89,6,9,0.00),(90,6,10,0.00),(91,6,11,0.00),(92,6,12,0.00),(93,6,13,0.00),(94,6,14,0.00),(95,6,15,0.00),(96,6,16,0.00),
(97,7,1,0.80),(98,7,2,0.40),(99,7,3,0.20),(100,7,4,0.60),(101,7,5,0.00),(102,7,6,0.40),(103,7,7,0.00),(104,7,8,0.40),(105,7,9,0.20),(106,7,10,0.00),(107,7,11,0.00),(108,7,12,0.80),(109,7,13,0.60),(110,7,14,0.60),(111,7,15,0.00),(112,7,16,0.00),
(113,8,1,1.00),(114,8,2,0.60),(115,8,3,0.00),(116,8,4,0.40),(117,8,5,0.00),(118,8,6,0.80),(119,8,7,0.00),(120,8,8,0.60),(121,8,9,0.00),(122,8,10,0.00),(123,8,11,0.00),(124,8,12,0.00),(125,8,13,0.00),(126,8,14,0.00),(127,8,15,0.00),(128,8,16,0.00),
(129,9,1,1.00),(130,9,2,0.80),(131,9,3,0.80),(132,9,4,0.80),(133,9,5,1.00),(134,9,6,0.60),(135,9,7,0.60),(136,9,8,0.60),(137,9,9,0.80),(138,9,10,0.40),(139,9,11,0.00),(140,9,12,1.00),(141,9,13,0.80),(142,9,14,0.60),(143,9,15,1.00),(144,9,16,0.80),
(145,10,1,0.00),(146,10,2,0.00),(147,10,3,0.00),(148,10,4,0.40),(149,10,5,0.00),(150,10,6,0.00),(151,10,7,0.00),(152,10,8,0.00),(153,10,9,0.00),(154,10,10,0.00),(155,10,11,0.00),(156,10,12,0.00),(157,10,13,0.00),(158,10,14,0.00),(159,10,15,0.00),(160,10,16,0.00),
(161,11,1,0.00),(162,11,2,0.00),(163,11,3,0.80),(164,11,4,0.80),(165,11,5,1.00),(166,11,6,0.00),(167,11,7,0.60),(168,11,8,0.00),(169,11,9,0.00),(170,11,10,0.60),(171,11,11,0.00),(172,11,12,1.00),(173,11,13,0.80),(174,11,14,0.60),(175,11,15,1.00),(176,11,16,0.00),
(177,12,1,0.00),(178,12,2,0.80),(179,12,3,0.80),(180,12,4,0.00),(181,12,5,0.00),(182,12,6,1.00),(183,12,7,0.00),(184,12,8,0.00),(185,12,9,0.00),(186,12,10,0.60),(187,12,11,0.00),(188,12,12,1.00),(189,12,13,0.00),(190,12,14,0.00),(191,12,15,0.00),(192,12,16,0.00),
(193,13,1,0.00),(194,13,2,0.00),(195,13,3,0.00),(196,13,4,0.00),(197,13,5,0.00),(198,13,6,0.00),(199,13,7,0.00),(200,13,8,0.00),(201,13,9,0.00),(202,13,10,0.00),(203,13,11,0.00),(204,13,12,1.00),(205,13,13,0.80),(206,13,14,0.60),(207,13,15,1.00),(208,13,16,0.00),
(209,14,1,1.00),(210,14,5,0.80),(211,14,7,0.80),(212,14,8,0.80),(213,14,9,0.80),(214,14,11,1.00),(215,14,12,1.00),(216,14,13,1.00),(217,14,15,1.00),
(218,15,1,1.00),(219,15,5,0.80),(220,15,7,0.80),(221,15,8,0.80),(222,15,9,0.80),(223,15,11,1.00),(224,15,12,1.00),(225,15,13,1.00),(226,15,15,1.00),
(227,16,1,0.00),(228,16,2,0.00),(229,16,3,0.00),(230,16,4,0.00),(231,16,5,0.00),(232,16,6,0.00),(233,16,7,0.00),(234,16,8,0.00),(235,16,9,0.00),(236,16,10,0.00),(237,16,11,0.00),(238,16,12,0.00),(239,16,13,0.00),(240,16,14,0.00),(241,16,15,0.00),(242,16,16,0.00),
(243,17,1,0.00),(244,17,2,0.00),(245,17,3,0.00),(246,17,4,0.00),(247,17,5,0.00),(248,17,6,0.00),(249,17,7,0.00),(250,17,8,0.00),(251,17,9,0.00),(252,17,10,0.00),(253,17,11,0.00),(254,17,12,0.00),(255,17,13,0.00),(256,17,14,0.00),(257,17,15,0.00),(258,17,16,0.00);

-- ------------------------------------------------------------------------------
-- 8. Table structure & data: `hasil_diagnosis`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `hasil_diagnosis`;
CREATE TABLE `hasil_diagnosis` (
  `id_hasil` int NOT NULL AUTO_INCREMENT,
  `id_pasien` int NOT NULL,
  `nilai_cf_akhir` decimal(8,6) NOT NULL,
  `persentase` decimal(5,2) NOT NULL,
  `interpretasi` varchar(100) NOT NULL,
  `derajat_dbd` varchar(150) DEFAULT NULL,
  `rekomendasi` text NOT NULL,
  `rules_terpenuhi` text,
  `prediksi_sistem` varchar(50) NOT NULL,
  `kesesuaian` varchar(50) DEFAULT NULL,
  `detail_perhitungan` longtext,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hasil`),
  KEY `id_pasien` (`id_pasien`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO `hasil_diagnosis` (`id_hasil`, `id_pasien`, `nilai_cf_akhir`, `persentase`, `interpretasi`, `derajat_dbd`, `rekomendasi`, `rules_terpenuhi`, `prediksi_sistem`, `kesesuaian`, `detail_perhitungan`, `created_at`) VALUES
(1, 1, 0.999970, 100.00, 'Sangat mungkin DBD', 'DBD Derajat I (Kriteria WHO Terpenuhi)', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R1: Kriteria WHO terpenuhi (Demam + petekie + tourniquet + trombositopenia)', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.8,\"item_cf\":0.64,\"cf_sebelum\":0,\"cf_sesudah\":0.64},{\"kode\":\"G02\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.64,\"cf_sesudah\":0.7696},{\"kode\":\"G03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.7696,\"cf_sesudah\":0.852544},{\"kode\":\"G04\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.852544,\"cf_sesudah\":0.935119},{\"kode\":\"G05\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.935119,\"cf_sesudah\":0.993512},{\"kode\":\"G06\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.5,\"item_cf\":0.2,\"cf_sebelum\":0.993512,\"cf_sesudah\":0.99481},{\"kode\":\"G09\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.99481,\"cf_sesudah\":0.998339},{\"kode\":\"L01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.998339,\"cf_sesudah\":0.999834},{\"kode\":\"L02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.999834,\"cf_sesudah\":0.999953},{\"kode\":\"L03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.999953,\"cf_sesudah\":0.99997}]', '2026-09-17 08:40:01'),
(2, 2, 0.772365, 77.24, 'Kemungkinan DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Observasi klinis ketat, istirahat tirah baring, rehidrasi cairan oral adekuat, dan ulangi pemeriksaan trombosit & hematokrit setiap 12-24 jam.', '', 'DBD Positif', 'Tidak Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.8,\"item_cf\":0.48,\"cf_sebelum\":0,\"cf_sesudah\":0.48},{\"kode\":\"G02\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.6,\"item_cf\":0.24,\"cf_sebelum\":0.48,\"cf_sesudah\":0.6048},{\"kode\":\"G04\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.7,\"item_cf\":0.28,\"cf_sebelum\":0.6048,\"cf_sesudah\":0.715456},{\"kode\":\"G06\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.5,\"item_cf\":0.2,\"cf_sebelum\":0.715456,\"cf_sesudah\":0.772365}]', '2026-09-18 08:40:01'),
(3, 3, 1.000000, 100.00, 'Sangat mungkin DBD', 'DBD Derajat III/IV (Dengue Shock Syndrome / DSS)', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R4: Syok klinis/kegawatdaruratan (G11)', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.8,\"item_cf\":0.8,\"cf_sebelum\":0,\"cf_sesudah\":0.8},{\"kode\":\"G02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0.8,\"cf_sesudah\":0.896},{\"kode\":\"G03\",\"rating\":\"Tidak tahu\",\"user_cf\":0.2,\"expert_cf\":0.6,\"item_cf\":0.12,\"cf_sebelum\":0.896,\"cf_sesudah\":0.90848},{\"kode\":\"G04\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.90848,\"cf_sesudah\":0.959731},{\"kode\":\"G05\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.959731,\"cf_sesudah\":0.988725},{\"kode\":\"G06\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.5,\"item_cf\":0.4,\"cf_sebelum\":0.988725,\"cf_sesudah\":0.993235},{\"kode\":\"G07\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.85,\"item_cf\":0.85,\"cf_sebelum\":0.993235,\"cf_sesudah\":0.998985},{\"kode\":\"G08\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.998985,\"cf_sesudah\":0.999553},{\"kode\":\"G09\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.999553,\"cf_sesudah\":0.999857},{\"kode\":\"G10\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.999857,\"cf_sesudah\":0.999909},{\"kode\":\"G11\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.999909,\"cf_sesudah\":0.999995},{\"kode\":\"L01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999995,\"cf_sesudah\":1},{\"kode\":\"L02\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":1,\"cf_sesudah\":1},{\"kode\":\"L03\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":1,\"cf_sesudah\":1}]', '2026-09-19 08:40:01'),
(4, 4, 0.695360, 69.54, 'Kemungkinan DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Observasi klinis ketat, istirahat tirah baring, rehidrasi cairan oral adekuat, dan ulangi pemeriksaan trombosit & hematokrit setiap 12-24 jam.', '', 'DBD Positif', 'Tidak Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.8,\"item_cf\":0.32,\"cf_sebelum\":0,\"cf_sesudah\":0.32},{\"kode\":\"G02\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.32,\"cf_sesudah\":0.5648},{\"kode\":\"G06\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.5,\"item_cf\":0.3,\"cf_sebelum\":0.5648,\"cf_sesudah\":0.69536}]', '2026-09-20 08:40:01'),
(5, 5, 0.999858, 99.99, 'Sangat mungkin DBD', 'DBD Derajat I (Kriteria WHO Terpenuhi)', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R1: Kriteria WHO terpenuhi (Demam + petekie + tourniquet + trombositopenia); R5: Konfirmasi laboratoris infeksi dengue (NS1 / Serologi)', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.8,\"item_cf\":0.64,\"cf_sebelum\":0,\"cf_sesudah\":0.64},{\"kode\":\"G02\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.64,\"cf_sesudah\":0.7696},{\"kode\":\"G03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.7696,\"cf_sesudah\":0.852544},{\"kode\":\"G04\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.7,\"item_cf\":0.42,\"cf_sebelum\":0.852544,\"cf_sesudah\":0.914476},{\"kode\":\"G05\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.9,\"item_cf\":0.36,\"cf_sebelum\":0.914476,\"cf_sesudah\":0.945264},{\"kode\":\"G06\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.5,\"item_cf\":0.2,\"cf_sebelum\":0.945264,\"cf_sesudah\":0.956211},{\"kode\":\"G08\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.7,\"item_cf\":0.28,\"cf_sebelum\":0.956211,\"cf_sesudah\":0.968472},{\"kode\":\"G09\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.85,\"item_cf\":0.51,\"cf_sebelum\":0.968472,\"cf_sesudah\":0.984551},{\"kode\":\"L01\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.9,\"item_cf\":0.54,\"cf_sebelum\":0.984551,\"cf_sesudah\":0.992894},{\"kode\":\"L02\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.9,\"item_cf\":0.36,\"cf_sebelum\":0.992894,\"cf_sesudah\":0.995452},{\"kode\":\"L03\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.6,\"item_cf\":0.24,\"cf_sebelum\":0.995452,\"cf_sesudah\":0.996543},{\"kode\":\"L04\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.996543,\"cf_sesudah\":0.999827},{\"kode\":\"L05\",\"rating\":\"Tidak tahu\",\"user_cf\":0.2,\"expert_cf\":0.9,\"item_cf\":0.18,\"cf_sebelum\":0.999827,\"cf_sesudah\":0.999858}]', '2026-09-21 08:40:01'),
(6, 6, 0.723360, 72.34, 'Kemungkinan DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Observasi klinis ketat, istirahat tirah baring, rehidrasi cairan oral adekuat, dan ulangi pemeriksaan trombosit & hematokrit setiap 12-24 jam.', '', 'DBD Positif', 'Tidak Sesuai', '[{\"kode\":\"G02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0,\"cf_sesudah\":0.48},{\"kode\":\"G03\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.6,\"item_cf\":0.24,\"cf_sebelum\":0.48,\"cf_sesudah\":0.6048},{\"kode\":\"G06\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.5,\"item_cf\":0.3,\"cf_sebelum\":0.6048,\"cf_sesudah\":0.72336}]', '2026-09-22 08:40:01'),
(7, 7, 0.994497, 99.45, 'Sangat mungkin DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', '', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.8,\"item_cf\":0.64,\"cf_sebelum\":0,\"cf_sesudah\":0.64},{\"kode\":\"G02\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.6,\"item_cf\":0.24,\"cf_sebelum\":0.64,\"cf_sesudah\":0.7264},{\"kode\":\"G03\",\"rating\":\"Tidak tahu\",\"user_cf\":0.2,\"expert_cf\":0.6,\"item_cf\":0.12,\"cf_sebelum\":0.7264,\"cf_sesudah\":0.759232},{\"kode\":\"G04\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.7,\"item_cf\":0.42,\"cf_sebelum\":0.759232,\"cf_sesudah\":0.860355},{\"kode\":\"G06\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.5,\"item_cf\":0.2,\"cf_sebelum\":0.860355,\"cf_sesudah\":0.888284},{\"kode\":\"G08\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.7,\"item_cf\":0.28,\"cf_sebelum\":0.888284,\"cf_sesudah\":0.919564},{\"kode\":\"G09\",\"rating\":\"Tidak tahu\",\"user_cf\":0.2,\"expert_cf\":0.85,\"item_cf\":0.17,\"cf_sebelum\":0.919564,\"cf_sesudah\":0.933238},{\"kode\":\"L01\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.933238,\"cf_sesudah\":0.981307},{\"kode\":\"L02\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.9,\"item_cf\":0.54,\"cf_sebelum\":0.981307,\"cf_sesudah\":0.991401},{\"kode\":\"L03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.991401,\"cf_sesudah\":0.994497}]', '2026-09-23 08:40:01'),
(8, 8, 0.967928, 96.79, 'Sangat mungkin DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', '', 'DBD Positif', 'Tidak Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.8,\"item_cf\":0.8,\"cf_sebelum\":0,\"cf_sesudah\":0.8},{\"kode\":\"G02\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.8,\"cf_sesudah\":0.872},{\"kode\":\"G04\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.7,\"item_cf\":0.28,\"cf_sebelum\":0.872,\"cf_sesudah\":0.90784},{\"kode\":\"G06\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.5,\"item_cf\":0.4,\"cf_sebelum\":0.90784,\"cf_sesudah\":0.944704},{\"kode\":\"G08\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.7,\"item_cf\":0.42,\"cf_sebelum\":0.944704,\"cf_sesudah\":0.967928}]', '2026-09-24 08:40:01'),
(9, 9, 1.000000, 100.00, 'Sangat mungkin DBD', 'DBD Derajat II', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R3: Perdarahan spontan + trombositopenia + hemokonsentrasi; R5: Konfirmasi laboratoris infeksi dengue (NS1 / Serologi)', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.8,\"item_cf\":0.8,\"cf_sebelum\":0,\"cf_sesudah\":0.8},{\"kode\":\"G02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0.8,\"cf_sesudah\":0.896},{\"kode\":\"G03\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0.896,\"cf_sesudah\":0.94592},{\"kode\":\"G04\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.94592,\"cf_sesudah\":0.976205},{\"kode\":\"G05\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.976205,\"cf_sesudah\":0.99762},{\"kode\":\"G06\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.5,\"item_cf\":0.3,\"cf_sebelum\":0.99762,\"cf_sesudah\":0.998334},{\"kode\":\"G07\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.85,\"item_cf\":0.51,\"cf_sebelum\":0.998334,\"cf_sesudah\":0.999184},{\"kode\":\"G08\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.7,\"item_cf\":0.42,\"cf_sebelum\":0.999184,\"cf_sesudah\":0.999527},{\"kode\":\"G09\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.999527,\"cf_sesudah\":0.999849},{\"kode\":\"G10\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.6,\"item_cf\":0.24,\"cf_sebelum\":0.999849,\"cf_sesudah\":0.999885},{\"kode\":\"L01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999885,\"cf_sesudah\":0.999988},{\"kode\":\"L02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.999988,\"cf_sesudah\":0.999997},{\"kode\":\"L03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.999997,\"cf_sesudah\":0.999998},{\"kode\":\"L04\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.999998,\"cf_sesudah\":1},{\"kode\":\"L05\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":1,\"cf_sesudah\":1}]', '2026-09-25 08:40:01'),
(10, 10, 0.280000, 28.00, 'Kemungkinan kecil DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Kemungkinan kecil DBD. Berikan terapi simtomatik, pantau perkembangan gejala, dan segera periksa kembali bila demam menetap lebih dari 3 hari.', '', 'DBD Negatif', 'Sesuai', '[{\"kode\":\"G04\",\"rating\":\"Sedikit yakin\",\"user_cf\":0.4,\"expert_cf\":0.7,\"item_cf\":0.28,\"cf_sebelum\":0,\"cf_sesudah\":0.28}]', '2026-09-26 08:40:01'),
(11, 11, 0.999994, 100.00, 'Sangat mungkin DBD', 'DBD Derajat II', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R3: Perdarahan spontan + trombositopenia + hemokonsentrasi; R5: Konfirmasi laboratoris infeksi dengue (NS1 / Serologi)', 'DBD Positif', 'Tidak Sesuai', '[{\"kode\":\"G03\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0,\"cf_sesudah\":0.48},{\"kode\":\"G04\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.48,\"cf_sesudah\":0.7712},{\"kode\":\"G05\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.7712,\"cf_sesudah\":0.97712},{\"kode\":\"G07\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.85,\"item_cf\":0.51,\"cf_sebelum\":0.97712,\"cf_sesudah\":0.988789},{\"kode\":\"G10\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.988789,\"cf_sesudah\":0.992825},{\"kode\":\"L01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.992825,\"cf_sesudah\":0.999282},{\"kode\":\"L02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.999282,\"cf_sesudah\":0.999799},{\"kode\":\"L03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.999799,\"cf_sesudah\":0.999871},{\"kode\":\"L04\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.999871,\"cf_sesudah\":0.999994}]', '2026-09-27 08:40:01'),
(12, 12, 0.991347, 99.13, 'Sangat mungkin DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', '', 'DBD Positif', 'Sesuai', '[{\"kode\":\"G02\",\"rating\":\"yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0,\"cf_sesudah\":0.48},{\"kode\":\"G03\",\"rating\":\"yakin\",\"user_cf\":0.8,\"expert_cf\":0.6,\"item_cf\":0.48,\"cf_sebelum\":0.48,\"cf_sesudah\":0.7296},{\"kode\":\"G06\",\"rating\":\"sangat yakin\",\"user_cf\":1,\"expert_cf\":0.5,\"item_cf\":0.5,\"cf_sebelum\":0.7296,\"cf_sesudah\":0.8648},{\"kode\":\"G10\",\"rating\":\"cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.8648,\"cf_sesudah\":0.913472},{\"kode\":\"L01\",\"rating\":\"sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.913472,\"cf_sesudah\":0.991347}]', '2026-09-28 08:40:01'),
(13, 13, 0.999104, 99.91, 'Sangat mungkin DBD', 'Non-DBD / Belum Memenuhi Kriteria WHO', 'Rujuk segera untuk konfirmasi laboratorium lanjutan (NS1 / IgM-IgG), rawat inap dan pemantauan tanda-tanda vital serta cairan intravena.', 'R5: Konfirmasi laboratoris infeksi dengue (NS1 / Serologi)', 'DBD Positif', 'Sesuai', '[{\"kode\":\"L01\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0,\"cf_sesudah\":0.9},{\"kode\":\"L02\",\"rating\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.9,\"cf_sesudah\":0.972},{\"kode\":\"L03\",\"rating\":\"Cukup yakin\",\"user_cf\":0.6,\"expert_cf\":0.6,\"item_cf\":0.36,\"cf_sebelum\":0.972,\"cf_sesudah\":0.98208},{\"kode\":\"L04\",\"rating\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.98208,\"cf_sesudah\":0.999104}]', '2026-09-29 08:40:01'),
(14, 14, 1.000000, 100.00, 'Sangat mungkin DBD', 'DBD Derajat III / IV (Dengue Shock Syndrome - DSS)', 'Rujuk segera ke fasilitas kesehatan tingkat lanjut / pertimbangkan rawat inap (opname). Lakukan konfirmasi laboratorium lanjutan (Antigen NS1 / Serologi IgM-IgG), pemasangan infus cairan kristaloid isotonik, dan monitoring tanda-tanda vital serta urin output secara ketat. PERHATIAN: Ditemukan 5 tanda bahaya klinis (warning signs). Lakukan intervensi medis prioritas!', 'R4: Tanda syok klinis (G11) terpenuhi; R5: Konfirmasi laboratoris dini (Antigen NS1 Dengue Positif)', 'DBD Positif', NULL, '[{\"kode\":\"G01\",\"nama\":\"Demam tinggi mendadak, 2-7 hari\",\"kategori\":\"klinis\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.8,\"item_cf\":0.8,\"cf_sebelum\":0,\"cf_sesudah\":0.8,\"formula\":\"CF1 = CF_user (1.0) \\u00d7 CF_pakar (0.80) = 0.8000\"},{\"kode\":\"G05\",\"nama\":\"Ruam kulit \\/ petekie\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.8,\"cf_sesudah\":0.944,\"formula\":\"CF_kombinasi = 0.8000 + 0.7200 \\u00d7 (1 - 0.8000) = 0.944000\"},{\"kode\":\"G07\",\"nama\":\"Perdarahan gusi \\/ hidung (epistaksis)\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.944,\"cf_sesudah\":0.98208,\"formula\":\"CF_kombinasi = 0.9440 + 0.6800 \\u00d7 (1 - 0.9440) = 0.982080\"},{\"kode\":\"G08\",\"nama\":\"Nyeri ulu hati \\/ abdomen\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.98208,\"cf_sesudah\":0.992115,\"formula\":\"CF_kombinasi = 0.9821 + 0.5600 \\u00d7 (1 - 0.9821) = 0.992115\"},{\"kode\":\"G09\",\"nama\":\"Uji tourniquet (Rumple Leede) positif\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.992115,\"cf_sesudah\":0.997477,\"formula\":\"CF_kombinasi = 0.9921 + 0.6800 \\u00d7 (1 - 0.9921) = 0.997477\"},{\"kode\":\"G11\",\"nama\":\"Tanda syok (akral dingin, nadi lemah, hipotensi)\",\"kategori\":\"klinis\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.997477,\"cf_sesudah\":0.999874,\"formula\":\"CF_kombinasi = 0.9975 + 0.9500 \\u00d7 (1 - 0.9975) = 0.999874\"},{\"kode\":\"L01\",\"nama\":\"Trombosit (< 100.000 \\/uL)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999874,\"cf_sesudah\":0.999987,\"formula\":\"CF_kombinasi = 0.9999 + 0.9000 \\u00d7 (1 - 0.9999) = 0.999987\"},{\"kode\":\"L02\",\"nama\":\"Hematokrit (Naik >= 20% dari baseline)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999987,\"cf_sesudah\":0.999999,\"formula\":\"CF_kombinasi = 1.0000 + 0.9000 \\u00d7 (1 - 1.0000) = 0.999999\"},{\"kode\":\"L04\",\"nama\":\"Antigen NS1 (Positif)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.999999,\"cf_sesudah\":1,\"formula\":\"CF_kombinasi = 1.0000 + 0.9500 \\u00d7 (1 - 1.0000) = 1.000000\"}]', '2026-10-01 15:50:26'),
(15, 15, 1.000000, 100.00, 'Sangat mungkin DBD', 'DBD Derajat III / IV (Dengue Shock Syndrome - DSS)', 'Rujuk segera ke fasilitas kesehatan tingkat lanjut / pertimbangkan rawat inap (opname). Lakukan konfirmasi laboratorium lanjutan (Antigen NS1 / Serologi IgM-IgG), pemasangan infus cairan kristaloid isotonik, dan monitoring tanda-tanda vital serta urin output secara ketat. PERHATIAN: Ditemukan 5 tanda bahaya klinis (warning signs). Lakukan intervensi medis prioritas!', 'R4: Tanda syok klinis (G11) terpenuhi; R5: Konfirmasi laboratoris dini (Antigen NS1 Dengue Positif)', 'DBD Positif', NULL, '[{\"kode\":\"G01\",\"nama\":\"Demam tinggi mendadak, 2-7 hari\",\"kategori\":\"klinis\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.8,\"item_cf\":0.8,\"cf_sebelum\":0,\"cf_sesudah\":0.8,\"formula\":\"CF1 = CF_user (1.0) \\u00d7 CF_pakar (0.80) = 0.8000\"},{\"kode\":\"G05\",\"nama\":\"Ruam kulit \\/ petekie\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.9,\"item_cf\":0.72,\"cf_sebelum\":0.8,\"cf_sesudah\":0.944,\"formula\":\"CF_kombinasi = 0.8000 + 0.7200 \\u00d7 (1 - 0.8000) = 0.944000\"},{\"kode\":\"G07\",\"nama\":\"Perdarahan gusi \\/ hidung (epistaksis)\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.944,\"cf_sesudah\":0.98208,\"formula\":\"CF_kombinasi = 0.9440 + 0.6800 \\u00d7 (1 - 0.9440) = 0.982080\"},{\"kode\":\"G08\",\"nama\":\"Nyeri ulu hati \\/ abdomen\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.7,\"item_cf\":0.56,\"cf_sebelum\":0.98208,\"cf_sesudah\":0.992115,\"formula\":\"CF_kombinasi = 0.9821 + 0.5600 \\u00d7 (1 - 0.9821) = 0.992115\"},{\"kode\":\"G09\",\"nama\":\"Uji tourniquet (Rumple Leede) positif\",\"kategori\":\"klinis\",\"rating_text\":\"Yakin\",\"user_cf\":0.8,\"expert_cf\":0.85,\"item_cf\":0.68,\"cf_sebelum\":0.992115,\"cf_sesudah\":0.997477,\"formula\":\"CF_kombinasi = 0.9921 + 0.6800 \\u00d7 (1 - 0.9921) = 0.997477\"},{\"kode\":\"G11\",\"nama\":\"Tanda syok (akral dingin, nadi lemah, hipotensi)\",\"kategori\":\"klinis\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.997477,\"cf_sesudah\":0.999874,\"formula\":\"CF_kombinasi = 0.9975 + 0.9500 \\u00d7 (1 - 0.9975) = 0.999874\"},{\"kode\":\"L01\",\"nama\":\"Trombosit (< 100.000 \\/uL)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999874,\"cf_sesudah\":0.999987,\"formula\":\"CF_kombinasi = 0.9999 + 0.9000 \\u00d7 (1 - 0.9999) = 0.999987\"},{\"kode\":\"L02\",\"nama\":\"Hematokrit (Naik >= 20% dari baseline)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.9,\"item_cf\":0.9,\"cf_sebelum\":0.999987,\"cf_sesudah\":0.999999,\"formula\":\"CF_kombinasi = 1.0000 + 0.9000 \\u00d7 (1 - 1.0000) = 0.999999\"},{\"kode\":\"L04\",\"nama\":\"Antigen NS1 (Positif)\",\"kategori\":\"lab\",\"rating_text\":\"Sangat yakin\",\"user_cf\":1,\"expert_cf\":0.95,\"item_cf\":0.95,\"cf_sebelum\":0.999999,\"cf_sesudah\":1,\"formula\":\"CF_kombinasi = 1.0000 + 0.9500 \\u00d7 (1 - 1.0000) = 1.000000\"}]', '2026-10-01 15:53:02'),
(16, 16, 0.000000, 0.00, 'Tidak ada indikasi (belum ada data)', 'Non-DBD / Suspek Ringan', 'Lengkapi pengisian data gejala klinis dan parameter laboratorium pada formulir diagnosis untuk mendapatkan hasil inferensi Certainty Factor.', '', 'DBD Negatif', NULL, '[]', '2026-10-01 15:55:38'),
(17, 17, 0.000000, 0.00, 'Tidak ada indikasi (belum ada data)', 'Non-DBD / Suspek Ringan', 'Lengkapi pengisian data gejala klinis dan parameter laboratorium pada formulir diagnosis untuk mendapatkan hasil inferensi Certainty Factor.', '', 'DBD Negatif', NULL, '[]', '2026-10-01 16:06:45');

-- ------------------------------------------------------------------------------
-- 9. Table structure & data: `users`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','dokter') NOT NULL DEFAULT 'dokter',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Administrator Sistem', 'admin', 1, NULL, NOW()),
(2, 'dokter1', 'b3959dee9b178b030c2b8373da55a04ab3adb318edeb178953ff8b77301a360a', 'dr. Maria, Sp.A', 'dokter', 1, NULL, NOW());

SET FOREIGN_KEY_CHECKS = 1;
