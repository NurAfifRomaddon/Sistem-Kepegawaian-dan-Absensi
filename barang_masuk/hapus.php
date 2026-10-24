<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT * FROM barang_masuk WHERE id_masuk = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_stmt_get_result($stmt)->fetch_assoc();

if ($row) {
    mysqli_begin_transaction($koneksi);
    try {
        $stmt2 = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
        mysqli_stmt_bind_param($stmt2, 'ii', $row['jumlah'], $row['id_barang']);
        mysqli_stmt_execute($stmt2);

        $stmt3 = mysqli_prepare($koneksi, "DELETE FROM barang_masuk WHERE id_masuk = ?");
        mysqli_stmt_bind_param($stmt3, 'i', $id);
        mysqli_stmt_execute($stmt3);

        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
    }
}

header('Location: index.php?sukses=Catatan barang masuk dihapus, stok disesuaikan kembali');
exit;
