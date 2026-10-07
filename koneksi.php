<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "bosscctv";

// Mengaktifkan mode exception untuk MySQLi (Keamanan & Debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $pass, $db);
    $koneksi->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Koneksi database gagal. Silakan hubungi administrator.");
}
?>