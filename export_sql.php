<?php
/**
 * Export current cdss_dbd MySQL schema and data into cdss_dbd.sql
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$tables = ['penyakit', 'gejala', 'aturan', 'skala_cf', 'rule_base', 'pasien', 'pasien_gejala', 'hasil_diagnosis', 'users'];

$out = "-- phpMyAdmin SQL Dump\n";
$out .= "-- CDSS DBD Certainty Factor Database\n";
$out .= "-- Host: localhost:3306\n";
$out .= "-- Generation Time: " . date('M d, Y \a\t H:i A') . "\n";
$out .= "-- Server version: 8.0.30\n";
$out .= "-- PHP Version: 8.1.10\n\n";
$out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$out .= "START TRANSACTION;\n";
$out .= "SET time_zone = \"+00:00\";\n\n";
$out .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$out .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$out .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$out .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
$out .= "-- Database: `cdss_dbd`\n\n";

foreach ($tables as $table) {
    $out .= "-- --------------------------------------------------------\n\n";
    $out .= "-- Table structure for table `$table`\n--\n\n";
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    $out .= "DROP TABLE IF EXISTS `$table`;\n";
    $out .= $create['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
    if (!empty($rows)) {
        $out .= "-- Dumping data for table `$table`\n--\n\n";
        $cols = array_keys($rows[0]);
        $colNames = implode('`, `', $cols);
        $valStrings = [];
        foreach ($rows as $r) {
            $vals = [];
            foreach ($r as $val) {
                if ($val === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $pdo->quote($val);
                }
            }
            $valStrings[] = "(" . implode(', ', $vals) . ")";
        }
        $out .= "INSERT INTO `$table` (`$colNames`) VALUES\n" . implode(",\n", $valStrings) . ";\n\n";
    }
}

$out .= "COMMIT;\n\n";
$out .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$out .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$out .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

file_put_contents(__DIR__ . '/cdss_dbd.sql', $out);
echo "cdss_dbd.sql generated successfully!\n";
