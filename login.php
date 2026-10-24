<?php
require_once 'config/koneksi.php';
require_once 'config/session.php';

if (isset($_SESSION['id_user'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : 'dashboard_karyawan.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($koneksi, "SELECT u.id_user, u.username, u.password, u.role, u.id_karyawan, k.nama_karyawan
                                       FROM users u
                                       LEFT JOIN karyawan k ON k.id_karyawan = u.id_karyawan
                                       WHERE u.username = ?");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['id_user']     = $user['id_user'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['id_karyawan'] = $user['id_karyawan'];
        $_SESSION['nama']        = $user['nama_karyawan'] ?? $user['username'];

        header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'dashboard_karyawan.php'));
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - BIG Absensi & Stok Barang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
  <div class="card login-card">
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <i class="bi bi-building-check" style="font-size:40px;color:#1e2a4a;"></i>
        <h5 class="mt-2 mb-0 fw-bold">BAGUS INDO GRAFIKA</h5>
        <small class="text-muted">Sistem Informasi Absensi & Stok Barang</small>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Tampilkan password">
              <i class="bi bi-eye" id="iconPassword"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="background:#1e2a4a;border:none;">Masuk</button>
      </form>
      
    </div>
  </div>
</div>

<script>
  const inputPassword = document.getElementById('password');
  const tombol = document.getElementById('togglePassword');
  const ikon = document.getElementById('iconPassword');

  tombol.addEventListener('click', function () {
    const tersembunyi = inputPassword.type === 'password';
    inputPassword.type = tersembunyi ? 'text' : 'password';
    ikon.className = tersembunyi ? 'bi bi-eye-slash' : 'bi bi-eye';
    tombol.setAttribute('aria-label', tersembunyi ? 'Sembunyikan password' : 'Tampilkan password');
  });
</script>
</body>
</html>