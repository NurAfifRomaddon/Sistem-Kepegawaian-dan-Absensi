<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_login();

$judul_halaman = 'Pengajuan Cuti/Izin';
$halaman_aktif = 'pengajuan';
$is_admin = $_SESSION['role'] === 'admin';

if ($is_admin) {
    $filter = $_GET['status'] ?? 'Menunggu';
    if ($filter === 'Semua') {
        $data = mysqli_query($koneksi, "
          SELECT p.*, k.nama_karyawan
          FROM pengajuan p JOIN karyawan k ON k.id_karyawan = p.id_karyawan
          ORDER BY p.created_at DESC");
    } else {
        $stmt = mysqli_prepare($koneksi, "
          SELECT p.*, k.nama_karyawan
          FROM pengajuan p JOIN karyawan k ON k.id_karyawan = p.id_karyawan
          WHERE p.status = ?
          ORDER BY p.created_at DESC");
        mysqli_stmt_bind_param($stmt, 's', $filter);
        mysqli_stmt_execute($stmt);
        $data = mysqli_stmt_get_result($stmt);
    }
} else {
    $id_karyawan = $_SESSION['id_karyawan'] ?? 0;
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan WHERE id_karyawan=? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $id_karyawan);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Pengajuan Cuti/Izin</h4>
    <?php if (!$is_admin): ?>
      <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajukan Cuti/Izin</a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <?php if ($is_admin): ?>
      <div class="btn-group mb-3">
        <a href="?status=Menunggu" class="btn btn-sm btn-outline-secondary <?= $filter=='Menunggu'?'active':'' ?>">Menunggu</a>
        <a href="?status=Disetujui" class="btn btn-sm btn-outline-secondary <?= $filter=='Disetujui'?'active':'' ?>">Disetujui</a>
        <a href="?status=Ditolak" class="btn btn-sm btn-outline-secondary <?= $filter=='Ditolak'?'active':'' ?>">Ditolak</a>
        <a href="?status=Semua" class="btn btn-sm btn-outline-secondary <?= $filter=='Semua'?'active':'' ?>">Semua</a>
      </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <?php if ($is_admin): ?><th>Nama</th><?php endif; ?>
              <th>Jenis</th><th>Tanggal</th><th>Alasan</th><th>Status</th>
              <?php if ($is_admin): ?><th class="text-end">Aksi</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data pengajuan</td></tr>
            <?php endif; ?>
            <?php while ($p = mysqli_fetch_assoc($data)): ?>
            <tr>
              <?php if ($is_admin): ?>
                <td><?= htmlspecialchars($p['nama_karyawan']) ?></td>
              <?php endif; ?>
              <td><?= htmlspecialchars($p['jenis']) ?></td>
              <td>
                <?= tgl_indo($p['tanggal_mulai']) ?>
                <?= $p['tanggal_mulai'] != $p['tanggal_selesai'] ? ' s.d. ' . tgl_indo($p['tanggal_selesai']) : '' ?>
              </td>
              <td><?= htmlspecialchars($p['alasan'] ?: '-') ?></td>
              <td><?= badge_pengajuan($p['status']) ?></td>
              <?php if ($is_admin): ?>
              <td class="text-end">
                <?php if ($p['status'] === 'Menunggu'): ?>
                  <a href="proses.php?id=<?= $p['id_pengajuan'] ?>&aksi=setuju" class="btn btn-sm btn-success"
                     onclick="return confirm('Setujui pengajuan ini? Absensi akan otomatis tercatat.')">Setujui</a>
                  <a href="proses.php?id=<?= $p['id_pengajuan'] ?>&aksi=tolak" class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Tolak pengajuan ini?')">Tolak</a>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
              <?php endif; ?>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
