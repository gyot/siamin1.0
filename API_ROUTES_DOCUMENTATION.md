# 📋 SIAMIN API Routes Documentation

**Base URL:** `http://127.0.0.1:8000/api/v1`

---

## 📌 API Endpoints

### 1. **Pegawai** - Daftar Pegawai
```
GET /api/v1/pegawai
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data pegawai berhasil diambil",
  "data": [
    {
      "id_pegawai": 1,
      "nip": "197803152005011001",
      "nama": "Dr. Bambang Sutrisno",
      "tempat_lahir": "Jakarta",
      "tanggal_lahir": "1978-03-15",
      "tmt_cpns": "2005-01-15",
      "tmt_pangkat": "2020-04-01",
      "pangkat": "Pembina Tingkat I",
      "golongan": "IV/b",
      "nama_jabatan": "Kepala Biro",
      "tmt_jabatan": "2022-01-01",
      "unit_kerja": "Biro Administrasi",
      "pendidikan_terakhir": "S3",
      "jurusan": "Administrasi Publik",
      "tempat_pendidikan": "Universitas Indonesia",
      "tahun_lulus": 2003,
      "masa_kerja_tahun": 19,
      "masa_kerja_bulan": 0,
      "latihan_jabatan": "Diklat Kepemimpinan Tingkat II",
      "perkiraan_pensiun": 2033,
      "status_kepegawaian": "PNS",
      "status": "aktif",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z"
    }
  ],
  "total": 5
}
```

---

### 2. **Users** - Daftar User
```
GET /api/v1/users
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data user berhasil diambil",
  "data": [
    {
      "id_user": 1,
      "id_pegawai": 1,
      "email": "bambang.sutrisno@kemkominfo.go.id",
      "role": "admin",
      "last_login": "2026-01-23T09:35:00Z",
      "status": "aktif",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "pegawai": {
        "id_pegawai": 1,
        "nip": "197803152005011001",
        "nama": "Dr. Bambang Sutrisno"
      }
    }
  ],
  "total": 5
}
```

---

### 3. **Kegiatan** - Daftar Kegiatan
```
GET /api/v1/kegiatan
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data kegiatan berhasil diambil",
  "data": [
    {
      "id_kegiatan": 1,
      "nama_kegiatan": "Pelatihan Dasar CPNS Angkatan I",
      "rincian_kegiatan": "Pelatihan dasar untuk CPNS angkatan I tahun 2025...",
      "dokumentasi_url": "https://api.example.com/dokumentasi/K001",
      "materi_url": "https://api.example.com/materi/K001",
      "panduan_url": "https://api.example.com/panduan/K001",
      "laporan_url": "https://api.example.com/laporan/K001",
      "surat_menyurat_url": "https://api.example.com/surat/K001",
      "tanggal_mulai": "2025-02-01",
      "tanggal_selesai": "2025-02-28",
      "lokasi": "Jakarta",
      "flyer": "https://api.example.com/flyer/K001.jpg",
      "peserta_ringkasan": "CPNS",
      "total_peserta": 45,
      "metode_pembayaran": "tunai",
      "deskripsi": "Pelatihan komprehensif untuk CPNS baru...",
      "metode_pelaksanaan": "hybrid",
      "status": "selesai",
      "created_by": 1,
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "creator": {
        "id_user": 1,
        "email": "bambang.sutrisno@kemkominfo.go.id"
      }
    }
  ],
  "total": 5
}
```

---

### 4. **Peserta** - Daftar Peserta
```
GET /api/v1/peserta
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data peserta berhasil diambil",
  "data": [
    {
      "id_peserta": 1,
      "id_kegiatan": 1,
      "nama_lengkap": "Budi Santoso",
      "nip": "198501152010011001",
      "pangkat": "Penata Muda",
      "gol": "III/a",
      "jabatan": "Staff",
      "no_hp": "081234567890",
      "email": "budi.santoso@kemkominfo.go.id",
      "npwp_nik": "1985011500000001",
      "tempat_lahir": "Jakarta",
      "tanggal_lahir": "1985-01-15",
      "jenis_kelamin": "Laki-laki",
      "nama_instansi": "Kemkominfo",
      "alamat_instansi": "Jl. Medan Merdeka Barat No.9, Jakarta Pusat",
      "kab_kota": "Jakarta Pusat",
      "provinsi": "DKI Jakarta",
      "telp_instansi": "02134819000",
      "email_instansi": "info@kemkominfo.go.id",
      "provider_pulsa": null,
      "nomor_rekening": "1234567890",
      "nama_bank": "Bank Mandiri",
      "no_surat_tugas": "ST-001/2025",
      "tanggal_surat_tugas": "2025-02-01",
      "peran": "Peserta",
      "tanda_tangan": null,
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "kegiatan": {
        "id_kegiatan": 1,
        "nama_kegiatan": "Pelatihan Dasar CPNS Angkatan I"
      }
    }
  ],
  "total": 5
}
```

---

### 5. **Akun Peserta** - Daftar Akun Peserta
```
GET /api/v1/akun-peserta
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data akun peserta berhasil diambil",
  "data": [
    {
      "id_akun_peserta": 1,
      "id_peserta": 1,
      "username": "budi_santoso",
      "last_login": "2026-01-20T10:30:00Z",
      "status": "aktif",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "peserta": {
        "id_peserta": 1,
        "nama_lengkap": "Budi Santoso"
      }
    }
  ],
  "total": 5
}
```

---

### 6. **Surat Tugas** - Daftar Surat Tugas
```
GET /api/v1/surat-tugas
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data surat tugas berhasil diambil",
  "data": [
    {
      "id_surat_tugas": 1,
      "id_kegiatan": 1,
      "nomor_surat": "ST-001/2025",
      "tanggal_surat": "2025-01-25",
      "id_penandatangan": 1,
      "status": "diterbitkan",
      "file_surat": "https://api.example.com/surat-tugas/ST-001-2025.pdf",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "kegiatan": {
        "id_kegiatan": 1,
        "nama_kegiatan": "Pelatihan Dasar CPNS Angkatan I"
      },
      "penandatangan": {
        "id_pegawai": 1,
        "nama": "Dr. Bambang Sutrisno"
      }
    }
  ],
  "total": 5
}
```

---

### 7. **Surat Tugas Pegawai** - Daftar Pegawai dalam Surat Tugas
```
GET /api/v1/surat-tugas-pegawai
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data surat tugas pegawai berhasil diambil",
  "data": [
    {
      "id": 1,
      "id_surat_tugas": 1,
      "id_pegawai": 1,
      "peran": "penanggung_jawab",
      "suratTugas": {
        "id_surat_tugas": 1,
        "nomor_surat": "ST-001/2025"
      },
      "pegawai": {
        "id_pegawai": 1,
        "nama": "Dr. Bambang Sutrisno"
      }
    }
  ],
  "total": 5
}
```

---

### 8. **Sertifikat** - Daftar Sertifikat
```
GET /api/v1/sertifikat
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data sertifikat berhasil diambil",
  "data": [
    {
      "id_sertifikat": 1,
      "id_kegiatan": 1,
      "id_peserta": 1,
      "nomor_sertifikat": "CERT-2025-001",
      "tanggal_ttd": "2025-02-28",
      "id_penandatangan": 1,
      "template": "template_1",
      "peran": "Peserta",
      "status": "terbit",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "kegiatan": {
        "id_kegiatan": 1,
        "nama_kegiatan": "Pelatihan Dasar CPNS Angkatan I"
      },
      "peserta": {
        "id_peserta": 1,
        "nama_lengkap": "Budi Santoso"
      },
      "penandatangan": {
        "id_pegawai": 1,
        "nama": "Dr. Bambang Sutrisno"
      }
    }
  ],
  "total": 5
}
```

---

### 9. **Log Aktivitas** - Daftar Log Aktivitas
```
GET /api/v1/log-aktivitas
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data log aktivitas berhasil diambil",
  "data": [
    {
      "id": 1,
      "id_user": 1,
      "aktivitas": "Membuat kegiatan baru: Pelatihan Dasar CPNS Angkatan I",
      "tabel": "kegiatan",
      "id_referensi": 1,
      "created_at": "2026-01-03T08:30:00Z",
      "user": {
        "id_user": 1,
        "email": "bambang.sutrisno@kemkominfo.go.id"
      }
    }
  ],
  "total": 5
}
```

---

### 10. **Unit Kerja** - Daftar Unit Kerja
```
GET /api/v1/unit-kerja
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data unit kerja berhasil diambil",
  "data": [
    {
      "id": 1,
      "kode_unit": "BA",
      "nama_unit": "Biro Administrasi",
      "jenis_unit": "utama",
      "tahun": 2026,
      "keterangan": "Biro yang menangani administrasi umum dan tata usaha",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "subUnitKerja": [
        {
          "id": 1,
          "unit_kerja_id": 1,
          "nama_sub_unit": "Bagian Tata Usaha",
          "fungsi": "Menangani tata usaha, administrasi umum, dan surat-menyurat"
        }
      ]
    }
  ],
  "total": 5
}
```

---

### 11. **Sub Unit Kerja** - Daftar Sub Unit Kerja
```
GET /api/v1/sub-unit-kerja
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data sub unit kerja berhasil diambil",
  "data": [
    {
      "id": 1,
      "unit_kerja_id": 1,
      "nama_sub_unit": "Bagian Tata Usaha",
      "fungsi": "Menangani tata usaha, administrasi umum, dan surat-menyurat",
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "unitKerja": {
        "id": 1,
        "nama_unit": "Biro Administrasi"
      }
    }
  ],
  "total": 5
}
```

---

### 12. **Keanggotan Tim** - Daftar Keanggotan Tim
```
GET /api/v1/keanggotan-tim
```
**Response Example:**
```json
{
  "success": true,
  "message": "Data keanggotan tim berhasil diambil",
  "data": [
    {
      "id": 1,
      "id_pegawai": 1,
      "unit_kerja_id": 1,
      "sub_unit_kerja_id": 1,
      "peran": "Kepala",
      "tahun": 2026,
      "created_at": "2026-01-23T09:35:00Z",
      "updated_at": "2026-01-23T09:35:00Z",
      "pegawai": {
        "id_pegawai": 1,
        "nama": "Dr. Bambang Sutrisno"
      },
      "unitKerja": {
        "id": 1,
        "nama_unit": "Biro Administrasi"
      },
      "subUnitKerja": {
        "id": 1,
        "nama_sub_unit": "Bagian Tata Usaha"
      }
    }
  ],
  "total": 5
}
```

---

## 📊 Summary

| # | Endpoint | Method | Controller | Status |
|---|----------|--------|-----------|--------|
| 1 | `/api/v1/pegawai` | GET | PegawaiController@index | ✅ Ready |
| 2 | `/api/v1/users` | GET | UserController@index | ✅ Ready |
| 3 | `/api/v1/kegiatan` | GET | KegiatanController@index | ✅ Ready |
| 4 | `/api/v1/peserta` | GET | PesertaController@index | ✅ Ready |
| 5 | `/api/v1/akun-peserta` | GET | AkunPesertaController@index | ✅ Ready |
| 6 | `/api/v1/surat-tugas` | GET | SuratTugasController@index | ✅ Ready |
| 7 | `/api/v1/surat-tugas-pegawai` | GET | SuratTugasPegawaiController@index | ✅ Ready |
| 8 | `/api/v1/sertifikat` | GET | SertifikatController@index | ✅ Ready |
| 9 | `/api/v1/log-aktivitas` | GET | LogAktivitasController@index | ✅ Ready |
| 10 | `/api/v1/unit-kerja` | GET | UnitKerjaController@index | ✅ Ready |
| 11 | `/api/v1/sub-unit-kerja` | GET | SubUnitKerjaController@index | ✅ Ready |
| 12 | `/api/v1/keanggotan-tim` | GET | KeanggotatanTimController@index | ✅ Ready |

---

## 🧪 Testing dengan Postman/cURL

**cURL Example:**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/pegawai" \
  -H "Accept: application/json"
```

**Response Format:**
```json
{
  "success": true,
  "message": "string",
  "data": [],
  "total": number
}
```

---

## 📝 Notes
- Semua endpoint mengembalikan data dalam format JSON
- Setiap response berisi `success`, `message`, `data`, dan `total`
- Relasi data sudah di-load dengan eager loading
- Server: `http://127.0.0.1:8000`
- Base API Path: `/api/v1`
