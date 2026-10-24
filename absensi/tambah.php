<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Input Absensi Karyawan';
$halaman_aktif = 'absensi';
$error = '';
$sukses_simpan = false;

$tanggal = $_GET['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal    = $_POST['tanggal'];
    $status_arr = $_POST['status'] ?? [];
    $jm_arr     = $_POST['jam_masuk'] ?? [];
    $jk_arr     = $_POST['jam_keluar'] ?? [];
    $shift_arr  = $_POST['shift'] ?? [];

    if (!$tanggal) {
        $error = 'Tanggal wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, jam_keluar, status, Shift)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                jam_masuk = VALUES(jam_masuk),
                jam_keluar = VALUES(jam_keluar),
                status = VALUES(status),
                Shift = VALUES(Shift)");

        if ($stmt === false) {
            die("Prepare gagal (INSERT): " . mysqli_error($koneksi));
        }

        $jumlah_disimpan = 0;
        foreach ($status_arr as $id_karyawan => $status) {
            $id_karyawan = intval($id_karyawan);
            $status = trim($status);
            if ($status === '' || !$id_karyawan) continue; // baris yang tidak diisi statusnya dilewati

            $jam_masuk  = $jm_arr[$id_karyawan] ?? null;
            $jam_keluar = $jk_arr[$id_karyawan] ?? null;
            $shift      = trim($shift_arr[$id_karyawan] ?? '');

            if ($status !== 'Hadir') {
                $jam_masuk = null;
                $jam_keluar = null;
            }
            $jam_masuk = $jam_masuk ?: null;
            $jam_keluar = $jam_keluar ?: null;

            mysqli_stmt_bind_param($stmt, 'isssss', $id_karyawan, $tanggal, $jam_masuk, $jam_keluar, $status, $shift);
            if (mysqli_stmt_execute($stmt)) {
                $jumlah_disimpan++;
            }
        }

        if ($jumlah_disimpan > 0) {
            header('Location: index.php?tanggal=' . $tanggal . '&sukses=' . $jumlah_disimpan . ' data absensi berhasil disimpan');
            exit;
        } else {
            $error = 'Tidak ada data yang disimpan. Pilih status untuk minimal satu karyawan.';
        }
    }
}

// ambil semua karyawan aktif, LEFT JOIN absensi pada tanggal terpilih supaya form terisi otomatis
// kalau tanggal itu sudah pernah diinput sebelumnya (jadi form ini juga bisa dipakai buat koreksi cepat)
$stmt = mysqli_prepare($koneksi, "
    SELECT k.id_karyawan, k.nama_karyawan, k.jabatan,
           a.status, a.jam_masuk, a.jam_keluar, a.Shift
    FROM karyawan k
    LEFT JOIN absensi a ON a.id_karyawan = k.id_karyawan AND a.tanggal = ?
    WHERE k.status = 'Aktif'
    ORDER BY k.nama_karyawan ASC");

if ($stmt === false) {
    die("Prepare gagal (SELECT): " . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, 's', $tanggal);
mysqli_stmt_execute($stmt);
$daftar_karyawan = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Input Absensi Karyawan</h4>
    <a href="index.php" class="btn btn-outline-secondary">Kembali ke Daftar</a>
  </div>

  <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-0 small">Tanggal Absensi</label>
          <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Ganti Tanggal</button>
        </div>
      </form>
      <small class="text-muted">Kalau tanggal ini sudah pernah diisi sebelumnya, datanya otomatis muncul lagi di bawah dan bisa dikoreksi.</small>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
    <div class="card">
      <div class="card-body">
        <p class="mb-3">Tanggal: <strong><?= tgl_indo($tanggal) ?></strong></p>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="min-width:160px;">Nama Karyawan</th>
                <th style="min-width:140px;">Status</th>
                <th style="min-width:120px;">Jam Masuk</th>
                <th style="min-width:120px;">Jam Keluar</th>
                <th style="min-width:200px;">Shift</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($daftar_karyawan) == 0): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada karyawan aktif</td></tr>
              <?php endif; ?>
              <?php $no = 0; while ($k = mysqli_fetch_assoc($daftar_karyawan)): $no++; $id = $k['id_karyawan']; $isHadir = $k['status'] === 'Hadir'; ?>
              <tr>
                <td>
                  <?= htmlspecialchars($k['nama_karyawan']) ?>
                  <div class="text-muted small"><?= htmlspecialchars($k['jabatan']) ?></div>
                </td>
                <td>
                  <select name="status[<?= $id ?>]" class="form-select form-select-sm status-select" data-id="<?= $id ?>" onchange="toggleJam(<?= $id ?>)">
                    <option value="" <?= !$k['status']?'selected':'' ?>>-- Belum diisi --</option>
                    <option value="Hadir" <?= $k['status']=='Hadir'?'selected':'' ?>>Hadir</option>
                    <option value="Cuti" <?= $k['status']=='Cuti'?'selected':'' ?>>Cuti</option>
                    <option value="Izin" <?= $k['status']=='Izin'?'selected':'' ?>>Izin</option>
                    <option value="Alpha" <?= $k['status']=='Alpha'?'selected':'' ?>>Alpha</option>
                  </select>
                </td>
                <td>
                  <input type="time" name="jam_masuk[<?= $id ?>]" id="jm_<?= $id ?>" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($k['jam_masuk'] ?? '') ?>" <?= $isHadir?'':'disabled' ?>>
                </td>
                <td>
                  <input type="time" name="jam_keluar[<?= $id ?>]" id="jk_<?= $id ?>" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($k['jam_keluar'] ?? '') ?>" <?= $isHadir?'':'disabled' ?>>
                </td>
                <td>
                  <input type="text" name="shift[<?= $id ?>]" class="form-control form-control-sm"
                         value="<?= htmlspecialchars($k['Shift'] ?? '') ?>" placeholder="Opsional">
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="mt-3">
      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Semua</button>
      <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</main>
<script>
function toggleJam(id) {
  const status = document.querySelector('.status-select[data-id="' + id + '"]').value;
  const jm = document.getElementById('jm_' + id);
  const jk = document.getElementById('jk_' + id);
  const aktif = status === 'Hadir';
  jm.disabled = !aktif;
  jk.disabled = !aktif;
  if (!aktif) { jm.value = ''; jk.value = ''; }
}
</script>
<?php include '../includes/footer.php'; ?>
