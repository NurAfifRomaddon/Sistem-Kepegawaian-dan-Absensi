-- =========================================================
-- DATABASE: sistem informasi absensi karyawan & stok barang
-- =========================================================
CREATE DATABASE IF NOT EXISTS simpeg_absensi_barang
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simpeg_absensi_barang;

-- ---------------------------------------------------------
-- TABEL KARYAWAN
-- ---------------------------------------------------------
CREATE TABLE karyawan (
  id_karyawan   INT AUTO_INCREMENT PRIMARY KEY,
  nama_karyawan VARCHAR(100) NOT NULL,
  jabatan       VARCHAR(50)  DEFAULT '-',
  no_hp         VARCHAR(20)  DEFAULT NULL,
  alamat        TEXT         DEFAULT NULL,
  status        ENUM('Aktif','Non Aktif') NOT NULL DEFAULT 'Aktif',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL USER (login sistem)
-- ---------------------------------------------------------
CREATE TABLE users (
  id_user     INT AUTO_INCREMENT PRIMARY KEY,
  id_karyawan INT NULL,
  username    VARCHAR(50) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_karyawan FOREIGN KEY (id_karyawan)
      REFERENCES karyawan(id_karyawan) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL ABSENSI (Hadir, Cuti, Izin, Alpha)
-- ---------------------------------------------------------
CREATE TABLE absensi (
  id_absensi  INT AUTO_INCREMENT PRIMARY KEY,
  id_karyawan INT NOT NULL,
  tanggal     DATE NOT NULL,
  jam_masuk   TIME NULL,
  jam_keluar  TIME NULL,
  status      ENUM('Hadir','Cuti','Izin','Alpha') NOT NULL,
  keterangan  VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_absensi_karyawan FOREIGN KEY (id_karyawan)
      REFERENCES karyawan(id_karyawan) ON DELETE CASCADE,
  UNIQUE KEY uniq_absen_harian (id_karyawan, tanggal)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL BARANG (master stok)
-- ---------------------------------------------------------
CREATE TABLE barang (
  id_barang    INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang  VARCHAR(20) NOT NULL UNIQUE,
  nama_barang  VARCHAR(100) NOT NULL,
  satuan       VARCHAR(20) NOT NULL DEFAULT 'Pcs',
  stok         INT NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL BARANG MASUK
-- ---------------------------------------------------------
CREATE TABLE barang_masuk (
  id_masuk    INT AUTO_INCREMENT PRIMARY KEY,
  id_barang   INT NOT NULL,
  tanggal     DATE NOT NULL,
  jumlah      INT NOT NULL,
  keterangan  VARCHAR(255) DEFAULT NULL,
  id_user     INT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_masuk_barang FOREIGN KEY (id_barang)
      REFERENCES barang(id_barang) ON DELETE CASCADE,
  CONSTRAINT fk_masuk_user FOREIGN KEY (id_user)
      REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL BARANG KELUAR
-- ---------------------------------------------------------
CREATE TABLE barang_keluar (
  id_keluar   INT AUTO_INCREMENT PRIMARY KEY,
  id_barang   INT NOT NULL,
  tanggal     DATE NOT NULL,
  jumlah      INT NOT NULL,
  keperluan   VARCHAR(255) DEFAULT NULL,
  id_user     INT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_keluar_barang FOREIGN KEY (id_barang)
      REFERENCES barang(id_barang) ON DELETE CASCADE,
  CONSTRAINT fk_keluar_user FOREIGN KEY (id_user)
      REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL PENGAJUAN CUTI/IZIN (self-service karyawan)
-- ---------------------------------------------------------
CREATE TABLE pengajuan (
  id_pengajuan   INT AUTO_INCREMENT PRIMARY KEY,
  id_karyawan    INT NOT NULL,
  jenis          ENUM('Cuti','Izin') NOT NULL,
  tanggal_mulai  DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  alasan         TEXT DEFAULT NULL,
  status         ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  diproses_oleh  INT DEFAULT NULL,
  diproses_at    TIMESTAMP NULL DEFAULT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pengajuan_karyawan FOREIGN KEY (id_karyawan)
      REFERENCES karyawan(id_karyawan) ON DELETE CASCADE,
  CONSTRAINT fk_pengajuan_user FOREIGN KEY (diproses_oleh)
      REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- DATA AWAL
-- =========================================================

-- akun admin default -> username: admin | password: admin123
INSERT INTO users (id_karyawan, username, password, role) VALUES
(NULL, 'admin', '$2y$10$6Vr/Ju7x88vfMCxSA96ouen4hZ/cGB7uxmsGR/xCwUN5h57Zd0xcS', 'admin');

-- contoh data karyawan
INSERT INTO karyawan (nama_karyawan, jabatan, no_hp, alamat, status) VALUES
('Andi Saputra', 'Staff Gudang', '081234567890', 'Jl. Merdeka No. 1, Jakarta', 'Aktif'),
('Siti Rahma', 'Admin Kantor', '081234567891', 'Jl. Sudirman No. 5, Jakarta', 'Aktif'),
('Budi Santoso', 'Teknisi', '081234567892', 'Jl. Gatot Subroto No. 9, Jakarta', 'Aktif');

-- contoh data barang
INSERT INTO barang (kode_barang, nama_barang, satuan, stok, stok_minimum) VALUES
('BRG001', 'Kertas A4', 'Rim', 50, 10),
('BRG002', 'Tinta Printer', 'Botol', 20, 5),
('BRG003', 'Spare Part Motor', 'Unit', 15, 3);

-- akun karyawan contoh -> username: staff1 | password: staff123 (dihubungkan ke karyawan Andi Saputra)
INSERT INTO users (id_karyawan, username, password, role) VALUES
(1, 'staff1', '$2y$10$U9lUQfRWtdqkdtywzVIIbOELqqcxGxbGLyodYk8ljldCWxJvtGwb2', 'karyawan');
