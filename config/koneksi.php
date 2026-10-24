<?php
/**
 * Koneksi ke database MySQL
 * Sesuaikan HOST, USER, PASS, DBNAME dengan konfigurasi server/XAMPP kamu
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simpeg_absensi_barang');

// PHP 8.1+ menjadikan error mysqli sebagai exception secara default.
// Baris ini mengembalikannya ke mode lama (return false saat error) supaya
// seluruh pengecekan "if (mysqli_stmt_execute($stmt))" di aplikasi ini
// tetap berfungsi menampilkan pesan error yang rapi, bukan crash 500.
mysqli_report(MYSQLI_REPORT_OFF);

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');
