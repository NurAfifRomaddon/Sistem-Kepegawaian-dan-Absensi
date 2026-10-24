<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Kelola User';
$halaman_aktif = 'user';

$data = mysqli_query($koneksi, "
  SELECT u.id_user, u.username, u.role, k.nama_karyawan
  FROM users u
  LEFT JOIN karyawan k ON k.id_karyawan = u.id_karyawan
  ORDER BY u.username ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Kelola User</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah User</a>
  </div>

  <?php if (isset($_GET['sukses'])): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['sukses']) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr><th>Username</th><th>Role</th><th>Terhubung Karyawan</th><th class="text-end">Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">Belum ada user</td></tr>
            <?php endif; ?>
            <?php while ($u = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><span class="badge bg-<?= $u['role']=='admin'?'dark':'secondary' ?>"><?= ucfirst($u['role']) ?></span></td>
              <td><?= htmlspecialchars($u['nama_karyawan'] ?: '-') ?></td>
              <td class="text-end">
                <a href="edit.php?id=<?= $u['id_user'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                  <a href="hapus.php?id=<?= $u['id_user'] ?>" class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Hapus user ini?')"><i class="bi bi-trash"></i></a>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled title="Tidak bisa hapus akun sendiri"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php include '../includes/footer.php'; ?>
