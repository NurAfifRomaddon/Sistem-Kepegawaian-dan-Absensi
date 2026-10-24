<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Master Barang';
$halaman_aktif = 'barang';

$keyword = trim($_GET['q'] ?? '');
$stmt = mysqli_prepare($koneksi, "SELECT * FROM barang WHERE nama_barang LIKE ? OR kode_barang LIKE ? ORDER BY nama_barang ASC");
$like = "%$keyword%";
mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Master Barang</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Barang</a>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:320px;">
          <input type="text" name="q" class="form-control" placeholder="Cari kode / nama barang..." value="<?= htmlspecialchars($keyword) ?>">
          <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr><th>Kode</th><th>Nama Barang</th><th>Satuan</th><th>Stok</th><th>Stok Minimum</th><th class="text-end">Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data barang</td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><?= htmlspecialchars($b['kode_barang']) ?></td>
              <td><?= htmlspecialchars($b['nama_barang']) ?></td>
              <td><?= htmlspecialchars($b['satuan']) ?></td>
              <td>
                <span class="badge bg-<?= $b['stok'] <= $b['stok_minimum'] ? 'danger' : 'success' ?>">
                  <?= $b['stok'] ?>
                </span>
              </td>
              <td><?= $b['stok_minimum'] ?></td>
              <td class="text-end">
                <a href="edit.php?id=<?= $b['id_barang'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <a href="hapus.php?id=<?= $b['id_barang'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus barang ini beserta riwayat masuk/keluarnya?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
