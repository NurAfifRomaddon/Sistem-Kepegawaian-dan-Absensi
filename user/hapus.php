<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id = intval($_GET['id'] ?? 0);

if ($id == $_SESSION['id_user']) {
    header('Location: index.php?error=Tidak bisa menghapus akun sendiri yang sedang login');
    exit;
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: index.php?sukses=User berhasil dihapus');
exit;
