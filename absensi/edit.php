<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Edit Absensi';
$halaman_aktif = 'absensi';
$error = '';

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT a.*, k.nama_karyawan FROM absensi a
                                   JOIN karyawan k ON k.id_karyawan = a.id_karyawan
                                   WHERE a.id_absensi = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$absen = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$absen) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status      = $_POST['status'];
    $jam_masuk   = $_POST['jam_masuk'] ?? null;
    $jam_keluar  = $_POST['jam_keluar'] ?? null;
    $jam_masuk   = $jam_masuk ?: null;
    $jam_keluar  = $jam_keluar ?: null;
    $keterangan  = trim($_POST['keterangan']);

    if ($status !== 'Hadir') {
        $jam_masuk = null;
        $jam_keluar = null;
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE absensi SET status=?, jam_masuk=?, jam_keluar=?, keterangan=? WHERE id_absensi=?");
    mysqli_stmt_bind_param($stmt, 'ssssi', $status, $jam_masuk, $jam_keluar, $keterangan, $id);
    if (mysqli_stmt_execute($stmt)) {
        header('Location: index.php?tanggal=' . $absen['tanggal'] . '&sukses=Absensi berhasil diperbarui');
        exit;
    } else {
        $error = 'Gagal menyimpan perubahan.';
    }
    $absen = array_merge($absen, $_POST);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Edit Absensi</h4>

  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <p class="mb-3"><strong><?= htmlspecialchars($absen['nama_karyawan']) ?></strong><br>
      <small class="text-muted">Tanggal: <?= tgl_indo($absen['tanggal']) ?></small></p>

      <form method="POST">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" id="statusSelect" required onchange="toggleJam()">
              <?php foreach (['Hadir','Cuti','Izin','Alpha'] as $s): ?>
                <option value="<?= $s ?>" <?= $absen['status']==$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4" id="jamMasukWrap">
            <label class="form-label">Jam Masuk</label>
            <input type="time" name="jam_masuk" class="form-control" value="<?= $absen['jam_masuk'] ?>">
          </div>
          <div class="col-md-4" id="jamKeluarWrap">
            <label class="form-label">Jam Keluar</label>
            <input type="time" name="jam_keluar" class="form-control" value="<?= $absen['jam_keluar'] ?>">
          </div>
          <div class="col-md-12">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($absen['keterangan']) ?></textarea>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
function toggleJam() {
  const status = document.getElementById('statusSelect').value;
  const tampil = status === 'Hadir';
  document.getElementById('jamMasukWrap').style.display = tampil ? 'block' : 'none';
  document.getElementById('jamKeluarWrap').style.display = tampil ? 'block' : 'none';
}
toggleJam();
</script>
<?php include '../includes/footer.php'; ?>
