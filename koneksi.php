<?php
/**
 * File: koneksi.php
 * Mendukung Environment Variables untuk keamanan (Cyber Security Standard)
 */

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');
$db   = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'smart_laundry');
$port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');

// Membuat koneksi database menggunakan MySQLi dengan port
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    error_log("Koneksi database gagal: " . $conn->connect_error);
    die("Maaf, sedang terjadi gangguan pada sistem.");
}

$conn->set_charset("utf8mb4");
?>