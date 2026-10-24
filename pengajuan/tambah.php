<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_login();

$judul_halaman = 'Ajukan Cuti/Izin';
$halaman_aktif = 'pengajuan';
$error = '';

$id_karyawan = $_SESSION['id_karyawan'] ?? null;

if (!$id_karyawan) {
    include '../includes/header.php';
    include '../includes/sidebar.php';
    echo '<main class="content p-4"><div class="alert alert-warning">Akun kamu belum dihubungkan ke data karyawan. Hubungi admin.</div></main>';
    include '../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis      = $_POST['jenis'];
    $mulai      = $_POST['tanggal_mulai'];
    $selesai    = $_POST['tanggal_selesai'];
    $alasan     = trim($_POST['alasan']);

    if (!$mulai || !$selesai) {
        $error = 'Tanggal mulai dan selesai wajib diisi.';
    } elseif ($selesai < $mulai) {
        $error = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengajuan (id_karyawan, jenis, tanggal_mulai, tanggal_selesai, alasan, status)
                                           VALUES (?, ?, ?, ?, ?, 'Menunggu')");
        mysqli_stmt_bind_param($stmt, 'issss', $id_karyawan, $jenis, $mulai, $selesai, $alasan);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=Pengajuan berhasil dikirim, menunggu persetujuan admin');
            exit;
        } else {
            $error = 'Gagal mengirim pengajuan.';
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Ajukan Cuti / Izin</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card" style="max-width:640px;">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Jenis Pengajuan</label>
            <select name="jenis" class="form-select" required>
              <option value="Cuti">Cuti</option>
              <option value="Izin">Izin</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" class="form-control" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-12">
            <label class="form-label">Alasan</label>
            <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Acara keluarga, sakit, dll." required></textarea>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Kirim Pengajuan</button>
          <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </div>
      </form>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
