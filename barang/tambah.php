<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Tambah Barang';
$halaman_aktif = 'barang';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode   = trim($_POST['kode_barang']);
    $nama   = trim($_POST['nama_barang']);
    $satuan = trim($_POST['satuan']);
    $stok   = intval($_POST['stok']);
    $min    = intval($_POST['stok_minimum']);

    if ($kode === '' || $nama === '') {
        $error = 'Kode dan nama barang wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO barang (kode_barang, nama_barang, satuan, stok, stok_minimum)
                                           VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssii', $kode, $nama, $satuan, $stok, $min);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=Barang berhasil ditambahkan');
            exit;
        } else {
            $error = 'Gagal menyimpan (kode barang mungkin sudah dipakai).';
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Tambah Barang</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Kode Barang</label>
            <input type="text" name="kode_barang" class="form-control" required value="<?= htmlspecialchars($_POST['kode_barang'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" required value="<?= htmlspecialchars($_POST['nama_barang'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <input type="text" name="satuan" class="form-control" placeholder="Pcs / Box / Rim" value="<?= htmlspecialchars($_POST['satuan'] ?? 'Pcs') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stok Awal</label>
            <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stok Minimum</label>
            <input type="number" name="stok_minimum" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stok_minimum'] ?? '0') ?>">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
