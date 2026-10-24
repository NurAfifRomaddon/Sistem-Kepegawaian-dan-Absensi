<?php
require_once 'config/koneksi.php';
require_once 'config/session.php';
cek_login();

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard_karyawan.php');
    exit;
}

$judul_halaman = 'Dashboard';
$halaman_aktif = 'dashboard';

$hari_ini = date('Y-m-d');

// ringkasan absensi hari ini
$rekap = ['Hadir'=>0,'Cuti'=>0,'Izin'=>0,'Alpha'=>0];
$q = mysqli_query($koneksi, "SELECT status, COUNT(*) jml FROM absensi WHERE tanggal='$hari_ini' GROUP BY status");
while ($r = mysqli_fetch_assoc($q)) { $rekap[$r['status']] = $r['jml']; }

$total_karyawan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM karyawan WHERE status='Aktif'"))['t'];

// jumlah pengajuan cuti/izin yang masih menunggu persetujuan
$pengajuan_menunggu = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM pengajuan WHERE status='Menunggu'"))['t'];

// barang stok menipis
$stok_menipis = mysqli_query($koneksi, "SELECT * FROM barang WHERE stok <= stok_minimum ORDER BY stok ASC LIMIT 5");

// aktivitas barang masuk/keluar terakhir
$aktivitas = mysqli_query($koneksi, "
  (SELECT 'Masuk' AS jenis, bm.tanggal, b.nama_barang, bm.jumlah
     FROM barang_masuk bm JOIN barang b ON b.id_barang=bm.id_barang)
  UNION ALL
  (SELECT 'Keluar' AS jenis, bk.tanggal, b.nama_barang, bk.jumlah
     FROM barang_keluar bk JOIN barang b ON b.id_barang=bk.id_barang)
  ORDER BY tanggal DESC LIMIT 6");

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0">Dashboard</h4>
      <small class="text-muted">Ringkasan absensi & stok barang, <?= tgl_indo($hari_ini) ?></small>
    </div>
    <?php if ($pengajuan_menunggu > 0): ?>
      <a href="pengajuan/index.php" class="btn btn-warning position-relative">
        <i class="bi bi-bell"></i> Pengajuan Menunggu
        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill">
          <?= $pengajuan_menunggu ?>
        </span>
      </a>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card-stat bg-hadir">
        <p><i class="bi bi-check-circle"></i> Hadir Hari Ini</p>
        <h3><?= $rekap['Hadir'] ?> <small style="font-size:14px;">/ <?= $total_karyawan ?> karyawan</small></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card-stat bg-cuti">
        <p><i class="bi bi-airplane"></i> Cuti</p>
        <h3><?= $rekap['Cuti'] ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card-stat bg-izin">
        <p><i class="bi bi-file-earmark-medical"></i> Izin</p>
        <h3><?= $rekap['Izin'] ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card-stat bg-alpha">
        <p><i class="bi bi-x-circle"></i> Alpha</p>
        <h3><?= $rekap['Alpha'] ?></h3>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-exclamation-triangle text-danger"></i> Stok Barang Menipis
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th class="ps-3">Barang</th><th>Stok</th><th>Min.</th></tr></thead>
            <tbody>
              <?php if (mysqli_num_rows($stok_menipis) == 0): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">Semua stok aman</td></tr>
              <?php endif; ?>
              <?php while ($b = mysqli_fetch_assoc($stok_menipis)): ?>
              <tr>
                <td class="ps-3"><?= htmlspecialchars($b['nama_barang']) ?></td>
                <td><span class="badge bg-danger"><?= $b['stok'] ?> <?= htmlspecialchars($b['satuan']) ?></span></td>
                <td><?= $b['stok_minimum'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-clock-history"></i> Aktivitas Stok Terbaru
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th class="ps-3">Tanggal</th><th>Barang</th><th>Jenis</th><th>Jml</th></tr></thead>
            <tbody>
              <?php if (mysqli_num_rows($aktivitas) == 0): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada aktivitas</td></tr>
              <?php endif; ?>
              <?php while ($a = mysqli_fetch_assoc($aktivitas)): ?>
              <tr>
                <td class="ps-3"><?= tgl_indo($a['tanggal']) ?></td>
                <td><?= htmlspecialchars($a['nama_barang']) ?></td>
                <td><span class="badge bg-<?= $a['jenis']=='Masuk'?'success':'secondary' ?>"><?= $a['jenis'] ?></span></td>
                <td><?= $a['jumlah'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<?php include 'includes/footer.php'; ?>
