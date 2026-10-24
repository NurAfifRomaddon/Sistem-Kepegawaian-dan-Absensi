<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Absensi';
$halaman_aktif = 'absensi';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$stmt = mysqli_prepare($koneksi, "
  SELECT a.*, k.nama_karyawan
  FROM absensi a JOIN karyawan k ON k.id_karyawan = a.id_karyawan
  WHERE a.tanggal = ?
  ORDER BY k.nama_karyawan ASC");
mysqli_stmt_bind_param($stmt, 's', $tanggal);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Absensi Karyawan</h4>
    <a href="tambah.php?tanggal=<?= $tanggal ?>" class="btn btn-primary"><i class="bi bi-list-check"></i> Input Absensi Karyawan</a>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
          <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Tampilkan</button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Nama</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status</th><th>Shift</th><th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Belum ada absensi pada tanggal ini</td></tr>
            <?php endif; ?>
            <?php while ($a = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><?= htmlspecialchars($a['nama_karyawan']) ?></td>
              <td><?= $a['jam_masuk'] ?: '-' ?></td>
              <td><?= $a['jam_keluar'] ?: '-' ?></td>
              <td><?= badge_status($a['status']) ?></td>
              <td><?= htmlspecialchars($a['shift'] ?: '-') ?></td>
              <td class="text-end">
                <a href="edit.php?id=<?= $a['id_absensi'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <a href="hapus.php?id=<?= $a['id_absensi'] ?>&tanggal=<?= $tanggal ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Hapus data absensi ini?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
