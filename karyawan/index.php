<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$judul_halaman = 'Data Karyawan';
$halaman_aktif = 'karyawan';

// fallback: kalau fungsi ini belum ada di config/koneksi.php, definisikan di sini
if (!function_exists('awal_minggu')) {
    function awal_minggu($tanggal) {
        // mengembalikan tanggal hari Senin dari minggu yang memuat $tanggal
        $ts = strtotime($tanggal);
        $hari_ke = (int) date('N', $ts); // 1 (Senin) s/d 7 (Minggu)
        return date('Y-m-d', strtotime('-' . ($hari_ke - 1) . ' days', $ts));
    }
}
if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal) {
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime($tanggal);
        return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

// helper: bungkus mysqli_prepare supaya kalau gagal (tabel/kolom belum ada, dsb),
// langsung tampil pesan error MySQL yang jelas alih-alih fatal error yang membingungkan
function siapkan($koneksi, $sql) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt === false) {
        die('<div style="font-family:monospace;background:#fee;padding:16px;border:1px solid #c00;margin:16px;">'
            . '<strong>Query gagal disiapkan:</strong> ' . htmlspecialchars(mysqli_error($koneksi))
            . '<br><br><strong>SQL:</strong><br>' . nl2br(htmlspecialchars(trim($sql)))
            . '</div>');
    }
    return $stmt;
}

$tab_aktif = $_GET['tab'] ?? 'karyawan'; // 'karyawan' atau 'shift'

// tanggal acuan minggu yang sedang dilihat -> selalu dinormalisasi ke hari Senin
$tanggal_acuan = $_GET['minggu'] ?? $_POST['minggu_acuan'] ?? date('Y-m-d');
$awal_minggu   = awal_minggu($tanggal_acuan);
$akhir_minggu  = date('Y-m-d', strtotime($awal_minggu . ' +6 days'));

$pesan_sukses = $_GET['sukses'] ?? '';
$error_shift  = '';

// ================================================================
// AMBIL SEMUA PENGATURAN (jam kerja + titik awal rotasi security)
// ================================================================
function ambil_pengaturan($koneksi) {
    $out = [];
    $res = mysqli_query($koneksi, "SELECT kunci, nilai FROM pengaturan");
    while ($row = mysqli_fetch_assoc($res)) {
        $out[$row['kunci']] = $row['nilai'];
    }
    return $out;
}
$pengaturan = ambil_pengaturan($koneksi);

$jp_masuk   = $pengaturan['jam_pagi_masuk']     ?? '08:00';
$jp_keluar  = $pengaturan['jam_pagi_keluar']    ?? '20:00';
$jm_masuk   = $pengaturan['jam_malam_masuk']    ?? '20:00';
$jm_keluar  = $pengaturan['jam_malam_keluar']   ?? '08:00';
$jns_masuk  = $pengaturan['jam_nonshift_masuk'] ?? '08:00';
$jns_keluar = $pengaturan['jam_nonshift_keluar']?? '16:00';
$tgl_mulai_rotasi = $pengaturan['tanggal_mulai_rotasi_security'] ?? $awal_minggu;

// ================================================================
// PROSES: SIMPAN PENGATURAN JAM KERJA (Pagi / Malam / Non-Shift)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jam_kerja'])) {
    $data_jam = [
        'jam_pagi_masuk'      => $_POST['jam_pagi_masuk'],
        'jam_pagi_keluar'     => $_POST['jam_pagi_keluar'],
        'jam_malam_masuk'     => $_POST['jam_malam_masuk'],
        'jam_malam_keluar'    => $_POST['jam_malam_keluar'],
        'jam_nonshift_masuk'  => $_POST['jam_nonshift_masuk'],
        'jam_nonshift_keluar' => $_POST['jam_nonshift_keluar'],
    ];
    $stmtJam = siapkan($koneksi, "
        INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    foreach ($data_jam as $kunci => $nilai) {
        mysqli_stmt_bind_param($stmtJam, 'ss', $kunci, $nilai);
        mysqli_stmt_execute($stmtJam);
    }
    header('Location: index.php?tab=shift&minggu=' . $awal_minggu . '&sukses=' . urlencode('Jam kerja berhasil diperbarui'));
    exit;
}

// ================================================================
// PROSES: SIMPAN JADWAL SHIFT UNTUK 1 MINGGU PENUH (Senin - Minggu)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jadwal'])) {
    $awal_minggu_post = awal_minggu($_POST['minggu_acuan'] ?? date('Y-m-d'));
    $shift_arr        = $_POST['shift'] ?? [];

    $stmtInsert = siapkan($koneksi, "
        INSERT INTO jadwal_shift (id_karyawan, tanggal, shift)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE shift = VALUES(shift)");
    $stmtDelete = siapkan($koneksi, "DELETE FROM jadwal_shift WHERE id_karyawan=? AND tanggal=?");

    foreach ($shift_arr as $id_karyawan => $shift) {
        $id_karyawan = intval($id_karyawan);
        $shift = trim($shift);
        if (!$id_karyawan) continue;

        // tulis/hapus untuk ke-7 hari dalam minggu itu sekaligus (Senin s/d Minggu)
        for ($i = 0; $i < 7; $i++) {
            $tgl = date('Y-m-d', strtotime($awal_minggu_post . " +$i days"));
            if ($shift === '') {
                mysqli_stmt_bind_param($stmtDelete, 'is', $id_karyawan, $tgl);
                mysqli_stmt_execute($stmtDelete);
            } else {
                mysqli_stmt_bind_param($stmtInsert, 'iss', $id_karyawan, $tgl, $shift);
                mysqli_stmt_execute($stmtInsert);
            }
        }
    }

    header('Location: index.php?tab=shift&minggu=' . $awal_minggu_post . '&sukses=' . urlencode('Jadwal shift 1 minggu berhasil disimpan'));
    exit;
}

// ================================================================
// PROSES: GENERATE ROTASI MINGGU BERIKUTNYA (TUKAR OTOMATIS Pagi<->Malam)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_rotasi'])) {
    $minggu_sekarang = awal_minggu($_POST['minggu_acuan'] ?? date('Y-m-d'));
    $minggu_depan    = date('Y-m-d', strtotime($minggu_sekarang . ' +7 days'));

    $stmt = siapkan($koneksi, "
        SELECT k.id_karyawan, j.shift
        FROM karyawan k
        LEFT JOIN jadwal_shift j ON j.id_karyawan = k.id_karyawan AND j.tanggal = ?
        WHERE k.status = 'Aktif' AND k.tipe_karyawan = 'Shift'");
    mysqli_stmt_bind_param($stmt, 's', $minggu_sekarang);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $tukar = ['Pagi' => 'Malam', 'Malam' => 'Pagi', 'Libur' => 'Libur'];

    $stmtInsert = siapkan($koneksi, "
        INSERT INTO jadwal_shift (id_karyawan, tanggal, shift)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE shift = VALUES(shift)");

    while ($row = mysqli_fetch_assoc($res)) {
        if (!$row['shift']) continue;
        $shift_baru = $tukar[$row['shift']] ?? $row['shift'];
        for ($i = 0; $i < 7; $i++) {
            $tgl = date('Y-m-d', strtotime($minggu_depan . " +$i days"));
            mysqli_stmt_bind_param($stmtInsert, 'iss', $row['id_karyawan'], $tgl, $shift_baru);
            mysqli_stmt_execute($stmtInsert);
        }
    }

    header('Location: index.php?tab=shift&minggu=' . $minggu_depan . '&sukses=' . urlencode('Rotasi shift minggu berikutnya berhasil dibuat otomatis (Pagi<->Malam tertukar)'));
    exit;
}

// ================================================================
// PROSES: ATUR ULANG TITIK AWAL ROTASI SECURITY
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_rotasi_security'])) {
    $tgl_baru = $_POST['tanggal_mulai_rotasi'] ?? date('Y-m-d');
    $stmtSet = siapkan($koneksi, "
        INSERT INTO pengaturan (kunci, nilai) VALUES ('tanggal_mulai_rotasi_security', ?)
        ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    mysqli_stmt_bind_param($stmtSet, 's', $tgl_baru);
    mysqli_stmt_execute($stmtSet);

    // hapus semua override manual yang sudah ada supaya rotasi murni dari titik baru
    mysqli_query($koneksi, "DELETE FROM jadwal_security");

    header('Location: index.php?tab=shift&minggu=' . $awal_minggu . '&sukses=' . urlencode('Titik awal rotasi security berhasil direset'));
    exit;
}

// ================================================================
// PROSES: SIMPAN OVERRIDE MANUAL JADWAL SECURITY UNTUK MINGGU INI
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jadwal_security'])) {
    $minggu_post = awal_minggu($_POST['minggu_acuan'] ?? date('Y-m-d'));
    $override    = $_POST['security'] ?? []; // security[id_karyawan][tanggal] = 'Kerja'/'Libur'/'' (kosong = ikut rotasi otomatis)

    $stmtUpsert = siapkan($koneksi, "
        INSERT INTO jadwal_security (id_karyawan, tanggal, keterangan)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE keterangan = VALUES(keterangan)");
    $stmtHapus = siapkan($koneksi, "DELETE FROM jadwal_security WHERE id_karyawan=? AND tanggal=?");

    foreach ($override as $id_karyawan => $per_tanggal) {
        $id_karyawan = intval($id_karyawan);
        foreach ($per_tanggal as $tgl => $ket) {
            $ket = trim($ket);
            if ($ket === '') {
                mysqli_stmt_bind_param($stmtHapus, 'is', $id_karyawan, $tgl);
                mysqli_stmt_execute($stmtHapus);
            } else {
                mysqli_stmt_bind_param($stmtUpsert, 'iss', $id_karyawan, $tgl, $ket);
                mysqli_stmt_execute($stmtUpsert);
            }
        }
    }

    header('Location: index.php?tab=shift&minggu=' . $minggu_post . '&sukses=' . urlencode('Jadwal security berhasil disimpan'));
    exit;
}

// ================================================================
// DATA UNTUK TAB "DATA KARYAWAN"
// ================================================================
$keyword = trim($_GET['q'] ?? '');
$stmt = siapkan($koneksi, "SELECT * FROM karyawan WHERE nama_karyawan LIKE ? ORDER BY nama_karyawan ASC");
$like = "%$keyword%";
mysqli_stmt_bind_param($stmt, 's', $like);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

// ================================================================
// DATA UNTUK TAB "JADWAL SHIFT" (karyawan tipe Shift saja)
// ================================================================
$stmt2 = siapkan($koneksi, "
    SELECT k.id_karyawan, k.nama_karyawan, k.jabatan, j.shift
    FROM karyawan k
    LEFT JOIN jadwal_shift j ON j.id_karyawan = k.id_karyawan AND j.tanggal = ?
    WHERE k.status = 'Aktif' AND k.tipe_karyawan = 'Shift'
    ORDER BY k.nama_karyawan ASC");
mysqli_stmt_bind_param($stmt2, 's', $awal_minggu);
mysqli_stmt_execute($stmt2);
$daftar_shift = mysqli_stmt_get_result($stmt2);

// daftar karyawan Non-Shift
$stmt3 = siapkan($koneksi, "SELECT id_karyawan, nama_karyawan, jabatan FROM karyawan WHERE status='Aktif' AND tipe_karyawan='Non-Shift' ORDER BY nama_karyawan ASC");
mysqli_stmt_execute($stmt3);
$daftar_nonshift = mysqli_stmt_get_result($stmt3);

// ================================================================
// DATA UNTUK JADWAL SECURITY
// urutan rotasi ditentukan dari urutan id_karyawan (ASC) di antara
// karyawan aktif bertipe 'Security'
// ================================================================
$stmt4 = siapkan($koneksi, "SELECT id_karyawan, nama_karyawan, jabatan FROM karyawan WHERE status='Aktif' AND tipe_karyawan='Security' ORDER BY id_karyawan ASC");
mysqli_stmt_execute($stmt4);
$res4 = mysqli_stmt_get_result($stmt4);
$daftar_security = [];
$urutan = 0;
while ($s = mysqli_fetch_assoc($res4)) {
    $s['urutan'] = $urutan;
    $daftar_security[] = $s;
    $urutan++;
}

// ambil semua override manual jadwal_security untuk minggu yang sedang dilihat
$override_security = []; // [id_karyawan][tanggal] = 'Kerja'/'Libur'
if (!empty($daftar_security)) {
    $stmt5 = siapkan($koneksi, "SELECT id_karyawan, tanggal, keterangan FROM jadwal_security WHERE tanggal BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt5, 'ss', $awal_minggu, $akhir_minggu);
    mysqli_stmt_execute($stmt5);
    $res5 = mysqli_stmt_get_result($stmt5);
    while ($o = mysqli_fetch_assoc($res5)) {
        $override_security[$o['id_karyawan']][$o['tanggal']] = $o['keterangan'];
    }
}

// fungsi hitung status rotasi otomatis: 2 hari kerja, 1 hari libur, bergeser per orang
// sehingga di antara 3 security selalu ada tepat 1 orang libur setiap harinya
function status_rotasi_security($tanggal, $urutan, $tgl_mulai) {
    $selisih_hari = (int) floor((strtotime($tanggal) - strtotime($tgl_mulai)) / 86400);
    $posisi = ($selisih_hari + $urutan) % 3;
    if ($posisi < 0) $posisi += 3;
    return $posisi == 2 ? 'Libur' : 'Kerja';
}

function status_security_final($id_karyawan, $tanggal, $urutan, $tgl_mulai, $override_security) {
    if (isset($override_security[$id_karyawan][$tanggal])) {
        return $override_security[$id_karyawan][$tanggal]; // manual menang
    }
    return status_rotasi_security($tanggal, $urutan, $tgl_mulai); // otomatis
}

// daftar 7 tanggal minggu yang sedang dilihat
$tanggal_minggu_ini = [];
for ($i = 0; $i < 7; $i++) {
    $tanggal_minggu_ini[] = date('Y-m-d', strtotime($awal_minggu . " +$i days"));
}
$nama_hari = ['Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu','Minggu'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<main class="content p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Data Karyawan</h4>
    <?php if ($tab_aktif === 'karyawan'): ?>
      <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Karyawan</a>
    <?php endif; ?>
  </div>

  <?php if ($pesan_sukses): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($pesan_sukses) ?></div>
  <?php endif; ?>
  <?php if ($error_shift): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error_shift) ?></div>
  <?php endif; ?>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= $tab_aktif == 'karyawan' ? 'active' : '' ?>" href="index.php?tab=karyawan">
        <i class="bi bi-people"></i> Data Karyawan
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab_aktif == 'shift' ? 'active' : '' ?>" href="index.php?tab=shift&minggu=<?= htmlspecialchars($awal_minggu) ?>">
        <i class="bi bi-calendar2-week"></i> Jadwal Shift
      </a>
    </li>
  </ul>

  <?php if ($tab_aktif === 'shift'): ?>
    <!-- ======================= PENGATURAN JAM KERJA ======================= -->
    <div class="card mb-3">
      <div class="card-header bg-white">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#pengaturanJam">
          <i class="bi bi-gear"></i> Atur Jam Kerja
        </button>
      </div>
      <div class="collapse" id="pengaturanJam">
        <div class="card-body">
          <form method="POST" class="row g-3">
            <input type="hidden" name="minggu_acuan" value="<?= htmlspecialchars($awal_minggu) ?>">
            <div class="col-md-4">
              <label class="form-label small fw-semibold"><i class="bi bi-sun text-warning"></i> Shift Pagi</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="time" name="jam_pagi_masuk" class="form-control form-control-sm" value="<?= htmlspecialchars($jp_masuk) ?>">
                <span>s/d</span>
                <input type="time" name="jam_pagi_keluar" class="form-control form-control-sm" value="<?= htmlspecialchars($jp_keluar) ?>">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold"><i class="bi bi-moon-stars"></i> Shift Malam</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="time" name="jam_malam_masuk" class="form-control form-control-sm" value="<?= htmlspecialchars($jm_masuk) ?>">
                <span>s/d</span>
                <input type="time" name="jam_malam_keluar" class="form-control form-control-sm" value="<?= htmlspecialchars($jm_keluar) ?>">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold"><i class="bi bi-building"></i> Non-Shift</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="time" name="jam_nonshift_masuk" class="form-control form-control-sm" value="<?= htmlspecialchars($jns_masuk) ?>">
                <span>s/d</span>
                <input type="time" name="jam_nonshift_keluar" class="form-control form-control-sm" value="<?= htmlspecialchars($jns_keluar) ?>">
              </div>
            </div>
            <div class="col-12">
              <button type="submit" name="simpan_jam_kerja" value="1" class="btn btn-primary btn-sm">
                <i class="bi bi-save"></i> Simpan Jam Kerja
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ======================= TAB: JADWAL SHIFT MINGGUAN ======================= -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
          <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="shift">
            <div class="col-auto">
              <label class="form-label mb-0 small">Pilih tanggal berapa saja di minggu yang dituju</label>
              <input type="date" name="minggu" class="form-control" value="<?= htmlspecialchars($awal_minggu) ?>" onchange="this.form.submit()">
            </div>
          </form>
          <div class="d-flex gap-2">
            <a href="index.php?tab=shift&minggu=<?= date('Y-m-d', strtotime($awal_minggu . ' -7 days')) ?>" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-left"></i> Minggu Lalu
            </a>
            <a href="index.php?tab=shift&minggu=<?= date('Y-m-d', strtotime($awal_minggu . ' +7 days')) ?>" class="btn btn-outline-secondary btn-sm">
              Minggu Depan <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
        <hr>
        <p class="mb-1"><strong>Minggu: <?= tgl_indo($awal_minggu) ?> &ndash; <?= tgl_indo($akhir_minggu) ?></strong></p>
        <small class="text-muted d-block">
          <i class="bi bi-sun text-warning"></i> Pagi: <?= htmlspecialchars($jp_masuk) ?> - <?= htmlspecialchars($jp_keluar) ?> &nbsp;|&nbsp;
          <i class="bi bi-moon-stars"></i> Malam: <?= htmlspecialchars($jm_masuk) ?> - <?= htmlspecialchars($jm_keluar) ?> &nbsp;|&nbsp;
          <i class="bi bi-house text-secondary"></i> Libur
          &nbsp;&mdash;&nbsp; Satu shift berlaku untuk 7 hari penuh di minggu ini (Senin s/d Minggu). Ubah jam lewat "Atur Jam Kerja" di atas.
        </small>
      </div>
    </div>

    <?php
    $kelompok = ['Pagi' => [], 'Malam' => [], 'Libur' => [], 'Belum' => []];
    while ($k = mysqli_fetch_assoc($daftar_shift)) {
        $grp = $k['shift'] ?: 'Belum';
        $kelompok[$grp][] = $k;
    }
    $label_grup = [
        'Pagi'  => ["Shift Pagi ($jp_masuk - $jp_keluar)", 'warning text-dark', 'bi-sun'],
        'Malam' => ["Shift Malam ($jm_masuk - $jm_keluar)", 'dark text-white',  'bi-moon-stars'],
        'Libur' => ['Libur',                       'secondary text-white', 'bi-house'],
        'Belum' => ['Belum Diatur',                 'light text-dark border', 'bi-question-circle'],
    ];
    ?>

    <form method="POST" class="mb-3">
      <input type="hidden" name="minggu_acuan" value="<?= htmlspecialchars($awal_minggu) ?>">
      <div class="row g-3">
        <?php foreach ($label_grup as $key => $info): [$judul, $warna, $ic] = $info; ?>
        <div class="col-md-3 col-sm-6">
          <div class="card h-100">
            <div class="card-header bg-<?= $warna ?> d-flex justify-content-between align-items-center">
              <span class="fw-semibold small"><i class="bi <?= $ic ?>"></i> <?= $judul ?></span>
              <span class="badge bg-white text-dark"><?= count($kelompok[$key]) ?></span>
            </div>
            <div class="card-body p-2" style="min-height:140px;">
              <?php if (empty($kelompok[$key])): ?>
                <p class="text-muted small text-center mb-0 py-4">Kosong</p>
              <?php endif; ?>
              <?php foreach ($kelompok[$key] as $k): $id = $k['id_karyawan']; ?>
                <div class="border rounded px-2 py-1 mb-2">
                  <div class="small fw-semibold"><?= htmlspecialchars($k['nama_karyawan']) ?></div>
                  <div class="text-muted mb-1" style="font-size:11px;"><?= htmlspecialchars($k['jabatan']) ?></div>
                  <select name="shift[<?= $id ?>]" class="form-select form-select-sm">
                    <option value="" <?= $key == 'Belum' ? 'selected' : '' ?>>-- Belum diatur --</option>
                    <option value="Pagi" <?= $key == 'Pagi' ? 'selected' : '' ?>>Pindah ke Pagi</option>
                    <option value="Malam" <?= $key == 'Malam' ? 'selected' : '' ?>>Pindah ke Malam</option>
                    <option value="Libur" <?= $key == 'Libur' ? 'selected' : '' ?>>Pindah ke Libur</option>
                  </select>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3">
        <button type="submit" name="simpan_jadwal" value="1" class="btn btn-primary">
          <i class="bi bi-save"></i> Simpan Jadwal Minggu Ini
        </button>
      </div>
    </form>

    <div class="card border-primary mb-3">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <p class="fw-semibold mb-1"><i class="bi bi-arrow-repeat"></i> Rotasi Otomatis Minggu Berikutnya</p>
          <p class="text-muted small mb-0">
            Menyalin jadwal minggu ini ke minggu depan (<?= tgl_indo(date('Y-m-d', strtotime($awal_minggu . ' +7 days'))) ?>),
            dengan Pagi &harr; Malam otomatis tertukar untuk setiap karyawan. Karyawan yang belum diatur minggu ini akan dilewati.
          </p>
        </div>
        <form method="POST" onsubmit="return confirm('Buat jadwal minggu depan otomatis dengan menukar Pagi <-> Malam dari minggu ini?');">
          <input type="hidden" name="minggu_acuan" value="<?= htmlspecialchars($awal_minggu) ?>">
          <button type="submit" name="generate_rotasi" value="1" class="btn btn-primary">
            <i class="bi bi-arrow-repeat"></i> Generate Minggu Depan (Tukar Otomatis)
          </button>
        </form>
      </div>
    </div>

    <?php if (mysqli_num_rows($daftar_nonshift) > 0): ?>
    <div class="row g-3 mb-3">
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-header bg-light text-dark border d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="bi bi-building"></i> Non-Shift</span>
            <span class="badge bg-white text-dark border"><?= mysqli_num_rows($daftar_nonshift) ?></span>
          </div>
          <div class="card-body p-2" style="min-height:140px;">
            <p class="small text-muted mb-2"><i class="bi bi-clock"></i> Jam kerja: <strong><?= htmlspecialchars($jns_masuk) ?> - <?= htmlspecialchars($jns_keluar) ?></strong></p>
            <?php while ($n = mysqli_fetch_assoc($daftar_nonshift)): ?>
              <div class="border rounded px-2 py-1 mb-2">
                <div class="small fw-semibold"><?= htmlspecialchars($n['nama_karyawan']) ?></div>
                <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($n['jabatan']) ?></div>
              </div>
            <?php endwhile; ?>
          </div>
          <div class="card-footer bg-white text-muted" style="font-size:11px;">
            Jam kerja tetap/kantor, tidak ikut rotasi shift. Ubah lewat tab Data Karyawan &rarr; Edit. Jam bisa diubah lewat "Atur Jam Kerja" di atas.
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ======================= JADWAL SECURITY (ROTASI 2 KERJA - 1 LIBUR) ======================= -->
    <?php if (!empty($daftar_security)): ?>
    <div class="card border-dark">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-shield-lock"></i> Jadwal Security (Rotasi 2 Hari Kerja - 1 Hari Libur)</span>
        <span class="badge bg-white text-dark"><?= count($daftar_security) ?> orang</span>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-2">
          Setiap security bekerja 2 hari lalu libur 1 hari, bergiliran otomatis, sehingga setiap hari selalu ada tepat 1 orang libur.
          Titik awal rotasi: <strong><?= tgl_indo($tgl_mulai_rotasi) ?></strong>.
          Kalau perlu koreksi manual untuk hari tertentu, ubah langsung di dropdown tabel di bawah lalu klik Simpan.
        </p>

        <form method="POST" class="mb-4">
          <input type="hidden" name="minggu_acuan" value="<?= htmlspecialchars($awal_minggu) ?>">
          <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-2">
              <thead class="table-light">
                <tr>
                  <th class="text-start">Nama</th>
                  <?php foreach ($tanggal_minggu_ini as $i => $tgl): ?>
                    <th style="min-width:110px;">
                      <?= $nama_hari[$i] ?><br>
                      <small class="text-muted fw-normal"><?= date('d/m', strtotime($tgl)) ?></small>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($daftar_security as $s): $id = $s['id_karyawan']; ?>
                <tr>
                  <td class="text-start">
                    <div class="fw-semibold small"><?= htmlspecialchars($s['nama_karyawan']) ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($s['jabatan']) ?></div>
                  </td>
                  <?php foreach ($tanggal_minggu_ini as $tgl):
                        $status = status_security_final($id, $tgl, $s['urutan'], $tgl_mulai_rotasi, $override_security);
                        $ada_override = isset($override_security[$id][$tgl]);
                  ?>
                    <td class="<?= $status == 'Libur' ? 'bg-secondary-subtle' : 'bg-success-subtle' ?>">
                      <select name="security[<?= $id ?>][<?= $tgl ?>]" class="form-select form-select-sm">
                        <option value="" <?= !$ada_override ? 'selected' : '' ?>>
                          Auto: <?= $status ?>
                        </option>
                        <option value="Kerja" <?= ($ada_override && $status=='Kerja') ? 'selected' : '' ?>>Kerja</option>
                        <option value="Libur" <?= ($ada_override && $status=='Libur') ? 'selected' : '' ?>>Libur</option>
                      </select>
                    </td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button type="submit" name="simpan_jadwal_security" value="1" class="btn btn-primary btn-sm">
            <i class="bi bi-save"></i> Simpan Jadwal Security Minggu Ini
          </button>
        </form>

        <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <p class="fw-semibold mb-1"><i class="bi bi-arrow-counterclockwise"></i> Reset Titik Awal Rotasi</p>
            <p class="text-muted small mb-0">Menghapus semua koreksi manual dan mulai hitung rotasi dari tanggal baru.</p>
          </div>
          <form method="POST" class="d-flex gap-2" onsubmit="return confirm('Reset rotasi security? Semua koreksi manual yang tersimpan akan dihapus.');">
            <input type="date" name="tanggal_mulai_rotasi" class="form-control form-control-sm" value="<?= htmlspecialchars($tgl_mulai_rotasi) ?>">
            <button type="submit" name="reset_rotasi_security" value="1" class="btn btn-outline-dark btn-sm">
              <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-light border small text-muted">
        Belum ada karyawan bertipe "Security". Tambahkan lewat tab Data Karyawan &rarr; Tambah/Edit, pilih Tipe Karyawan "Security".
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- ======================= TAB: DATA KARYAWAN ======================= -->
    <div class="card">
      <div class="card-body">
        <form method="GET" class="mb-3">
          <input type="hidden" name="tab" value="karyawan">
          <div class="input-group" style="max-width:320px;">
            <input type="text" name="q" class="form-control" placeholder="Cari nama karyawan..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Nama</th><th>Jabatan</th><th>Tipe</th><th>No. HP</th><th>Status</th><th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data karyawan</td></tr>
              <?php endif; ?>
              <?php while ($k = mysqli_fetch_assoc($data)): ?>
              <tr>
                <td><?= htmlspecialchars($k['nama_karyawan']) ?></td>
                <td><?= htmlspecialchars($k['jabatan']) ?></td>
                <td>
                  <?php $tipe_k = $k['tipe_karyawan'] ?? 'Shift'; ?>
                  <?php if ($tipe_k == 'Shift'): ?>
                    <span class="badge bg-info text-dark">Shift</span>
                  <?php elseif ($tipe_k == 'Security'): ?>
                    <span class="badge bg-dark">Security</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Non-Shift</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($k['no_hp'] ?: '-') ?></td>
                <td><span class="badge bg-<?= $k['status'] == 'Aktif' ? 'success' : 'secondary' ?>"><?= $k['status'] ?></span></td>
                <td class="text-end">
                  <a href="edit.php?id=<?= $k['id_karyawan'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <a href="hapus.php?id=<?= $k['id_karyawan'] ?>" class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Hapus data karyawan ini beserta riwayat absensinya?')"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>
<?php include '../includes/footer.php'; ?>
