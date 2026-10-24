<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Edit Karyawan';
$halaman_aktif = 'karyawan';
$error = '';

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT * FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$karyawan = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$karyawan) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama_karyawan']);
    $jabatan = trim($_POST['jabatan']);
    $tipe    = $_POST['tipe_karyawan'] ?? 'Shift';
    $no_hp   = trim($_POST['no_hp']);
    $alamat  = trim($_POST['alamat']);
    $status  = $_POST['status'];

    if ($nama === '') {
        $error = 'Nama wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE karyawan SET nama_karyawan=?, jabatan=?, tipe_karyawan=?, no_hp=?, alamat=?, status=?
                                           WHERE id_karyawan=?");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $nama, $jabatan, $tipe, $no_hp, $alamat, $status, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=Data karyawan berhasil diperbarui');
            exit;
        } else {
            $error = 'Gagal menyimpan perubahan.';
        }
    }
    $karyawan = array_merge($karyawan, $_POST);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Edit Karyawan</h4>

  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Nama Karyawan</label>
            <input type="text" name="nama_karyawan" class="form-control" required value="<?= htmlspecialchars($karyawan['nama_karyawan']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Jabatan</label>
            <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($karyawan['jabatan']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipe Karyawan</label>
            <select name="tipe_karyawan" class="form-select">
              <option value="Shift" <?= ($karyawan['tipe_karyawan'] ?? 'Shift') == 'Shift' ? 'selected' : '' ?>>Shift (ikut jadwal Pagi/Malam)</option>
              <option value="Non-Shift" <?= ($karyawan['tipe_karyawan'] ?? '') == 'Non-Shift' ? 'selected' : '' ?>>Non-Shift (jam kerja tetap/kantor)</option>
              <option value="Security" <?= ($karyawan['tipe_karyawan'] ?? '') == 'Security' ? 'selected' : '' ?>>Security (rotasi 2 hari kerja - 1 hari libur)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($karyawan['no_hp']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($karyawan['alamat']) ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="Aktif" <?= $karyawan['status']=='Aktif'?'selected':'' ?>>Aktif</option>
              <option value="Non Aktif" <?= $karyawan['status']=='Non Aktif'?'selected':'' ?>>Non Aktif</option>
            </select>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
