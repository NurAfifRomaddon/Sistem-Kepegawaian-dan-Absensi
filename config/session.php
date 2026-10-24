<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Wajibkan login sebelum mengakses halaman */
function cek_login() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . base_url() . 'login.php');
        exit;
    }
}

/** Wajibkan role admin */
function cek_admin() {
    cek_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . base_url() . 'dashboard_karyawan.php');
        exit;
    }
}

/** Deteksi base url relatif terhadap root project, agar link tetap benar
 *  meski file dipanggil dari sub-folder (karyawan/, absensi/, dst) */
function base_url() {
    $script = $_SERVER['SCRIPT_NAME'];
    $depth  = substr_count(trim(dirname($script), '/'), '/');
    // asumsikan file di root project berada 0 folder dari root
    // dan file di sub-folder (karyawan/, absensi/, dst) 1 folder dari root
    $inSubfolder = preg_match('#/(karyawan|absensi|barang|barang_masuk|barang_keluar|laporan|user|pengajuan)/#', $script);
    return $inSubfolder ? '../' : '';
}

function escape($koneksi, $val) {
    return mysqli_real_escape_string($koneksi, $val);
}

function tgl_indo($tanggal) {
    if (!$tanggal) return '-';
    $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    [$y, $m, $d] = explode('-', $tanggal);
    return intval($d) . ' ' . $bulan[$m] . ' ' . $y;
}

function badge_status($status) {
    $map = [
        'Hadir' => 'success',
        'Cuti'  => 'info',
        'Izin'  => 'warning',
        'Alpha' => 'danger',
    ];
    $warna = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$warna\">$status</span>";
}

function badge_pengajuan($status) {
    $map = [
        'Menunggu'  => 'warning',
        'Disetujui' => 'success',
        'Ditolak'   => 'danger',
    ];
    $warna = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$warna\">$status</span>";
}
