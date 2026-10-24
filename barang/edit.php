<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Edit Barang';
$halaman_aktif = 'barang';
$error = '';

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT * FROM barang WHERE id_barang = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$barang = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$barang) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode   = trim($_POST['kode_barang']);
    $nama   = trim($_POST['nama_barang']);
    $satuan = trim($_POST['satuan']);
    $min    = intval($_POST['stok_minimum']);
    // catatan: field "stok" TIDAK diedit manual di sini agar konsisten dengan
    // riwayat transaksi barang_masuk / barang_keluar. Stok hanya berubah lewat transaksi.

    if ($kode === '' || $nama === '') {
        $error = 'Kode dan nama barang wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE barang SET kode_barang=?, nama_barang=?, satuan=?, stok_minimum=? WHERE id_barang=?");
        mysqli_stmt_bind_param($stmt, 'sssii', $kode, $nama, $satuan, $min, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=Data barang berhasil diperbarui');
            exit;
        } else {
            $error = 'Gagal menyimpan perubahan (kode barang mungkin sudah dipakai).';
        }
    }
    $barang = array_merge($barang, $_POST);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Edit Barang</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Kode Barang</label>
            <input type="text" name="kode_barang" class="form-control" required value="<?= htmlspecialchars($barang['kode_barang']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" required value="<?= htmlspecialchars($barang['nama_barang']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($barang['satuan']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stok Saat Ini</label>
            <input type="text" class="form-control" value="<?= $barang['stok'] ?>" disabled>
            <small class="text-muted">Ubah lewat menu Barang Masuk / Barang Keluar</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stok Minimum</label>
            <input type="number" name="stok_minimum" class="form-control" min="0" value="<?= htmlspecialchars($barang['stok_minimum']) ?>">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
