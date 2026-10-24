<?php $halaman_aktif = $halaman_aktif ?? ''; $is_admin = ($_SESSION['role'] ?? '') === 'admin'; ?>
<nav class="sidebar">
  <div class="sidebar-brand">
    <i class="bi bi-building-check"></i>
    <span>BAGUS INDO GRAFIKA</span>
  </div>
  <div class="px-3 py-2 text-white-50 small">
    Masuk sebagai<br>
    <strong class="text-white"><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username']) ?></strong>
    (<?= htmlspecialchars($_SESSION['role']) ?>)
  </div>
  <ul class="nav flex-column mt-2">
    <li class="nav-item">
      <a class="nav-link <?= in_array($halaman_aktif, ['dashboard','dashboard_karyawan'])?'active':'' ?>"
         href="<?= base_url() . ($is_admin ? 'dashboard.php' : 'dashboard_karyawan.php') ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>

    <?php if ($is_admin): ?>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='karyawan'?'active':'' ?>" href="<?= base_url() ?>karyawan/index.php">
        <i class="bi bi-people"></i> Data Karyawan
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='absensi'?'active':'' ?>" href="<?= base_url() ?>absensi/index.php">
        <i class="bi bi-calendar-check"></i> Absensi
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='pengajuan'?'active':'' ?>" href="<?= base_url() ?>pengajuan/index.php">
        <i class="bi bi-file-earmark-text"></i> Pengajuan Cuti/Izin
      </a>
    </li>

    <?php if ($is_admin): ?>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='barang'?'active':'' ?>" href="<?= base_url() ?>barang/index.php">
        <i class="bi bi-box-seam"></i> Master Barang
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='barang_masuk'?'active':'' ?>" href="<?= base_url() ?>barang_masuk/index.php">
        <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='barang_keluar'?'active':'' ?>" href="<?= base_url() ?>barang_keluar/index.php">
        <i class="bi bi-box-arrow-up"></i> Barang Keluar
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='laporan_absensi'?'active':'' ?>" href="<?= base_url() ?>laporan/laporan_absensi.php">
        <i class="bi bi-file-earmark-text"></i> Laporan Absensi
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='laporan_stok'?'active':'' ?>" href="<?= base_url() ?>laporan/laporan_stok.php">
        <i class="bi bi-file-earmark-bar-graph"></i> Laporan Stok
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $halaman_aktif=='user'?'active':'' ?>" href="<?= base_url() ?>user/index.php">
        <i class="bi bi-person-gear"></i> Kelola User
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-item mt-3">
      <a class="nav-link text-danger" href="<?= base_url() ?>logout.php">
        <i class="bi bi-box-arrow-right"></i> Keluar
      </a>
    </li>
  </ul>
</nav>
