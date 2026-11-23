-- ============================================================
-- Absensi SDN Tulungrejo 01
-- Database : petugas_piket
-- Engine   : MariaDB 10.4 (XAMPP) / MySQL 5.7+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `petugas_piket`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `petugas_piket`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabel: users (admin & guru/petugas piket)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user`    INT(11) NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(30) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `nama`       VARCHAR(100) NOT NULL,
  `nip`        VARCHAR(18) DEFAULT NULL,
  `jabatan`    VARCHAR(50) DEFAULT 'Guru',
  `hak_akses`  ENUM('admin','guru') NOT NULL DEFAULT 'guru',
  `foto`       VARCHAR(255) DEFAULT NULL,
  `aktif`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: rombel (ruang kelas)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `rombel`;
CREATE TABLE `rombel` (
  `id_rombel`  INT(11) NOT NULL AUTO_INCREMENT,
  `kode_rombel` VARCHAR(10) NOT NULL,
  `nama_rombel` VARCHAR(50) NOT NULL,
  `tingkat`    TINYINT(2) NOT NULL,
  `wali_kelas` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_rombel`),
  UNIQUE KEY `uk_kode_rombel` (`kode_rombel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: siswa
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `nis`         VARCHAR(12) NOT NULL,
  `nisn`        VARCHAR(10) DEFAULT NULL,
  `nama`        VARCHAR(100) NOT NULL,
  `jk`          ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(50) DEFAULT NULL,
  `tgl_lahir`   DATE DEFAULT NULL,
  `alamat`      VARCHAR(150) DEFAULT NULL,
  `no_hp_ortu`  VARCHAR(15) DEFAULT NULL,
  `id_rombel`   INT(11) NOT NULL,
  `aktif`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`nis`),
  KEY `fk_siswa_rombel` (`id_rombel`),
  CONSTRAINT `fk_siswa_rombel` FOREIGN KEY (`id_rombel`)
      REFERENCES `rombel` (`id_rombel`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: absensi (kehadiran siswa harian)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `absensi`;
CREATE TABLE `absensi` (
  `id_absen`    BIGINT(20) NOT NULL AUTO_INCREMENT,
  `tgl_absen`   DATE NOT NULL,
  `nis`         VARCHAR(12) NOT NULL,
  `status`      ENUM('H','I','S','A') NOT NULL DEFAULT 'H', -- H=Hadir I=Izin S=Sakit A=Alpa
  `keterangan`  VARCHAR(150) DEFAULT NULL,
  `id_user`     INT(11) DEFAULT NULL,                        -- petugas yang mengisi
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_absen`),
  UNIQUE KEY `uk_absen` (`tgl_absen`, `nis`),
  KEY `fk_absen_siswa` (`nis`),
  KEY `fk_absen_user` (`id_user`),
  CONSTRAINT `fk_absen_siswa` FOREIGN KEY (`nis`)
      REFERENCES `siswa` (`nis`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_absen_user` FOREIGN KEY (`id_user`)
      REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: piket (jadwal petugas piket guru)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `piket`;
CREATE TABLE `piket` (
  `id_piket`   INT(11) NOT NULL AUTO_INCREMENT,
  `hari`       ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `id_user`    INT(11) NOT NULL,
  `pos`        VARCHAR(50) DEFAULT 'Gerbang Utama',
  `jam_masuk`  TIME DEFAULT '06:30:00',
  `jam_pulang` TIME DEFAULT '14:00:00',
  `keterangan` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`id_piket`),
  UNIQUE KEY `uk_piket` (`hari`, `id_user`, `pos`),
  KEY `fk_piket_user` (`id_user`),
  CONSTRAINT `fk_piket_user` FOREIGN KEY (`id_user`)
      REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: pengaturan (profil sekolah & tahun pelajaran)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `nama_sekolah`    VARCHAR(100) NOT NULL DEFAULT 'SDN Tulungrejo 01',
  `npsn`            VARCHAR(16) DEFAULT NULL,
  `alamat`          VARCHAR(150) DEFAULT NULL,
  `kepala_sekolah`  VARCHAR(100) DEFAULT NULL,
  `tahun_pelajaran` VARCHAR(9)  NOT NULL DEFAULT '2025/2026',
  `semester`        ENUM('Ganjil','Genap') NOT NULL DEFAULT 'Ganjil',
  `logo`            VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL (seed)
-- ============================================================

-- User admin (username: admin / password: admin123)
INSERT INTO `users` (`username`,`password`,`nama`,`nip`,`jabatan`,`hak_akses`) VALUES
('admin','$2y$10$IGjrrFtD3m0msbHfHpUH9.4b/jhTkQdsalQJmtjqwN9.oHYweRfji','Administrator',NULL,'Admin','admin'),
-- Guru (username: guru1..guru6 / password: guru123)
('guru1','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Siti Aminah, S.Pd','197504121998032004','Wali Kelas 1','guru'),
('guru2','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Bambang Sutrisno, S.Pd','197801152001121003','Wali Kelas 2','guru'),
('guru3','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Dwi Hastuti, S.Pd','198003082005122002','Wali Kelas 3','guru'),
('guru4','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Agus Priyanto, S.Pd','198212012008011005','Wali Kelas 4','guru'),
('guru5','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Nur Kholifah, S.Pd','198505202010012007','Wali Kelas 5','guru'),
('guru6','$2y$10$UPKEASpOc6AvDbjZieEW3eNGITi./HtpzAysmnfuFvAkNk/59IfB6','Hendra Wijaya, S.Pd','198707172012011009','Wali Kelas 6','guru');

INSERT INTO `rombel` (`kode_rombel`,`nama_rombel`,`tingkat`,`wali_kelas`) VALUES
('1A','Kelas 1A',1,'Siti Aminah, S.Pd'),
('2A','Kelas 2A',2,'Bambang Sutrisno, S.Pd'),
('3A','Kelas 3A',3,'Dwi Hastuti, S.Pd'),
('4A','Kelas 4A',4,'Agus Priyanto, S.Pd'),
('5A','Kelas 5A',5,'Nur Kholifah, S.Pd'),
('6A','Kelas 6A',6,'Hendra Wijaya, S.Pd');

INSERT INTO `siswa` (`nis`,`nisn`,`nama`,`jk`,`tempat_lahir`,`tgl_lahir`,`alamat`,`no_hp_ortu`,`id_rombel`) VALUES
('2410001','0101234501','Ahmad Fauzi','L','Blitar','2018-03-12','Tulungrejo','081234567801',1),
('2410002','0101234502','Siti Maryam','P','Blitar','2018-05-20','Tulungrejo','081234567802',1),
('2410003','0101234503','Rizky Ramadhan','L','Blitar','2018-01-08','Tulungrejo','081234567803',1),
('2410004','0101234504','Dewi Anggraini','P','Blitar','2018-09-17','Kalimanis','081234567804',1),
('2410005','0101234505','Muhammad Rizal','L','Blitar','2018-11-02','Tulungrejo','081234567805',1),
('2420001','0101234506','Nadia Putri','P','Blitar','2017-04-14','Tulungrejo','081234567806',2),
('2420002','0101234507','Bagas Prakoso','L','Blitar','2017-06-25','Kepuh','081234567807',2),
('2420003','0101234508','Aisyah Nur','P','Blitar','2017-08-09','Tulungrejo','081234567808',2),
('2420004','0101234509','Yoga Saputra','L','Blitar','2017-12-01','Tulungrejo','081234567809',2),
('2420005','0101234510','Intan Permata','P','Blitar','2017-02-23','Kalimanis','081234567810',2),
('2430001','0101234511','Andika Putra','L','Blitar','2016-03-30','Tulungrejo','081234567811',3),
('2430002','0101234512','Fitri Handayani','P','Blitar','2016-07-11','Kepuh','081234567812',3),
('2430003','0101234513','Rendi Kurniawan','L','Blitar','2016-10-05','Tulungrejo','081234567813',3),
('2430004','0101234514','Salma Oktavia','P','Blitar','2016-12-19','Tulungrejo','081234567814',3),
('2430005','0101234515','Galih Pratama','L','Blitar','2016-05-27','Kalimanis','081234567815',3),
('2440001','0101234516','Fajar Nugroho','L','Blitar','2015-02-16','Tulungrejo','081234567816',4),
('2440002','0101234517','Anisa Rahmawati','P','Blitar','2015-06-08','Tulungrejo','081234567817',4),
('2440003','0101234518','Dimas Aryo','L','Blitar','2015-09-21','Kepuh','081234567818',4),
('2440004','0101234519','Laila Vania','P','Blitar','2015-11-13','Tulungrejo','081234567819',4),
('2440005','0101234520','Bayu Setiawan','L','Blitar','2015-04-04','Kalimanis','081234567820',4),
('2450001','0101234521','Reza Maulana','L','Blitar','2014-01-25','Tulungrejo','081234567821',5),
('2450002','0101234522','Putri Amelia','P','Blitar','2014-03-17','Tulungrejo','081234567822',5),
('2450003','0101234523','Irfan Hakim','L','Blitar','2014-08-30','Kepuh','081234567823',5),
('2450004','0101234524','Mega Lestari','P','Blitar','2014-10-12','Tulungrejo','081234567824',5),
('2450005','0101234525','Arif Budiman','L','Blitar','2014-12-05','Kalimanis','081234567825',5),
('2460001','0101234526','Gilang Ramadhan','L','Blitar','2013-02-09','Tulungrejo','081234567826',6),
('2460002','0101234527','Zahra Aulia','P','Blitar','2013-05-23','Tulungrejo','081234567827',6),
('2460003','0101234528','Panji Nugraha','L','Blitar','2013-07-14','Kepuh','081234567828',6),
('2460004','0101234529','Salsabila Ayu','P','Blitar','2013-09-28','Tulungrejo','081234567829',6),
('2460005','0101234530','Wahyu Firmansyah','L','Blitar','2013-11-16','Kalimanis','081234567830',6);

INSERT INTO `piket` (`hari`,`id_user`,`pos`,`jam_masuk`,`jam_pulang`) VALUES
('Senin',1,'Gerbang Utama','06:30:00','14:00:00'),
('Selasa',2,'Gerbang Utama','06:30:00','14:00:00'),
('Rabu',3,'Gerbang Utama','06:30:00','14:00:00'),
('Kamis',4,'Gerbang Utama','06:30:00','14:00:00'),
('Jumat',5,'Gerbang Utama','06:30:00','11:30:00'),
('Sabtu',6,'Perpustakaan','06:30:00','10:00:00');

INSERT INTO `pengaturan` (`nama_sekolah`,`npsn`,`alamat`,`kepala_sekolah`,`tahun_pelajaran`,`semester`) VALUES
('SDN Tulungrejo 01','20528722','Jl. Tulungrejo No. 01, Blitar','Drs. Suryadi, M.Pd','2025/2026','Ganjil');
