<?php
require_once 'config/koneksi.php';
require_once 'config/session.php';
cek_login();

if ($_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$judul_halaman = 'Dashboard Saya';
$halaman_aktif = 'dashboard_karyawan';

$id_karyawan = $_SESSION['id_karyawan'] ?? null;

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="content p-4">
  <div class="mb-4">
    <h4 class="fw-bold mb-0">Halo, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</h4>
    <small class="text-muted">Dashboard karyawan - absensi & pengajuan cuti/izin</small>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>

  <?php if (!$id_karyawan): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle"></i>
      Akun kamu belum dihubungkan ke data karyawan. Hubungi admin untuk menghubungkan akun ini
      supaya bisa melihat absensi dan mengajukan cuti/izin.
    </div>
  <?php else: ?>

    <?php
    $bulan_ini = date('Y-m');
    $rekap = ['Hadir'=>0,'Cuti'=>0,'Izin'=>0,'Alpha'=>0];
    $stmt = mysqli_prepare($koneksi, "SELECT status, COUNT(*) jml FROM absensi
                                       WHERE id_karyawan=? AND DATE_FORMAT(tanggal,'%Y-%m')=?
                                       GROUP BY status");
    mysqli_stmt_bind_param($stmt, 'is', $id_karyawan, $bulan_ini);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) { $rekap[$r['status']] = $r['jml']; }
    ?>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card-stat bg-hadir">
          <p><i class="bi bi-check-circle"></i> Hadir Bulan Ini</p>
          <h3><?= $rekap['Hadir'] ?></h3>
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
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-file-earmark-text"></i> Pengajuan Terbaru</span>
            <a href="pengajuan/tambah.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Ajukan</a>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th class="ps-3">Jenis</th><th>Tanggal</th><th>Status</th></tr></thead>
              <tbody>
                <?php
                $stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan WHERE id_karyawan=? ORDER BY created_at DESC LIMIT 5");
                mysqli_stmt_bind_param($stmt, 'i', $id_karyawan);
                mysqli_stmt_execute($stmt);
                $pengajuan = mysqli_stmt_get_result($stmt);
                ?>
                <?php if (mysqli_num_rows($pengajuan) == 0): ?>
                  <tr><td colspan="3" class="text-center text-muted py-3">Belum ada pengajuan</td></tr>
                <?php endif; ?>
                <?php while ($p = mysqli_fetch_assoc($pengajuan)): ?>
                <tr>
                  <td class="ps-3"><?= htmlspecialchars($p['jenis']) ?></td>
                  <td><?= tgl_indo($p['tanggal_mulai']) ?><?= $p['tanggal_mulai'] != $p['tanggal_selesai'] ? ' - ' . tgl_indo($p['tanggal_selesai']) : '' ?></td>
                  <td><?= badge_pengajuan($p['status']) ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <div class="card-footer bg-white text-end">
            <a href="pengajuan/index.php" class="small">Lihat semua pengajuan &rarr;</a>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-white fw-semibold">
            <i class="bi bi-calendar-check"></i> Riwayat Absensi Terbaru
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th class="ps-3">Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead>
              <tbody>
                <?php
                $stmt = mysqli_prepare($koneksi, "SELECT * FROM absensi WHERE id_karyawan=? ORDER BY tanggal DESC LIMIT 8");
                mysqli_stmt_bind_param($stmt, 'i', $id_karyawan);
                mysqli_stmt_execute($stmt);
                $riwayat = mysqli_stmt_get_result($stmt);
                ?>
                <?php if (mysqli_num_rows($riwayat) == 0): ?>
                  <tr><td colspan="3" class="text-center text-muted py-3">Belum ada riwayat absensi</td></tr>
                <?php endif; ?>
                <?php while ($a = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                  <td class="ps-3"><?= tgl_indo($a['tanggal']) ?></td>
                  <td><?= badge_status($a['status']) ?></td>
                  <td><?= htmlspecialchars(($a['keterangan'] ?? '') !== '' ? $a['keterangan'] : '-') ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>
<?php include 'includes/footer.php'; ?>