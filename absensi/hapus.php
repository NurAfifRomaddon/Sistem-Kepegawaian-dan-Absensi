<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id = intval($_GET['id'] ?? 0);
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$stmt = mysqli_prepare($koneksi, "DELETE FROM absensi WHERE id_absensi = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: index.php?tanggal=' . $tanggal . '&sukses=Data absensi berhasil dihapus');
exit;
