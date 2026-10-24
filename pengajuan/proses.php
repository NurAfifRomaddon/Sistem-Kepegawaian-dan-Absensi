<?php
require_once '../config/koneksi.php';
require_once '../config/session.php';
cek_admin();

$id   = intval($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan WHERE id_pengajuan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$p = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$p || $p['status'] !== 'Menunggu') {
    header('Location: index.php?error=Pengajuan tidak ditemukan atau sudah diproses');
    exit;
}

$id_user = $_SESSION['id_user'];

if ($aksi === 'setuju') {
    mysqli_begin_transaction($koneksi);
    try {
        // update status pengajuan
        $stmt = mysqli_prepare($koneksi, "UPDATE pengajuan SET status='Disetujui', diproses_oleh=?, diproses_at=NOW() WHERE id_pengajuan=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id_user, $id);
        mysqli_stmt_execute($stmt);

        // catat absensi untuk setiap tanggal dalam rentang pengajuan
        $mulai   = new DateTime($p['tanggal_mulai']);
        $selesai = new DateTime($p['tanggal_selesai']);
        $selesai->modify('+1 day'); // supaya tanggal akhir ikut ter-include di loop
        $periode = new DatePeriod($mulai, new DateInterval('P1D'), $selesai);

        $stmtAbsen = mysqli_prepare($koneksi, "
            INSERT INTO absensi (id_karyawan, tanggal, status, keterangan)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)");

        $keterangan = 'Disetujui via pengajuan' . ($p['alasan'] ? ': ' . $p['alasan'] : '');

        foreach ($periode as $tgl) {
            $tanggal = $tgl->format('Y-m-d');
            mysqli_stmt_bind_param($stmtAbsen, 'isss', $p['id_karyawan'], $tanggal, $p['jenis'], $keterangan);
            mysqli_stmt_execute($stmtAbsen);
        }

        mysqli_commit($koneksi);
        header('Location: index.php?sukses=Pengajuan disetujui, absensi otomatis tercatat');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: index.php?error=Gagal memproses persetujuan');
        exit;
    }
} elseif ($aksi === 'tolak') {
    $stmt = mysqli_prepare($koneksi, "UPDATE pengajuan SET status='Ditolak', diproses_oleh=?, diproses_at=NOW() WHERE id_pengajuan=?");
    mysqli_stmt_bind_param($stmt, 'isss', $id_user, $id);
    mysqli_stmt_execute($stmt);
    header('Location: index.php?sukses=Pengajuan ditolak');
    exit;
} else {
    header('Location: index.php');
    exit;
}
