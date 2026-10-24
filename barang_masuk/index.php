<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Barang Masuk';
$halaman_aktif = 'barang_masuk';

$data = mysqli_query($koneksi, "
  SELECT bm.*, b.nama_barang, b.kode_barang, b.satuan, u.username
  FROM barang_masuk bm
  JOIN barang b ON b.id_barang = bm.id_barang
  LEFT JOIN users u ON u.id_user = bm.id_user
  ORDER BY bm.tanggal DESC, bm.id_masuk DESC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Barang Masuk</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Barang Masuk</a>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr><th>Tanggal</th><th>Kode</th><th>Barang</th><th>Jumlah</th><th>Keterangan</th><th>Dicatat Oleh</th><th class="text-end">Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat barang masuk</td></tr>
            <?php endif; ?>
            <?php while ($r = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><?= tgl_indo($r['tanggal']) ?></td>
              <td><?= htmlspecialchars($r['kode_barang']) ?></td>
              <td><?= htmlspecialchars($r['nama_barang']) ?></td>
              <td><span class="badge bg-success">+<?= $r['jumlah'] ?> <?= htmlspecialchars($r['satuan']) ?></span></td>
              <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
              <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
              <td class="text-end">
                <a href="hapus.php?id=<?= $r['id_masuk'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus catatan ini? Stok barang akan dikurangi kembali.')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
