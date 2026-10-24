<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Barang Keluar';
$halaman_aktif = 'barang_keluar';

$data = mysqli_query($koneksi, "
  SELECT bk.*, b.nama_barang, b.kode_barang, b.satuan, u.username
  FROM barang_keluar bk
  JOIN barang b ON b.id_barang = bk.id_barang
  LEFT JOIN users u ON u.id_user = bk.id_user
  ORDER BY bk.tanggal DESC, bk.id_keluar DESC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Barang Keluar</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Barang Keluar</a>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr><th>Tanggal</th><th>Kode</th><th>Barang</th><th>Jumlah</th><th>Keperluan</th><th>Dicatat Oleh</th><th class="text-end">Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat barang keluar</td></tr>
            <?php endif; ?>
            <?php while ($r = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><?= tgl_indo($r['tanggal']) ?></td>
              <td><?= htmlspecialchars($r['kode_barang']) ?></td>
              <td><?= htmlspecialchars($r['nama_barang']) ?></td>
              <td><span class="badge bg-danger">-<?= $r['jumlah'] ?> <?= htmlspecialchars($r['satuan']) ?></span></td>
              <td><?= htmlspecialchars($r['keperluan'] ?: '-') ?></td>
              <td><?= htmlspecialchars($r['username'] ?? '-') ?></td>
              <td class="text-end">
                <a href="hapus.php?id=<?= $r['id_keluar'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus catatan ini? Stok barang akan dikembalikan.')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
