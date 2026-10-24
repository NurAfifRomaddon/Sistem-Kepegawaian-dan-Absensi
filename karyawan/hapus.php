<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "DELETE FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: index.php?sukses=Data karyawan berhasil dihapus');
exit;
