-- ============================================
-- SIAMIN Database - Dummy Data (5 records each)
-- ============================================

-- 1. INSERT INTO PEGAWAI (5 records)
INSERT INTO pegawai (nip, nama, tempat_lahir, tanggal_lahir, tmt_cpns, tmt_pangkat, pangkat, golongan, nama_jabatan, tmt_jabatan, unit_kerja, pendidikan_terakhir, jurusan, tempat_pendidikan, tahun_lulus, masa_kerja_tahun, masa_kerja_bulan, latihan_jabatan, perkiraan_pensiun, status_kepegawaian, status, created_at, updated_at) VALUES
('197803152005011001', 'Dr. Bambang Sutrisno', 'Jakarta', '1978-03-15', '2005-01-15', '2020-04-01', 'Pembina Tingkat I', 'IV/b', 'Kepala Biro', '2022-01-01', 'Biro Administrasi', 'S3', 'Administrasi Publik', 'Universitas Indonesia', 2003, 19, 0, 'Diklat Kepemimpinan Tingkat II', 2033, 'PNS', 'aktif', NOW(), NOW()),
('198204201988032001', 'Ir. Siti Nurhaliza', 'Bandung', '1982-04-20', '1988-03-20', '2018-07-15', 'Pembina', 'IV/a', 'Kepala Bidang', '2021-06-01', 'Bidang Program dan Evaluasi', 'S2', 'Teknik Sipil', 'ITB Bandung', 2006, 18, 6, 'Diklat Kepemimpinan Tingkat III', 2032, 'PNS', 'aktif', NOW(), NOW()),
('198501152010011001', 'Budi Santoso, S.Kom', 'Surabaya', '1985-01-15', '2010-01-15', '2021-03-01', 'Penata', 'III/d', 'Staff', '2020-01-01', 'Bidang IT', 'S1', 'Teknik Informatika', 'Universitas Surabaya', 2007, 14, 0, 'Diklat Fungsional IT', 2027, 'PNS', 'aktif', NOW(), NOW()),
('198703102015021002', 'Eka Putri Wijaya', 'Yogyakarta', '1987-03-10', '2015-02-01', '2022-08-01', 'Penata Muda', 'III/b', 'Analis', '2019-06-01', 'Bidang Perencanaan', 'S1', 'Perencanaan Pembangunan', 'UGM', 2009, 9, 4, 'Diklat Analis Kebijakan', 2024, 'PPPK', 'aktif', NOW(), NOW()),
('199012251920023003', 'Muhammad Rizki Pratama', 'Medan', '1990-12-25', '2019-12-15', '2024-01-01', 'Penata Muda Tingkat I', 'III/c', 'Operator', '2022-03-01', 'Bidang Administrasi Data', 'S1', 'Administrasi Negara', 'Universitas Sumatera Utara', 2012, 4, 1, 'Diklat Operator Data', 2025, 'PPPK', 'aktif', NOW(), NOW());

-- 2. INSERT INTO UNIT_KERJA (5 records)
INSERT INTO unit_kerja (kode_unit, nama_unit, jenis_unit, tahun, keterangan, created_at, updated_at) VALUES
('BA', 'Biro Administrasi', 'utama', 2026, 'Biro yang menangani administrasi umum dan tata usaha', NOW(), NOW()),
('BPE', 'Bidang Program dan Evaluasi', 'utama', 2026, 'Bidang yang mengurus program, perencanaan dan evaluasi kegiatan', NOW(), NOW()),
('BIT', 'Bidang IT', 'pendukung', 2026, 'Bidang yang menangani infrastruktur teknologi informasi', NOW(), NOW()),
('BP', 'Bidang Perencanaan', 'utama', 2026, 'Bidang yang menangani perencanaan strategis', NOW(), NOW()),
('BAD', 'Bidang Administrasi Data', 'pendukung', 2026, 'Bidang yang mengelola data dan basis data organisasi', NOW(), NOW());

-- 3. INSERT INTO SUB_UNIT_KERJA (5 records)
INSERT INTO sub_unit_kerja (unit_kerja_id, nama_sub_unit, fungsi, created_at, updated_at) VALUES
(1, 'Bagian Tata Usaha', 'Menangani tata usaha, administrasi umum, dan surat-menyurat', NOW(), NOW()),
(1, 'Bagian Keuangan', 'Mengelola keuangan, anggaran, dan gaji pegawai', NOW(), NOW()),
(2, 'Seksi Program', 'Menyusun dan mengontrol program kegiatan', NOW(), NOW()),
(2, 'Seksi Evaluasi', 'Melakukan evaluasi terhadap pelaksanaan program', NOW(), NOW()),
(3, 'Seksi Infrastruktur', 'Mengelola infrastruktur dan jaringan komputer', NOW(), NOW());

-- 4. INSERT INTO USERS (5 records)
INSERT INTO users (id_pegawai, email, password, role, last_login, status, created_at, updated_at) VALUES
(1, 'bambang.sutrisno@kemkominfo.go.id', '$2y$10$hashedpassword1234567890', 'admin', NOW(), 'aktif', NOW(), NOW()),
(2, 'siti.nurhaliza@kemkominfo.go.id', '$2y$10$hashedpassword1234567890', 'kepala', NOW(), 'aktif', NOW(), NOW()),
(3, 'budi.santoso@kemkominfo.go.id', '$2y$10$hashedpassword1234567890', 'operator', '2026-01-23 10:30:00', 'aktif', NOW(), NOW()),
(4, 'eka.putri@kemkominfo.go.id', '$2y$10$hashedpassword1234567890', 'verifikator', '2026-01-22 14:15:00', 'aktif', NOW(), NOW()),
(5, 'rizki.pratama@kemkominfo.go.id', '$2y$10$hashedpassword1234567890', 'operator', '2026-01-21 09:45:00', 'aktif', NOW(), NOW());

-- 5. INSERT INTO KEGIATAN (5 records)
INSERT INTO kegiatan (nama_kegiatan, rincian_kegiatan, dokumentasi_url, materi_url, panduan_url, laporan_url, surat_menyurat_url, tanggal_mulai, tanggal_selesai, lokasi, flyer, peserta_ringkasan, total_peserta, metode_pembayaran, deskripsi, metode_pelaksanaan, status, created_by, created_at, updated_at) VALUES
('Pelatihan Dasar CPNS Angkatan I', 'Pelatihan dasar untuk CPNS angkatan I tahun 2025 meliputi materi etika, integritas, dan layanan publik', 'https://api.example.com/dokumentasi/K001', 'https://api.example.com/materi/K001', 'https://api.example.com/panduan/K001', 'https://api.example.com/laporan/K001', 'https://api.example.com/surat/K001', '2025-02-01', '2025-02-28', 'Jakarta', 'https://api.example.com/flyer/K001.jpg', 'CPNS', 45, 'tunai', 'Pelatihan komprehensif untuk CPNS baru dengan materi etika dan integritas', 'hybrid', 'selesai', 1, NOW(), NOW()),
('Workshop Digital Marketing', 'Workshop tentang strategi digital marketing untuk meningkatkan engagement media sosial organisasi', 'https://api.example.com/dokumentasi/K002', 'https://api.example.com/materi/K002', 'https://api.example.com/panduan/K002', 'https://api.example.com/laporan/K002', 'https://api.example.com/surat/K002', '2025-03-10', '2025-03-12', 'Bandung', 'https://api.example.com/flyer/K002.jpg', 'PNS', 30, 'transfer', 'Workshop interaktif tentang tren marketing digital terkini', 'luring', 'berjalan', 2, NOW(), NOW()),
('Seminar Transformasi Digital', 'Seminar tentang transformasi digital dalam organisasi pemerintah', 'https://api.example.com/dokumentasi/K003', 'https://api.example.com/materi/K003', 'https://api.example.com/panduan/K003', 'https://api.example.com/laporan/K003', 'https://api.example.com/surat/K003', '2026-02-15', '2026-02-16', 'Jakarta', 'https://api.example.com/flyer/K003.jpg', 'Pegawai Umum', 100, 'transfer_dan_pulsa', 'Seminar dengan pembicara internasional tentang digital transformation', 'hybrid', 'draft', 1, NOW(), NOW()),
('Pelatihan Customer Service Excellence', 'Pelatihan untuk meningkatkan kualitas layanan pelanggan', 'https://api.example.com/dokumentasi/K004', 'https://api.example.com/materi/K004', 'https://api.example.com/panduan/K004', 'https://api.example.com/laporan/K004', 'https://api.example.com/surat/K004', '2026-04-01', '2026-04-03', 'Surabaya', 'https://api.example.com/flyer/K004.jpg', 'Staff Layanan', 40, 'pulsa', 'Pelatihan intensif tentang customer service terbaik', 'luring', 'draft', 3, NOW(), NOW()),
('Forum Diskusi Kebijakan Publik', 'Forum diskusi tentang kebijakan publik dan dampaknya terhadap masyarakat', 'https://api.example.com/dokumentasi/K005', 'https://api.example.com/materi/K005', 'https://api.example.com/panduan/K005', 'https://api.example.com/laporan/K005', 'https://api.example.com/surat/K005', '2026-05-20', '2026-05-21', 'Yogyakarta', 'https://api.example.com/flyer/K005.jpg', 'Analis Kebijakan', 35, 'tidak_dibayar', 'Forum terbuka untuk berbagi pemikiran tentang kebijakan publik', 'daring', 'draft', 4, NOW(), NOW());

-- 6. INSERT INTO SURAT_TUGAS (5 records)
INSERT INTO surat_tugas (id_kegiatan, nomor_surat, tanggal_surat, id_penandatangan, status, file_surat, created_at, updated_at) VALUES
(1, 'ST-001/2025', '2025-01-25', 1, 'diterbitkan', 'https://api.example.com/surat-tugas/ST-001-2025.pdf', NOW(), NOW()),
(2, 'ST-002/2025', '2025-03-05', 2, 'diterbitkan', 'https://api.example.com/surat-tugas/ST-002-2025.pdf', NOW(), NOW()),
(3, 'ST-003/2026', '2026-02-10', 1, 'draft', 'https://api.example.com/surat-tugas/ST-003-2026.pdf', NOW(), NOW()),
(4, 'ST-004/2026', '2026-03-25', 2, 'draft', 'https://api.example.com/surat-tugas/ST-004-2026.pdf', NOW(), NOW()),
(5, 'ST-005/2026', '2026-05-15', 1, 'dibatalkan', NULL, NOW(), NOW());

-- 7. INSERT INTO SURAT_TUGAS_PEGAWAI (5 records)
INSERT INTO surat_tugas_pegawai (id_surat_tugas, id_pegawai, peran) VALUES
(1, 1, 'penanggung_jawab'),
(1, 2, 'ketua_panitia'),
(2, 3, 'panitia'),
(3, 4, 'narasumber'),
(4, 5, 'peserta');

-- 8. INSERT INTO PESERTA (5 records)
INSERT INTO peserta (id_kegiatan, nama_lengkap, nip, pangkat, gol, jabatan, no_hp, email, npwp_nik, tempat_lahir, tanggal_lahir, jenis_kelamin, nama_instansi, alamat_instansi, kab_kota, provinsi, telp_instansi, email_instansi, provider_pulsa, nomor_rekening, nama_bank, no_surat_tugas, tanggal_surat_tugas, peran, tanda_tangan, created_at, updated_at) VALUES
(1, 'Budi Santoso', '198501152010011001', 'Penata Muda', 'III/a', 'Staff', '081234567890', 'budi.santoso@kemkominfo.go.id', '1985011500000001', 'Jakarta', '1985-01-15', 'Laki-laki', 'Kemkominfo', 'Jl. Medan Merdeka Barat No.9, Jakarta Pusat', 'Jakarta Pusat', 'DKI Jakarta', '02134819000', 'info@kemkominfo.go.id', NULL, '1234567890', 'Bank Mandiri', 'ST-001/2025', '2025-02-01', 'Peserta', NULL, NOW(), NOW()),
(1, 'Sri Rahayuni', '197909201995032002', 'Penata', 'III/c', 'Analis', '082345678901', 'sri.rahayuni@kemkominfo.go.id', '1979092000000002', 'Bandung', '1979-09-20', 'Perempuan', 'Kemkominfo', 'Jl. Medan Merdeka Barat No.9, Jakarta Pusat', 'Jakarta Pusat', 'DKI Jakarta', '02134819000', 'info@kemkominfo.go.id', 'Telkomsel', '2345678901', 'Bank BRI', 'ST-001/2025', '2025-02-01', 'Peserta', NULL, NOW(), NOW()),
(2, 'Ahmad Hidayat', '198102101998021003', 'Penata Muda Tingkat I', 'III/b', 'Operator', '083456789012', 'ahmad.hidayat@kemkominfo.go.id', '1981021000000003', 'Medan', '1981-02-10', 'Laki-laki', 'Kemkominfo', 'Jl. Medan Merdeka Barat No.9, Jakarta Pusat', 'Jakarta Pusat', 'DKI Jakarta', '02134819000', 'info@kemkominfo.go.id', 'Indosat', '3456789012', 'Bank BCA', 'ST-002/2025', '2025-03-10', 'Peserta', NULL, NOW(), NOW()),
(3, 'Dewi Lestari', '198403151999012004', 'Penata', 'III/c', 'Koordinator', '084567890123', 'dewi.lestari@kemkominfo.go.id', '1984031500000004', 'Semarang', '1984-03-15', 'Perempuan', 'Kemkominfo', 'Jl. Medan Merdeka Barat No.9, Jakarta Pusat', 'Jakarta Pusat', 'DKI Jakarta', '02134819000', 'info@kemkominfo.go.id', NULL, '4567890123', 'Bank Mandiri', 'ST-003/2026', '2026-02-15', 'Peserta', NULL, NOW(), NOW()),
(4, 'Hendri Gunawan', '198605271997022005', 'Penata Muda', 'III/a', 'Staff', '085678901234', 'hendri.gunawan@kemkominfo.go.id', '1986052700000005', 'Palembang', '1986-05-27', 'Laki-laki', 'Kemkominfo', 'Jl. Medan Merdeka Barat No.9, Jakarta Pusat', 'Jakarta Pusat', 'DKI Jakarta', '02134819000', 'info@kemkominfo.go.id', 'XL Axiata', '5678901234', 'Bank CIMB', 'ST-004/2026', '2026-04-01', 'Peserta', NULL, NOW(), NOW());

-- 9. INSERT INTO AKUN_PESERTA (5 records)
INSERT INTO akun_peserta (id_peserta, username, password, last_login, status, created_at, updated_at) VALUES
(1, 'budi_santoso', '$2y$10$hashedpassword123456789012', '2026-01-20 10:30:00', 'aktif', NOW(), NOW()),
(2, 'sri_rahayuni', '$2y$10$hashedpassword123456789012', '2026-01-19 14:15:00', 'aktif', NOW(), NOW()),
(3, 'ahmad_hidayat', '$2y$10$hashedpassword123456789012', '2026-01-18 09:45:00', 'aktif', NOW(), NOW()),
(4, 'dewi_lestari', '$2y$10$hashedpassword123456789012', NULL, 'nonaktif', NOW(), NOW()),
(5, 'hendri_gunawan', '$2y$10$hashedpassword123456789012', '2026-01-17 11:20:00', 'aktif', NOW(), NOW());

-- 10. INSERT INTO SERTIFIKAT (5 records)
INSERT INTO sertifikat (id_kegiatan, id_peserta, nomor_sertifikat, tanggal_ttd, id_penandatangan, template, peran, status, created_at, updated_at) VALUES
(1, 1, 'CERT-2025-001', '2025-02-28', 1, 'template_1', 'Peserta', 'terbit', NOW(), NOW()),
(1, 2, 'CERT-2025-002', '2025-02-28', 1, 'template_1', 'Peserta', 'terbit', NOW(), NOW()),
(2, 3, 'CERT-2025-003', '2025-03-15', 2, 'template_2', 'Peserta', 'terbit', NOW(), NOW()),
(3, 4, 'CERT-2026-001', NULL, 1, 'template_1', 'Peserta', 'draft', NOW(), NOW()),
(4, 5, 'CERT-2026-002', NULL, 2, 'template_2', 'Peserta', 'draft', NOW(), NOW());

-- 11. INSERT INTO LOG_AKTIVITAS (5 records)
INSERT INTO log_aktivitas (id_user, aktivitas, tabel, id_referensi, created_at) VALUES
(1, 'Membuat kegiatan baru: Pelatihan Dasar CPNS Angkatan I', 'kegiatan', 1, '2025-01-10 08:30:00'),
(2, 'Menerbitkan surat tugas: ST-001/2025', 'surat_tugas', 1, '2025-01-25 10:15:00'),
(3, 'Menambahkan peserta baru: Budi Santoso', 'peserta', 1, '2025-02-01 09:45:00'),
(1, 'Membuat sertifikat untuk peserta: Budi Santoso', 'sertifikat', 1, '2025-02-28 14:30:00'),
(4, 'Verifikasi data peserta', 'peserta', 2, '2026-01-20 11:00:00');

-- 12. INSERT INTO KEANGGOTAN_TIM (5 records)
INSERT INTO keanggotan_tim (id_pegawai, unit_kerja_id, sub_unit_kerja_id, peran, tahun) VALUES
(1, 1, 1, 'Kepala', 2026),
(2, 2, 3, 'Ketua', 2026),
(3, 3, 5, 'Anggota', 2026),
(4, 2, 4, 'Anggota', 2026),
(5, 5, NULL, 'Anggota', 2026);
