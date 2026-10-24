<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Laporan Absensi';
$halaman_aktif = 'laporan_absensi';

$awal  = $_GET['awal']  ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-t');

// rekap per karyawan
$stmt = mysqli_prepare($koneksi, "
  SELECT k.nama_karyawan,
    SUM(CASE WHEN a.status='Hadir' THEN 1 ELSE 0 END) AS hadir,
    SUM(CASE WHEN a.status='Cuti'  THEN 1 ELSE 0 END) AS cuti,
    SUM(CASE WHEN a.status='Izin'  THEN 1 ELSE 0 END) AS izin,
    SUM(CASE WHEN a.status='Alpha' THEN 1 ELSE 0 END) AS alpha
  FROM karyawan k
  LEFT JOIN absensi a ON a.id_karyawan = k.id_karyawan AND a.tanggal BETWEEN ? AND ?
  GROUP BY k.id_karyawan
  ORDER BY k.nama_karyawan ASC");
mysqli_stmt_bind_param($stmt, 'ss', $awal, $akhir);
mysqli_stmt_execute($stmt);
$rekap = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Laporan Rekap Absensi</h4>

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
              <th>Nama Karyawan</th>
              <th class="text-center">Hadir</th><th class="text-center">Cuti</th>
              <th class="text-center">Izin</th><th class="text-center">Alpha</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($rekap) == 0): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data karyawan</td></tr>
            <?php endif; ?>
            <?php while ($r = mysqli_fetch_assoc($rekap)): ?>
            <tr>
              <td><?= htmlspecialchars($r['nama_karyawan']) ?></td>
              <td class="text-center"><?= $r['hadir'] ?></td>
              <td class="text-center"><?= $r['cuti'] ?></td>
              <td class="text-center"><?= $r['izin'] ?></td>
              <td class="text-center"><?= $r['alpha'] ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
