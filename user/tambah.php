<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Tambah User';
$halaman_aktif = 'user';
$error = '';

$karyawan = mysqli_query($koneksi, "SELECT * FROM karyawan WHERE status='Aktif' ORDER BY nama_karyawan ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $role = $_POST['role'];
    $id_karyawan = $_POST['id_karyawan'] !== '' ? intval($_POST['id_karyawan']) : null;

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO users (id_karyawan, username, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isss', $id_karyawan, $username, $hash, $role);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=User berhasil ditambahkan');
            exit;
        } else {
            $error = 'Gagal menyimpan (username mungkin sudah dipakai).';
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Tambah User</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="karyawan">Karyawan</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
            <small class="text-muted">Minimal 6 karakter</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="konfirmasi_password" class="form-control" required minlength="6">
          </div>
          <div class="col-md-8">
            <label class="form-label">Hubungkan ke Data Karyawan <small class="text-muted">(opsional)</small></label>
            <select name="id_karyawan" class="form-select">
              <option value="">-- Tidak dihubungkan --</option>
              <?php while ($k = mysqli_fetch_assoc($karyawan)): ?>
                <option value="<?= $k['id_karyawan'] ?>"><?= htmlspecialchars($k['nama_karyawan']) ?></option>
              <?php endwhile; ?>
            </select>
            <small class="text-muted">Kalau dihubungkan, nama karyawan akan tampil sebagai nama user yang login</small>
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
