<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Laporan Stok';
$halaman_aktif = 'laporan_stok';

$awal  = $_GET['awal']  ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-t');

$stmt = mysqli_prepare($koneksi, "
  SELECT b.kode_barang, b.nama_barang, b.satuan, b.stok AS stok_saat_ini,
    COALESCE((SELECT SUM(jumlah) FROM barang_masuk bm WHERE bm.id_barang=b.id_barang AND bm.tanggal BETWEEN ? AND ?),0) AS total_masuk,
    COALESCE((SELECT SUM(jumlah) FROM barang_keluar bk WHERE bk.id_barang=b.id_barang AND bk.tanggal BETWEEN ? AND ?),0) AS total_keluar
  FROM barang b
  ORDER BY b.nama_barang ASC");
mysqli_stmt_bind_param($stmt, 'ssss', $awal, $akhir, $awal, $akhir);
mysqli_stmt_execute($stmt);
$rekap = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Laporan Mutasi Stok Barang</h4>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-0 small">Dari Tanggal</label>
          <input type="date" name="awal" class="form-control" value="<?= htmlspecialchars($awal) ?>">
        </div>
        <div class="col-auto">
          <label class="form-label mb-0 small">Sampai Tanggal</label>
          <input type="date" name="akhir" class="form-control" value="<?= htmlspecialchars($akhir) ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary"><i class="bi bi-funnel"></i> Tampilkan</button>
        </div>
        <div class="col-auto">
          <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted mb-3">Periode: <?= tgl_indo($awal) ?> s.d. <?= tgl_indo($akhir) ?></p>
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>Kode</th><th>Nama Barang</th><th>Satuan</th>
              <th class="text-center">Total Masuk</th><th class="text-center">Total Keluar</th>
              <th class="text-center">Stok Saat Ini</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($rekap) == 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data barang</td></tr>
            <?php endif; ?>
            <?php while ($r = mysqli_fetch_assoc($rekap)): ?>
            <tr>
              <td><?= htmlspecialchars($r['kode_barang']) ?></td>
              <td><?= htmlspecialchars($r['nama_barang']) ?></td>
              <td><?= htmlspecialchars($r['satuan']) ?></td>
              <td class="text-center text-success fw-semibold">+<?= $r['total_masuk'] ?></td>
              <td class="text-center text-danger fw-semibold">-<?= $r['total_keluar'] ?></td>
              <td class="text-center fw-bold"><?= $r['stok_saat_ini'] ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
