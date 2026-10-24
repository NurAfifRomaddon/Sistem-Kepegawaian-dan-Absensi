<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Tambah Karyawan';
$halaman_aktif = 'karyawan';
$error = '';

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
        $stmt = mysqli_prepare($koneksi, "INSERT INTO karyawan (nama_karyawan, jabatan, tipe_karyawan, no_hp, alamat, status)
                                           VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssss', $nama, $jabatan, $tipe, $no_hp, $alamat, $status);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=Karyawan berhasil ditambahkan');
            exit;
        } else {
            $error = 'Gagal menyimpan data.';
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Tambah Karyawan</h4>

  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Nama Karyawan</label>
            <input type="text" name="nama_karyawan" class="form-control" required value="<?= htmlspecialchars($_POST['nama_karyawan'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Jabatan</label>
            <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tipe Karyawan</label>
            <select name="tipe_karyawan" class="form-select">
              <option value="Shift" <?= ($_POST['tipe_karyawan'] ?? 'Shift') == 'Shift' ? 'selected' : '' ?>>Shift (ikut jadwal Pagi/Malam)</option>
              <option value="Non-Shift" <?= ($_POST['tipe_karyawan'] ?? '') == 'Non-Shift' ? 'selected' : '' ?>>Non-Shift (jam kerja tetap/kantor)</option>
              <option value="Security" <?= ($_POST['tipe_karyawan'] ?? '') == 'Security' ? 'selected' : '' ?>>Security (rotasi 2 hari kerja - 1 hari libur)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="Aktif">Aktif</option>
              <option value="Non Aktif">Non Aktif</option>
            </select>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
