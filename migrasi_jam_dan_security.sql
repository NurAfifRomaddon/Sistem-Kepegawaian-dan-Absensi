-- ============================================================
-- MIGRASI: Pengaturan Jam Kerja (editable) + Jadwal Security
-- Jalankan file ini sekali lewat phpMyAdmin / mysql CLI
-- ============================================================

-- 1) Tabel pengaturan umum (key-value), dipakai untuk simpan jam kerja
--    Pagi/Malam/Non-Shift dan titik awal rotasi security.
CREATE TABLE IF NOT EXISTS pengaturan (
    kunci  VARCHAR(50) PRIMARY KEY,
    nilai  VARCHAR(50) NOT NULL
);

-- Nilai default (silakan sesuaikan lewat halaman "Jadwal Shift" nanti)
INSERT INTO pengaturan (kunci, nilai) VALUES
    ('jam_pagi_masuk', '08:00'),
    ('jam_pagi_keluar', '20:00'),
    ('jam_malam_masuk', '20:00'),
    ('jam_malam_keluar', '08:00'),
    ('jam_nonshift_masuk', '08:00'),
    ('jam_nonshift_keluar', '16:00'),
    ('tanggal_mulai_rotasi_security', CURDATE())
ON DUPLICATE KEY UPDATE kunci = kunci; -- tidak menimpa kalau sudah pernah dijalankan

-- 2) Tabel jadwal khusus security (Kerja / Libur per tanggal)
--    Kalau baris untuk id_karyawan+tanggal tertentu tidak ada,
--    sistem akan MENGHITUNG OTOMATIS dari rumus rotasi.
--    Baris di sini dipakai untuk OVERRIDE manual saja.
CREATE TABLE IF NOT EXISTS jadwal_security (
    id_karyawan INT NOT NULL,
    tanggal     DATE NOT NULL,
    keterangan  ENUM('Kerja','Libur') NOT NULL,
    PRIMARY KEY (id_karyawan, tanggal),
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON DELETE CASCADE
);

-- 3) Kalau kolom tipe_karyawan Anda didefinisikan sebagai ENUM (bukan VARCHAR),
--    jalankan ini juga supaya nilai 'Security' diizinkan:
-- ALTER TABLE karyawan MODIFY tipe_karyawan ENUM('Shift','Non-Shift','Security') NOT NULL DEFAULT 'Shift';
