# Rekap Perubahan — 1 Juli 2026

**Proyek:** SIAMIN (Sistem Administrasi dan Manajemen Kegiatan)  
**Tanggal:** 1 Juli 2026  
**Lingkup:** Backend (Laravel) + Frontend (Vue 3)

---

## 1. Penambahan Relasi TPK pada Tabel Peserta

### Tujuan
Setiap peserta kini memiliki TPK (Tempat Pelaksanaan Kegiatan) masing-masing, sehingga saat data peserta ditampilkan, informasi TPK juga ikut tampil.

### Backend

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_06_30_085143_add_id_tpk_to_peserta_table.php` | Migration baru: menambah kolom `id_tpk` (nullable, FK → `tpk.id_tpk`, ON DELETE SET NULL) pada tabel `peserta` |
| `app/Models/Peserta.php` | Menambah `id_tpk` ke `$fillable`; menambah relationship `tpk()` → `belongsTo Tpk` |
| `app/Models/Tpk.php` | Menambah relationship `peserta()` → `hasMany Peserta` |
| `app/Http/Controllers/Api/PesertaController.php` | Eager-load `tpk` pada method `index`, `show`, `showWithKegiatan`; menambah validasi `id_tpk` (nullable, exists di tabel tpk) pada `store` dan `update` |

### Frontend

| File | Perubahan |
|------|-----------|
| `src/pages/FormulirPeserta.vue` | Import `getKegiatanLocationItems`; menambah `id_tpk` ke formData; menambah computed `tpkItems`; menambah dropdown "Tempat Pelaksanaan (TPK)" di section Data Pribadi |
| `src/pages/PesertaManagement.vue` | Import `getKegiatanLocationItems`; menambah `id_tpk` ke formPeserta; menambah computed `tpkItemsForForm`; menambah watcher reset `id_tpk` saat kegiatan berubah; menambah dropdown TPK di form admin; menambah info TPK di modal biodata; menambah kolom TPK di tabel desktop + card mobile; menambah filter TPK dropdown + computed `uniqueTpk` |
| `src/pages/DaftarPesertaPublik.vue` | Menambah kolom TPK pada tabel publik |

---

## 2. Evaluasi Berdasarkan TPK

### Tujuan
Endpoint evaluasi sekarang mendukung filter berdasarkan TPK, sehingga narasumber yang dievaluasi sesuai dengan TPK masing-masing.

### Backend

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_06_30_234245_add_id_tpk_to_evaluasi_table.php` | Migration baru: menambah kolom `id_tpk` (nullable, FK → `tpk.id_tpk`, ON DELETE SET NULL) pada tabel `evaluasi` |
| `app/Models/Evaluasi.php` | Menambah `id_tpk` ke `$fillable`; menambah relationship `tpk()` → `belongsTo Tpk` |
| `app/Http/Controllers/Api/EvaluasiController.php` | Semua method (`store`, `indexByKegiatan`, `statistik`, `check`) sekarang mendukung parameter `id_tpk` opsional untuk filter |
| `routes/api.php` | Route evaluasi berubah: `POST evaluasi`, `GET evaluasi/check/{id_kegiatan}/{id_tpk?}`, `GET evaluasi/{id_kegiatan}/{id_tpk?}/statistik`, `GET evaluasi/{id_kegiatan}/{id_tpk?}` |
| `API_EVALUASI.md` | Dokumentasi diperbarui sesuai endpoint baru |

### Route Baru

```
POST   /api/v1/evaluasi                                  → store
GET    /api/v1/evaluasi/check/{id_kegiatan}/{id_tpk?}    → check
GET    /api/v1/evaluasi/{id_kegiatan}/{id_tpk?}/statistik → statistik
GET    /api/v1/evaluasi/{id_kegiatan}/{id_tpk?}           → indexByKegiatan (auth)
```

---

## 3. Frontend Evaluasi — Narasumber Berdasarkan TPK

### Tujuan
Form evaluasi menampilkan narasumber sesuai TPK yang dipilih, bukan semua narasumber dalam satu kegiatan.

### Perubahan

| File | Perubahan |
|------|-----------|
| `src/router/index.js` | Route berubah: `/evaluasi/:kode/:idTpk?/:slugJudul?` dan `/laporan-evaluasi/:kode/:idTpk?/:slugJudul?` |
| `src/pages/EvaluasiKegiatan.vue` | Mengambil `idTpk` dari route params; `loadNarasumber()` memfilter peserta berdasarkan `id_tpk`; mengirim `id_tpk` dalam payload submit; check evaluasi menggunakan `id_tpk` |
| `src/pages/LaporanEvaluasi.vue` | `loadStatistik()` dan `loadDetailEvaluasi()` mendapat parameter `idTpk` dan memanggil endpoint yang sesuai |

---

## 4. Link Evaluasi Per-TPK di Semua Halaman

### Tujuan
Semua halaman yang menampilkan link evaluasi kini menampilkan satu link per TPK beserta nama TPK-nya.

### Contoh Tampilan
```
Hotel Lombok Raya (Kota Mataram)
http://localhost:5173/evaluasi/6/1/konsolidasi-daerah-pendidikan-dasar-dan-menengah-tahun-2026

Hotel Lombok Garden (Kota Mataram)
http://localhost:5173/evaluasi/6/8/konsolidasi-daerah-pendidikan-dasar-dan-menengah-tahun-2026
```

### Perubahan

| File | Perubahan |
|------|-----------|
| `src/pages/KegiatanDetailPublik.vue` | Mengganti single `evaluasiUrl` → computed `evaluasiLinks` (array per TPK); template `v-for` per TPK dengan nama + URL + QR; QR generation loop per TPK |
| `src/pages/Landing.vue` | Mengganti single `evaluasiUrl` → computed `evaluasiLinks` (array per TPK); template `v-for` per TPK dengan QR + nama + URL; QR generation loop per TPK |
| `src/pages/Dashboard.vue` | Import `getKegiatanLocationItems`; `evaluationLinks` menghasilkan 1 link per TPK; QR generation per TPK; template menampilkan nama TPK |
| `src/pages/Kegiatan.vue` | Import `getKegiatanLocationItems`; `activityLinks` membangun 1 entry evaluasi per TPK (`Evaluasi - Nama TPK`) |

### Builder Function Pattern
```javascript
// Sebelum (single link)
buildPublicEvaluasiLink(kode, judul)
// → /evaluasi/6/nama-kegiatan

// Sesudah (per-TPK link)
buildPublicEvaluasiLink(kode, judul, idTpk)
// → /evaluasi/6/1/nama-kegiatan
```

---

## 5. Ringkasan File yang Diubah

### Backend (7 file)

1. `database/migrations/2026_06_30_085143_add_id_tpk_to_peserta_table.php` — **BARU**
2. `database/migrations/2026_06_30_234245_add_id_tpk_to_evaluasi_table.php` — **BARU**
3. `app/Models/Peserta.php` — modified
4. `app/Models/Tpk.php` — modified
5. `app/Models/Evaluasi.php` — modified
6. `app/Http/Controllers/Api/PesertaController.php` — modified
7. `app/Http/Controllers/Api/EvaluasiController.php` — modified
8. `routes/api.php` — modified
9. `API_EVALUASI.md` — modified

### Frontend (9 file)

1. `src/router/index.js` — modified
2. `src/pages/FormulirPeserta.vue` — modified
3. `src/pages/PesertaManagement.vue` — modified
4. `src/pages/DaftarPesertaPublik.vue` — modified
5. `src/pages/EvaluasiKegiatan.vue` — modified
6. `src/pages/LaporanEvaluasi.vue` — modified
7. `src/pages/KegiatanDetailPublik.vue` — modified
8. `src/pages/Landing.vue` — modified
9. `src/pages/Dashboard.vue` — modified
10. `src/pages/Kegiatan.vue` — modified

---

## 6. Database Migration

```bash
# Jalankan migration baru
php artisan migrate --path=database/migrations/2026_06_30_085143_add_id_tpk_to_peserta_table.php
php artisan migrate --path=database/migrations/2026_06_30_234245_add_id_tpk_to_evaluasi_table.php
```

Kedua migration sudah dijalankan dan berhasil.

---

*Dokumen ini dibuat otomatis pada 1 Juli 2026 pukul 15:26 WITA.*
