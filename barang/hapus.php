<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "DELETE FROM barang WHERE id_barang = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: index.php?sukses=Data barang berhasil dihapus');
exit;
