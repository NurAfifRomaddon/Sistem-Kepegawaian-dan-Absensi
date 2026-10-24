<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Edit User';
$halaman_aktif = 'user';
$error = '';

$id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    header('Location: index.php');
    exit;
}

$karyawan = mysqli_query($koneksi, "SELECT * FROM karyawan WHERE status='Aktif' ORDER BY nama_karyawan ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $role = $_POST['role'];
    $id_karyawan = $_POST['id_karyawan'] !== '' ? intval($_POST['id_karyawan']) : null;

    if ($username === '') {
        $error = 'Username wajib diisi.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET username=?, password=?, role=?, id_karyawan=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmt, 'sssii', $username, $hash, $role, $id_karyawan, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET username=?, role=?, id_karyawan=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmt, 'ssii', $username, $role, $id_karyawan, $id);
        }
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?sukses=User berhasil diperbarui');
            exit;
        } else {
            $error = 'Gagal menyimpan (username mungkin sudah dipakai).';
        }
    }
    $user = array_merge($user, $_POST);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <h4 class="fw-bold mb-3">Edit User</h4>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($user['username']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="karyawan" <?= $user['role']=='karyawan'?'selected':'' ?>>Karyawan</option>
              <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak diganti)</small></label>
            <input type="password" name="password" class="form-control" minlength="6">
          </div>
          <div class="col-md-6">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="konfirmasi_password" class="form-control" minlength="6">
          </div>
          <div class="col-md-8">
            <label class="form-label">Hubungkan ke Data Karyawan <small class="text-muted">(opsional)</small></label>
            <select name="id_karyawan" class="form-select">
              <option value="">-- Tidak dihubungkan --</option>
              <?php while ($k = mysqli_fetch_assoc($karyawan)): ?>
                <option value="<?= $k['id_karyawan'] ?>" <?= $user['id_karyawan']==$k['id_karyawan']?'selected':'' ?>>
                  <?= htmlspecialchars($k['nama_karyawan']) ?>
                </option>
              <?php endwhile; ?>
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
