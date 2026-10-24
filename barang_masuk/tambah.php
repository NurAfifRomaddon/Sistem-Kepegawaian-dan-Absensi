<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Catat Barang Masuk';
$halaman_aktif = 'barang_masuk';
$error = '';

$barang = mysqli_query($koneksi, "SELECT * FROM barang ORDER BY nama_barang ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang  = intval($_POST['id_barang']);
    $tanggal    = $_POST['tanggal'];
    $jumlah     = intval($_POST['jumlah']);
    $keterangan = trim($_POST['keterangan']);
    $id_user    = $_SESSION['id_user'];

    if (!$id_barang || !$tanggal || $jumlah <= 0) {
        $error = 'Barang, tanggal, dan jumlah (lebih dari 0) wajib diisi.';
    } else {
        // transaksi: insert riwayat + update stok barang
        mysqli_begin_transaction($koneksi);
        try {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO barang_masuk (id_barang, tanggal, jumlah, keterangan, id_user)
                                               VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isisi', $id_barang, $tanggal, $jumlah, $keterangan, $id_user);
            mysqli_stmt_execute($stmt);

            $stmt2 = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
            mysqli_stmt_bind_param($stmt2, 'ii', $jumlah, $id_barang);
            mysqli_stmt_execute($stmt2);

            mysqli_commit($koneksi);
            header('Location: index.php?sukses=Barang masuk berhasil dicatat, stok diperbarui');
            exit;
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error = 'Gagal menyimpan transaksi barang masuk.';
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Catat Barang Masuk</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Barang</label>
            <select name="id_barang" class="form-select" required>
              <option value="">-- Pilih Barang --</option>
              <?php while ($b = mysqli_fetch_assoc($barang)): ?>
                <option value="<?= $b['id_barang'] ?>"><?= htmlspecialchars($b['kode_barang'] . ' - ' . $b['nama_barang'] . ' (stok saat ini: ' . $b['stok'] . ')') ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Jumlah Masuk</label>
            <input type="number" name="jumlah" class="form-control" min="1" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Keterangan <small class="text-muted">(mis. nama supplier / no. faktur)</small></label>
            <textarea name="keterangan" class="form-control" rows="2"></textarea>
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
