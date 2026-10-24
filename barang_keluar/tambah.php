<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Catat Barang Keluar';
$halaman_aktif = 'barang_keluar';
$error = '';

$barang = mysqli_query($koneksi, "SELECT * FROM barang ORDER BY nama_barang ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang  = intval($_POST['id_barang']);
    $tanggal    = $_POST['tanggal'];
    $jumlah     = intval($_POST['jumlah']);
    $keperluan  = trim($_POST['keperluan']);
    $id_user    = $_SESSION['id_user'];

    if (!$id_barang || !$tanggal || $jumlah <= 0) {
        $error = 'Barang, tanggal, dan jumlah (lebih dari 0) wajib diisi.';
    } else {
        // cek stok tersedia sebelum diproses
        $stmt = mysqli_prepare($koneksi, "SELECT stok, nama_barang FROM barang WHERE id_barang = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id_barang);
        mysqli_stmt_execute($stmt);
        $cek = mysqli_stmt_get_result($stmt)->fetch_assoc();

        if (!$cek) {
            $error = 'Barang tidak ditemukan.';
        } elseif ($cek['stok'] < $jumlah) {
            $error = 'Stok tidak mencukupi. Stok tersedia: ' . $cek['stok'] . ' untuk ' . htmlspecialchars($cek['nama_barang']) . '.';
        } else {
            mysqli_begin_transaction($koneksi);
            try {
                $stmt2 = mysqli_prepare($koneksi, "INSERT INTO barang_keluar (id_barang, tanggal, jumlah, keperluan, id_user)
                                                    VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, 'isisi', $id_barang, $tanggal, $jumlah, $keperluan, $id_user);
                mysqli_stmt_execute($stmt2);

                $stmt3 = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
                mysqli_stmt_bind_param($stmt3, 'ii', $jumlah, $id_barang);
                mysqli_stmt_execute($stmt3);

                mysqli_commit($koneksi);
                header('Location: index.php?sukses=Barang keluar berhasil dicatat, stok diperbarui');
                exit;
            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $error = 'Gagal menyimpan transaksi barang keluar.';
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Catat Barang Keluar</h4>
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
            <label class="form-label">Jumlah Keluar</label>
            <input type="number" name="jumlah" class="form-control" min="1" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Keperluan <small class="text-muted">(mis. dipakai untuk unit/departemen apa)</small></label>
            <textarea name="keperluan" class="form-control" rows="2"></textarea>
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
