# SPESIFIKASI API BACKEND SIMAIK (Laravel 12)

> Hasil studi kode backend `/home/bpmp/website/siamin1.0` (routes + controllers + form
> requests + resources + services). Dokumen ini adalah referensi WAJIB agar aplikasi
> Android native (`siamin-ui/android-native`) memanggil API dengan cara yang sama persis
> dengan web frontend. Jangan menebak perilaku di luar yang tercatat di sini.
>
> Total endpoint tercakup: **128** endpoint API di `routes/api.php` (dinormalisasi per action —
> update mewakili PUT+PATCH) + 6 route OG di `routes/og.php` (§14).

---

## 1. IKHTISAR

| Item | Nilai |
|---|---|
| Base URL | `https://<host>/api` |
| Prefix versi | `/v1` → semua endpoint di bawah `/api/v1/...` |
| Autentikasi | Laravel Sanctum, header `Authorization: Bearer <token>` (token `plainTextToken` dari login) |
| Format request | JSON (atau `multipart/form-data` untuk endpoint dengan upload file) |
| Format response | JSON, umumnya envelope `{"success": bool, "message"?: string, "data"?: ...}` |
| Error validasi | HTTP 422, format Laravel standar: `{"message": "...", "errors": {"field": ["..."]}}` |
| Error auth | HTTP 401 `{"message": "Unauthenticated."}` (direspons custom di `bootstrap/app.php`) |
| Server timezone | WITA (UTC+08) — `tanggal_evaluasi` dll dikirim ISO-8601 (UTC, `Z`) |

### 1.1 Throttling (rate limit)

| Endpoint | Limit |
|---|---|
| `POST /v1/auth/login`, `/login-admin`, `/login-peserta` | `throttle:10,1` (10 request/menit per IP) |
| `POST /v1/auth/register` | `throttle:20,1` |
| Route OG (`/kegiatan/{kode}` dst, lihat §12) | `throttle:60,1` |
| `POST /v1/evaluasi` | Rate limit aplikasi: maks **3 submit/jam per IP** (lihat §7) |

### 1.2 Akses publik vs terproteksi

Semua endpoint di §4–§9 yang ditandai **PUBLIK** dapat diakses tanpa token (read-only).
Semua **mutasi** (POST/PUT/PATCH/DELETE) wajib `auth:sanctum` kecuali:
- `POST /v1/auth/*` (login/register)
- `POST /v1/evaluasi` (form evaluasi publik)
- `POST /v1/test/submit` (submit jawaban tes publik)

**PENTING** — sebagian besar endpoint TIDAK punya cek role di controller (cukup login).
Pengecekan role (admin-only) hanya ada di:
1. `TestController::ensureAdminAccess` — semua endpoint laporan & CRUD paket/soal (§9): role harus salah satu dari `admin`, `super_admin`, `operator`, `verifikator`, `kepala` (dinormalisasi: lowercase, spasi/`-` → `_`). Selain itu 403 `{"success":false,"message":"Anda tidak memiliki akses."}`.
2. `EvaluasiController::ensureAdminAccess` — `GET /v1/evaluasi/{id_kegiatan}/{id_tpk?}` (§7): role sama seperti di atas; 403 `"Anda tidak memiliki akses ke data evaluasi."` (user tanpa login → 401).
3. `UserController::ensureAdmin` — store/update/destroy user: **hanya `role === 'admin'`** (tepat, tanpa normalisasi). CATATAN: route untuk store/update/destroy user TIDAK didaftarkan di `routes/api.php` (hanya `index`/`show`) — jadi tidak bisa dipanggil dari API.

### 1.3 Konvensi role pengguna

- `users.role`: `admin`, `super_admin`, `operator`, `verifikator`, `kepala` (dipakai login-admin; normalisasi `super admin`/`super-admin` → `super_admin`).
- Peserta bukan tabel `users`, tapi `akun_peserta` (role virtual `peserta`).
- Status user/akun harus `aktif` untuk bisa login.

### 1.4 Format tanggal

| Field | Format di response | Keterangan |
|---|---|---|
| `tanggal_ttd` (sertifikat/batch) | `Y-m-d` | diformat eksplisit di Resource |
| `tanggal_mulai`, `tanggal_selesai` kegiatan | nilai mentah kolom DB (`YYYY-MM-DD` untuk kolom date) | TIDAK di-cast di model |
| `tanggal_lahir`, `tanggal_surat_tugas`, dst. | nilai mentah kolom DB | tidak di-cast |
| `created_at`, `updated_at`, `last_login` | ISO-8601 (`2026-01-15T10:00:00.000000Z`) | serialisasi default Laravel |
| `tanggal_evaluasi` | ISO-8601 (cast `datetime`) | `toISOString()` di response store |
| `tanggal` jadwal harian | `Y-m-d` di `GET laporan/{id}/jadwal`; ISO-8601 di response `POST laporan/{id}/jadwal` (cast `date`) | |

Input `date` divalidasi rule `date` Laravel → terima `YYYY-MM-DD` dan `YYYY-MM-DD HH:mm:ss`.

### 1.5 Upload file (semua disimpan ke disk `public`, diakses via `https://<host>/storage/<path>`)

| Field | Endpoint | Rule | Folder |
|---|---|---|---|
| `flyer` | store/update kegiatan | `file\|image\|max:10048` (KB) | `flyers/` |
| `template_biodata` | store/update kegiatan | `file\|mimes:doc,docx\|max:10120` | `template_biodata/` |
| `tanda_tangan` | store/update peserta | `file\|image\|max:2048` | `signatures/` |
| `template_file` | store/update sertifikat batch | `nullable\|file\|mimes:doc,docx\|max:10120` | `template_sertifikat/` |
| `file` | import soal | `required\|file\|mimes:xlsx,xls,csv\|max:10240` | (dibaca langsung, tidak disimpan) |

Semua request dengan file harus `multipart/form-data`.

---

## 2. AUTENTIKASI (`/v1/auth`)

### 2.1 `POST /v1/auth/login-admin` — Login admin/pegawai (PUBLIK, throttle 10/menit)

Body (JSON):
```json
{ "email": "<email ATAU username>", "password": "..." }
```
- `email`: `required|string` — boleh berisi email ATAU username. Jika lolos `FILTER_VALIDATE_EMAIL` → dicocokkan ke kolom `users.email`, selain itu ke kolom `users.user`.
- `password`: `required|string`.
- Role yang diizinkan: `admin`, `super_admin`, `operator`, `verifikator`, `kepala`. User `peserta` DITOLAK (response sama dengan password salah).
- User harus `status = aktif`.

Sukses (200):
```json
{
  "success": true, "message": "Login berhasil",
  "data": {
    "user": { "id_user", "email", "user", "role", "status", "id_pegawai", "pegawai": {...}, ... },
    "unit_kerja_id": ["<kode/id>", ...],
    "unit_kerja": [{ "unit_kerja_id": "…", "nama_unit": "…", "kode_unit": "…" }, ...],
    "token": "<plainTextToken>",
    "token_type": "Bearer"
  }
}
```
- `user` = objek `User` (dengan relasi `pegawai`), key utama `id_user`.
- `unit_kerja` = gabungan dari `keanggotaan_tim.unit_kerja_id` milik `id_pegawai` + kolom `users.unit_kerja_id` (bisa JSON array / string koma). Resolusi ke `unit_kerja` by `kode_unit` atau `id`.

Gagal (422): `{"message": "...", "errors": {"email": ["Email/username atau password tidak valid."]}}`

### 2.2 `POST /v1/auth/login-peserta` — Login peserta (PUBLIK, throttle 10/menit)

Sama seperti §2.1 tetapi hanya role `peserta` (tabel `akun_peserta`, kolom `username`, `status = aktif`).
CATATAN: `attemptLogin` mencari ke tabel `users` — untuk login peserta gunakan `POST /v1/auth/login` (unified) atau pastikan akun peserta memang dicari lewat jalur `users`... **Berdasarkan kode**: `loginPeserta` memanggil `attemptLogin($request, ['peserta'])` yang hanya query `User::where($field, $identity)` — akun peserta TIDAK ada di tabel `users`, sehingga endpoint ini praktis selalu gagal kecuali ada user `users.role = 'peserta'`. **Untuk Android gunakan `POST /v1/auth/login` (unified)** yang benar-benar mengecek `akun_peserta`.

### 2.3 `POST /v1/auth/login` — Login unified admin/peserta (PUBLIK, throttle 10/menit) ✅ DIPAKAI ANDROID

Body: `email` (string, email atau username), `password` (string) — keduanya `required`.

Alur:
1. Cek `users` by `email`/`user` + `status=aktif` + `Hash::check` → sukses `login_as: "admin"` (payload seperti §2.1 + field `login_as`).
2. Cek `akun_peserta` by `username` (IDENTITAS HARUS USERNAME, bukan email) + `status=aktif` + `Hash::check` → sukses `login_as: "peserta"`, update `last_login = now()`.

Sukses peserta (200):
```json
{
  "success": true, "message": "Login berhasil",
  "data": {
    "login_as": "peserta",
    "user": {
      "id": "<id_akun_peserta>", "id_peserta": ...,
      "username": "...", "role": "peserta",
      "nama": "<nama_lengkap dari profil>", "id_profil": ...,
      "profil": { ...profil_peserta... }
    },
    "token": "<plainTextToken>", "token_type": "Bearer"
  }
}
```
Gagal (422): `errors.email = ["Username atau password tidak valid."]`

### 2.4 `POST /v1/auth/register` — Registrasi akun peserta (PUBLIK, throttle 20/menit)

Body (JSON):
| Field | Rule |
|---|---|
| `nama_lengkap` | **required** string max:255 |
| `username` | **required** string max:100, unique `akun_peserta.username` |
| `password` | **required** string min:6, `confirmed` (butuh `password_confirmation`) |
| `nip` | nullable string max:30 |
| `email` | nullable email max:255 |
| `no_hp` | nullable string max:20 |
| `jenis_kelamin` | nullable in:`laki-laki`,`perempuan` |
| `tempat_lahir` | nullable string max:100 |
| `tanggal_lahir` | nullable date |
| `npwp_nik` | nullable string max:30 |
| `pangkat` | nullable string max:100 |
| `jabatan` | nullable string max:255 |
| `nama_instansi` | nullable string max:255 |
| `npsn` | nullable string max:8 |
| `kab_kota` | nullable string max:100 |

Efek: transaksi DB — buat `profil_peserta` lalu `akun_peserta` (status `aktif`, password di-hash).
Sukses 201: `{"success":true,"message":"Registrasi berhasil. Silakan login.","data":{"id_akun_peserta":...,"username":"..."}}`
Gagal server 500: `{"success":false,"message":"Gagal registrasi: ..."}` (rollback).

### 2.5 `POST /v1/logout` (auth:sanctum)

Menghapus token yang dipakai saat ini. 200 `{"success":true,"message":"Logout berhasil"}`.

### 2.6 `GET /v1/auth/me` (auth:sanctum)

- Jika user = `AkunPeserta`: `data.login_as="peserta"`, `data.user` ringkas (lihat §2.3, tanpa `unit_kerja`).
- Jika user = `User`: `data.user` (id_user, email, role, status, last_login, created_at, `unit_kerja_id`[], `unit_kerja`[]), `data.pegawai` (objek pegawai atau null), `data.unit_kerja`[].

---

## 3. RINGKASAN AKSES PER ENDPOINT (cheat sheet)

| Grup | Read | Create/Update/Delete | Cek role |
|---|---|---|---|
| auth | publik | register publik | — |
| kegiatan (index/show/statistik/all/tim) | publik | auth | — |
| kegiatan store/update/destroy | — | auth | — |
| peserta | publik (index wajib `kegiatan_id` tanpa login) | auth | — |
| kegiatan-atk, tpk, unit-kerja | publik | auth | — |
| sertifikat & sertifikat-batch | publik | auth | — |
| evaluasi (store/check/statistik) | publik | store publik | — |
| evaluasi indexByKegiatan | auth | — | admin-role list |
| test (peserta/paket/soal publik, submit publik) | publik | auth | — |
| test laporan + CRUD paket/soal | auth | auth | admin-role list |
| kelas | auth | auth | — |
| laporan draft/jadwal | auth (jadwal GET publik) | auth | — |
| users, pegawai | auth | (tidak dirutekan) | — |
| akun-peserta, keanggotaan-tim, log-aktivitas, penugasan-pegawai, sub-unit-kerja | auth | auth | — |
| push/register, push/remove | — | auth | — |

---

## 4. KEGIATAN

### 4.1 `GET /v1/kegiatan/statistik` (PUBLIK)
Mengembalikan seluruh isi tabel view `info_dashboard`:
`{"success":true,"data":[ {...}, ... ]}`.

### 4.2 `GET /v1/kegiatan/all` (PUBLIK) — daftar kegiatan paginasi untuk dashboard
Query params (divalidasi `DashboardKegiatanIndexRequest`):
| Param | Rule | Default |
|---|---|---|
| `page` | integer min:1 | 1 |
| `limit` | integer 1..100 | 9 |
| `search` | string max:255 (LIKE `nama_kegiatan`) | — |
| `tahun` | 4 digit (filter `YEAR(tanggal_mulai)`) | — |
| `status` | in:`draft`,`berjalan`,`selesai`,`dibatalkan` | — |
| `unit_kerja` | string (cocok: id numerik ATAU LIKE `nama_unit`/`kode_unit`) | — |

Response (`DashboardKegiatanCollection`, TANPA envelope `data` biasa):
```json
{ "success": true, "data": [DashboardKegiatanResource...], "total": N, "current_page": N, "per_page": N, "last_page": N }
```
`DashboardKegiatanResource` field: `id_kegiatan, nama_kegiatan, tanggal_mulai, tanggal_selesai, lokasi, kabupaten_kota, status, total_peserta, peserta_ringkasan, metode_pelaksanaan, metode_pembayaran, deskripsi, rincian_kegiatan, dokumentasi_url, materi_url, panduan_url, laporan_url, surat_menyurat_url, unit_kerja_id, unit_kerja` (nama unit), `daftar_tpk` (relasi, hanya jika table `tpk` ada). `lokasi`/`kabupaten_kota` = TPK pertama (by `id_tpk`).

### 4.3 `GET /v1/kegiatan/tim/{id}` — kegiatan tim by `unit_kerja_id`
- Menggunakan `auth('sanctum')->user()` **opsional** — jika user login dan punya `id_pegawai`:
  - Jika user anggota `keanggotaan_tim` unit tsb → semua baris `kegiatan_tim` unit itu.
  - Jika tidak → hanya yang user-nya punya `penugasan_pegawai` di kegiatan tsb.
  - Jika user TIDAK login (`user()` null) → 401 `{"success":false,"message":"Unauthenticated."}` (pegawaiId null).
- Response: `{"success":true,"data":[baris tabel kegiatan_tim...]}` diurut `tanggal_mulai` desc.

### 4.4 `GET /v1/kegiatan/tim_kegiatan/{id}` (PUBLIK)
Semua baris `kegiatan` dengan `unit_kerja_id = {id}`, `tanggal_mulai` desc. Data mentah tabel `kegiatan`.

### 4.5 `GET /v1/kegiatan/tim-saya` (auth:sanctum)
Kegiatan milik unit kerja user (dari `keanggotaan_tim` + kolom `id_tim`) ATAU yang user-nya ditugaskan (`penugasan_pegawai`). Response: `{"success":true,"data":[KegiatanResource...]}`. Jika user tanpa `id_pegawai` & tanpa unit → `data: []`.

### 4.6 `GET /v1/kegiatan` (PUBLIK) — index
Jika request TANPA token → query dibatasi `whereRaw('0 = 1')` → **selalu `data: []`**. Dengan token: kegiatan yang `id_pegawai` = user ATAU user ada di `penugasan_pegawai`, urut `tanggal_mulai` desc. Response `{"success":true,"data":[KegiatanResource...]}`.

`KegiatanResource` field: `id_kegiatan, nama_kegiatan, rincian_kegiatan, dokumentasi_url, materi_url, panduan_url, laporan_url, surat_menyurat_url, tanggal_mulai, tanggal_selesai, flyer, template_biodata, peserta_ringkasan, total_peserta, metode_pembayaran, deskripsi, metode_pelaksanaan, status, created_by, id_pegawai, unit_kerja_id, created_at, updated_at` + `daftar_atk`, `daftar_tpk`, `paket_soal` (saat di-load; selalu di-load oleh `KegiatanService::query()`).

### 4.7 `GET /v1/kegiatan/{id}` (PUBLIK) — show
404 `{"success":false,"message":"Kegiatan not found"}` jika tak ada. Response `{"success":true,"data":KegiatanResource}` (dengan `daftar_atk`, `daftar_tpk`, `paket_soal`).

### 4.8 `GET /v1/kegiatan/{id}/peserta-sertifikat` (PUBLIK) — status sertifikat per peserta kegiatan
Response:
```json
{
  "success": true,
  "batch_exists": true,
  "batch": SertifikatBatchResource|null,
  "batches": [SertifikatBatchResource...],
  "data": [{ "id_peserta", "nama_lengkap", "peran", "status_sertifikat": "draft|terbit|dicabut|belum_ada", "id_batch" }]
}
```

### 4.9 `GET /v1/kegiatan/{id}/sertifikat-batch` (PUBLIK)
Jika kosong: `{"exists": false}` (TANPA `success`). Jika ada: `{"exists": true, "data":[SertifikatBatchResource...]}`.

### 4.10 `POST /v1/kegiatan` (auth) — create kegiatan (multipart/form-data atau JSON)

`StoreKegiatanRequest`:
| Field | Rule |
|---|---|
| `nama_kegiatan` | **required** string max:255 |
| `tanggal_mulai` | **required** date |
| `tanggal_selesai` | **required** date, `after_or_equal:tanggal_mulai` |
| `status` | **required** in:`draft`,`berjalan`,`selesai`,`dibatalkan` |
| `id_pegawai` | **required** (nullable) exists:`pegawai.id_pegawai` |
| `daftar_tpk` | **required** array min:1 |
| `daftar_tpk[].lokasi` | **required** string max:255 |
| `daftar_tpk[].kabupaten_kota` | nullable string max:255 |
| `rincian_kegiatan` | sometimes string |
| `dokumentasi_url`, `materi_url`, `panduan_url`, `laporan_url`, `surat_menyurat_url` | sometimes url max:255 |
| `flyer` | sometimes file image max:10048 |
| `template_biodata` | sometimes file mimes:doc,docx max:10120 |
| `peserta_ringkasan` | sometimes string |
| `total_peserta` | sometimes integer min:0 |
| `metode_pembayaran` | sometimes in:`transfer`,`pulsa`,`transfer_dan_pulsa`,`tunai`,`tidak_dibayar` |
| `deskripsi` | sometimes nullable string |
| `metode_pelaksanaan` | sometimes in:`daring`,`luring`,`hybrid` |
| `created_by` | sometimes nullable exists:`users.id_user` |
| `unit_kerja_id` | sometimes nullable exists:`unit_kerja.id` |
| `daftar_atk` | sometimes array |
| `daftar_atk[].nama_barang` | required_with:`daftar_atk` string max:255 |
| `daftar_atk[].spesifikasi` | nullable string max:255 |
| `daftar_atk[].jumlah` | sometimes integer min:1 (default 1) |
| `daftar_atk[].satuan` | nullable string max:100 |
| `daftar_atk[].keterangan` | nullable string |

`KegiatanService::preparePayload` (efek samping / resolusi otomatis):
- `id_pegawai` default = `id_pegawai` user login jika tidak dikirim.
- `created_by` default = key user login.
- `unit_kerja_id` jika kosong: diambil dari `keanggotaan_tim` pegawai — jika tepat 1 unit → dipakai otomatis; jika >1 → 422 `"Pegawai memiliki lebih dari satu unit kerja. Frontend wajib mengirim unit_kerja_id."`; jika 0 → 422 `"Unit kerja tidak ditemukan untuk pegawai ini. Frontend wajib mengirim unit_kerja_id yang valid."`; jika `id_pegawai` null → 422 `"ID pegawai wajib dikirim untuk menyimpan kegiatan."`.
- `daftar_tpk` di-sync: item dengan `id_tpk` yang sudah ada → update; cocok `lokasi`+`kabupaten_kota` → update; selain itu create baru; yang tidak disubmit → **dihapus**.
- `daftar_atk` di-replace penuh (delete semua lalu insert).
- File disimpan; jika transaksi gagal file baru di-rollback (dihapus).

Response 201: `{"success":true,"data":KegiatanResource}` (relasi lengkap).

### 4.11 `PUT/PATCH /v1/kegiatan/{id}` (auth) — update
`UpdateKegiatanRequest` = semua field `sometimes` (tidak ada yang required), plus:
- `daftar_tpk[].id_tpk` sometimes nullable exists:`tpk.id_tpk`
- `daftar_paket_soal` sometimes array; item integer exists:`paket_soal.id_paket_soal` → **sync pivot** `kegiatan_paket_soal` (replace).

Perilaku field file:
- `flyer` ter-upload → replace (file lama TIDAK dihapus — beda dengan template_biodata); `flyer` dikirim kosong (`exists` tapi blank) → set null; tidak dikirim → tidak diubah.
- `template_biodata` ter-upload → file lama dihapus, simpan baru; dikirim kosong → hapus file lama + set null; tidak dikirim → tidak diubah.
- `daftar_atk`/`daftar_tpk`/`daftar_paket_soal` hanya di-sync jika key-nya ADA di request (`$request->has(...)`).
404: `{"success":false,"message":"Kegiatan yang anda cari tidak ditemukan"}`.
Response 200: `{"success":true,"data":KegiatanResource}`.

### 4.12 `DELETE /v1/kegiatan/{id}` (auth)
Hapus file `flyer` & `template_biodata` dari storage, hapus `daftarAtk` & `daftarTpk` terkait, hapus kegiatan (transaksi). 200 `{"success":true,"message":"Deleted successfully"}`; 404 `"Kegiatan not found"`.

---

## 5. PESERTA

### 5.1 `GET /v1/peserta` (PUBLIK dengan batasan)
- **Tanpa token WAJIB kirim `?kegiatan_id=...`**, jika tidak → 403 `{"success":false,"message":"Parameter kegiatan_id wajib untuk akses publik."}`.
- Filter: `kegiatan_id` (=`id_kegiatan`), `id_peserta`, `search` (LIKE `nama_lengkap`/`nip`).
- Relasi yang di-load: `kegiatan` (kolom: id_kegiatan, nama_kegiatan, tanggal_mulai, tanggal_selesai, metode_pelaksanaan, status), `tpk` (id_tpk, id_kegiatan, lokasi, kabupaten_kota). Urut `created_at` desc.
- `per_page` hadir → paginasi; batas: max 500 (login) / 200 (publik). Response:
  `{"success":true,"data":[...],"meta":{"current_page","last_page","per_page","total"}}`
- Tanpa `per_page` → semua baris: `{"success":true,"data":[...]}`.
- **Akses publik: kolom sensitif disembunyikan** — `npwp_nik, nomor_rekening, nama_bank, provider_pulsa, no_hp, telp_instansi, email, email_instansi` TIDAK muncul di response.

### 5.2 `GET /v1/peserta/{id}` (PUBLIK) — show
`data` = ARRAY (bukan objek!) — satu entri per sertifikat peserta (tabel `sertifikat_peserta` via batch):
```json
{ "success": true, "data": [ {
    "peserta": { ...peserta dengan kegiatan & tpk... },
    "id_batch", "id_kegiatan", "nama_kegiatan", "tgl_mulai", "tgl_akhir", "deskripsi",
    "status_sertifikat", "nomor_sertifikat", "tanggal_ttd", "qr_token", "template_file_url"
} ] }
```
CATATAN: field `tgl_mulai`/`tgl_akhir` dibaca dari `$batch->kegiatan->tgl_mulai` — kemungkinan null jika kolom di model `Kegiatan` bernama `tanggal_mulai` (bentrok nama kolom historis). 404 `"Peserta not found"`.

### 5.3 `GET /v1/peserta/{id}/kegiatan` (PUBLIK) — peserta + daftar kegiatan via sertifikat
```json
{ "success": true, "data": { "peserta": {...dengan tpk...}, "kegiatan": [ { "id_batch", "id_kegiatan", "nama_kegiatan", "tgl_mulai", "tgl_akhir", "deskripsi", "status_sertifikat", "nomor_sertifikat", "tanggal_ttd", "qr_token", "template_file_url" } ] } }
```

### 5.4 `POST /v1/peserta` (auth) — create
| Field | Rule |
|---|---|
| `id_kegiatan` | **required** exists:`kegiatan.id_kegiatan` |
| `nama_lengkap` | **required** string max:150 |
| `id_tpk` | nullable exists:`tpk.id_tpk` |
| `nip` | sometimes string max:30 |
| `pangkat` | sometimes string max:50 |
| `gol` | sometimes string max:50 |
| `jabatan` | sometimes string max:150 |
| `no_hp` | sometimes string max:20 |
| `email` | sometimes email max:150 |
| `npwp_nik` | sometimes string max:30 |
| `tempat_lahir` | sometimes string max:255 |
| `tanggal_lahir` | sometimes date |
| `jenis_kelamin` | sometimes in: `laki-laki`,`perempuan`,`Laki-laki`,`Perempuan`,`M`,`F`,`male`,`female` → **dinormalisasi** ke `laki-laki`/`perempuan` |
| `nama_instansi` | sometimes string max:255 |
| `alamat_instansi` | sometimes string |
| `npsn` | sometimes string max:20 |
| `kab_kota` | sometimes string max:100 |
| `provinsi` | sometimes string max:100 |
| `telp_instansi` | sometimes string max:20 |
| `email_instansi` | sometimes email max:150 |
| `provider_pulsa` | sometimes string max:50 |
| `nomor_rekening` | sometimes string max:50 |
| `nama_bank` | sometimes string max:100 |
| `no_surat_tugas` | sometimes string max:255 |
| `tanggal_surat_tugas` | sometimes date |
| `peran` | sometimes string max:100 |
| `tanda_tangan` | sometimes file image max:2048 (disimpan ke `signatures/`) |

Response 201: `{"success":true,"data":<objek Peserta mentah>}`.

### 5.5 `PUT/PATCH /v1/peserta/{id}` (auth) — update
Rule sama dengan §5.4 kecuali `id_kegiatan` menjadi `sometimes`. `tanda_tangan` baru → file lama dihapus. Normalisasi gender sama. 404 `"Peserta not found"`. Response `{"success":true,"data":<Peserta>}`.

### 5.6 `DELETE /v1/peserta/{id}` (auth)
Hapus file `tanda_tangan` jika ada, hapus peserta. 200 `{"success":true,"message":"Peserta deleted successfully"}`.

---

## 6. TPK, KEgiatan-ATK, UNIT KERJA, SUB UNIT, PENUGASAN, KEANGGOTAAN, LOG, AKUN PESERTA, USER, PEGAWAI

### 6.1 `GET /v1/tpk` (PUBLIK), `GET /v1/tpk/{id}` (PUBLIK)
- index: `Tpk::with('kegiatan')->latest()`, filter `?id_kegiatan=`. Response `{"success":true,"data":[...]}`.
- show: 404 `{"success":false,"message":"TPK not found"}`.
- `POST/PUT/DELETE /v1/tpk...` (auth). Rules store: `id_kegiatan` **required** exists kegiatan; `lokasi` **required** string max:255; `kabupaten_kota` sometimes nullable string max:255. Update: semua `sometimes`. Response create 201 / update 200 dengan relasi `kegiatan`; delete 200 `{"success":true,"message":"TPK deleted successfully"}`.

### 6.2 `GET /v1/kegiatan-atk`, `GET /v1/kegiatan-atk/{id}` (PUBLIK) + CRUD (auth)
Sama pola dengan TPK. Rules: `id_kegiatan` **required** exists; `nama_barang` **required** string max:255; `spesifikasi` nullable max:255; `jumlah` sometimes integer min:1; `satuan` nullable max:100; `keterangan` nullable string. 404 `"ATK not found"`, delete msg `"ATK deleted successfully"`.

### 6.3 `GET /v1/unit-kerja` (PUBLIK)
`{"success":true,"data":[...]}` — semua `unit_kerja` KECUALI `kode_unit = '000'`.
CATATAN bentuk response berbeda: `GET /v1/unit-kerja/user/{id}` mengembalikan **array JSON langsung tanpa envelope** (`response()->json($data)`) — isi = semua baris `keanggotaan_tim` milik pegawai `{id}` (id = `id_pegawai`).
`GET /v1/unit-kerja/{id}` (PUBLIK, BaseApiController::show) → envelope `{"success":true,"data":...}`, 404 `{"success":false,"message":"Not found"}`.
`POST/PUT/DELETE /v1/unit-kerja` (auth) — BaseApiController TANPA rules → **semua input diterima apa adanya** (`$request->all()`). 201 create.

### 6.4 `GET/POST/PUT/DELETE /v1/sub-unit-kerja` (auth) — BaseApiController
Rules (store): `unit_kerja_id` **required** exists:`unit_kerja,id`; `nama_sub_unit` sometimes string max:255. Update: unique-rule auto-adjust. Response envelope BaseApiController (`{"success":true,"data":...}`, 201 create, `"Deleted"` utk delete, 404 `"Not found"`).

### 6.5 `GET/POST/PUT/DELETE /v1/akun-peserta` (auth) — BaseApiController
Rules: `id_peserta` **required** exists:`peserta,id_peserta`; `username` **required** string max:100 unique `akun_peserta.username`; `password` sometimes string. **Password otomatis di-Hash** sebelum disimpan (store & update). Response envelope BaseApiController.

### 6.6 `GET/POST/PUT/DELETE /v1/keanggotaan-tim` (auth) — BaseApiController override index
Rules (store & update SAMA — update TIDAK pakai `sometimes`, jadi semua required saat update!): `id_pegawai` **required** exists pegawai; `unit_kerja_id` **required** exists unit_kerja; `sub_unit_kerja_id` nullable exists:`sub_unit_kerja,id`; `peran` nullable string max:100; `tahun` nullable digits:4 integer.
index: filter `id_pegawai`, `unit_kerja_id`, `tahun`, `peran` (LIKE). Jika ada param `page` atau `limit` → paginator Laravel (`data` = objek paginator lengkap dengan `links`/`meta`); selain itu array biasa. Relasi `pegawai`, `unit`, `subUnit` selalu di-load. 404 `"Not found"`.

### 6.7 `GET/POST/PUT/DELETE /v1/log-aktivitas` (auth) — BaseApiController TANPA rules
Semua input diterima apa adanya (dibuat oleh logika aplikasi). Envelope BaseApiController.

### 6.8 `GET/POST/PUT/DELETE /v1/penugasan-pegawai` (auth)
- index: `PenugasanPegawai::with(['pegawai','kegiatan'])`, filter `id_kegiatan`, `id_pegawai`. `{"success":true,"data":[...]}`.
- store rules: `id_kegiatan` **required** exists kegiatan; `id_pegawai` **required** exists pegawai; `peran` sometimes nullable.
  - **Cek duplikat**: kombinasi `id_kegiatan`+`id_pegawai` yang sudah ada → 422 `{"success":false,"message":"Pegawai sudah ditambahkan pada kegiatan ini"}`.
  - **SIDE EFFECT: push notification FCM** ke pegawai tsb — title `"Penugasan Baru"`, body `"Anda ditambahkan dalam penugasan pada kegiatan {nama_kegiatan} sebagai {peran}"`, data `{"type":"penugasan","id_kegiatan":...,"peran":...}` (default peran `"Peserta"` jika null). Gagal kirim = hanya warning log, response tetap sukses.
  - Response 201: `{"success":true,"data":<penugasan dengan pegawai, kegiatan.daftarTpk>}`.
- update rules: `id_kegiatan`/`id_pegawai` sometimes exists; `peran` sometimes nullable. Cek duplikat juga (ignore id sendiri). TIDAK ada push notification. 404 `"Not found"`.
- destroy: BaseApiController → `{"success":true,"message":"Deleted"}`.
- `peran` diterima sebagai string bebas (nullable, tanpa enum) — referensi nilai ada di tabel `peran` (lihat `GET /v1/peran`): `penanggung_jawab, ketua_panitia, sekretaris_panitia, penitia, narasumber, peserta, pendamping`.

### 6.9 `GET /v1/peran` (PUBLIK)
Semua baris tabel `peran`: `{"success":true,"data":[...]}`.

### 6.10 `GET /v1/penugasan/ketua-panitia/{id_kegiatan}` (PUBLIK)
Cari penugasan dengan peran cocok `ketua_panitia` / `Ketua Panitia` / LIKE `%ketua%panitia%`. Ketemu: `{"success":true,"data":{"nama":"...","nip":"..."}}` (default `'-'`). Tidak ketemu: `{"success":true,"data":null}`.

### 6.11 `GET /v1/penugasan-pegawai-detailed` (auth) — paginasi detail
Params: `per_page` (default 15, max 100), `id_kegiatan`, `id_pegawai`, `peran`, `search` (LIKE nama pegawai ATAU nama kegiatan), `sort_by` (default `id`; khusus `tanggal_mulai` pakai join ke `kegiatan`), `sort_order` (`asc`/`desc`, default desc; selain itu dipaksa `desc`).
Response:
```json
{ "success": true, "data": [PenugasanDetailResource...],
  "pagination": { "total", "per_page", "current_page", "last_page", "from", "to" } }
```
`PenugasanDetailResource`: `id` (= `id_penugasan`, juga diulang sebagai `id_penugasan`), `id_kegiatan`, `id_pegawai`, `peran`, `nama_pegawai` (default `'N/A'`), `nama_kegiatan` (default `'N/A'`), `tanggal_mulai`, `tanggal_selesai`, `kabupaten_kota` (TPK pertama), `lokasi` (TPK pertama), `daftar_tpk`.

### 6.12 `GET /v1/users` + `GET /v1/users/{id}` (auth) — read-only
index: gabung baris view per `id_user` → `{id_user, id_pegawai, name, user, email, role, roles_detail, id_tim, tim_ids, tim_detail, status, last_login, created_at, updated_at}`. (store/update/destroy user TIDAK dirutekan.)

### 6.13 `GET /v1/pegawai` + `GET /v1/pegawai/{id}` (auth) — read-only
index: gabung baris view per `id_pegawai` + `roles_detail`, `tim_detail`, `pdm_detail`, `jabatan_pdm_detail` (implode unique). (CRUD tidak dirutekan; rules model: `nip` required unique, `nama` required, dst. — hanya relevan bila route dibuka.)

---

## 7. EVALUASI

### 7.1 `POST /v1/evaluasi` (PUBLIK) — submit evaluasi
**Rate limit aplikasi**: maks 3 submit per IP per jam → 429:
`{"success":false,"message":"Terlalu banyak submit evaluasi dari IP ini. Coba lagi nanti.","retry_after_seconds":<int>}`.

Body:
| Field | Rule |
|---|---|
| `id_kegiatan` | **required** exists:`kegiatan.id_kegiatan` |
| `id_tpk` | nullable exists:`tpk.id_tpk` |
| `program_tujuan` | **required** integer 1..5 |
| `program_bahan_ajar` | **required** integer 1..5 |
| `program_alokasi_waktu` | **required** integer 1..5 |
| `layanan_panitia` | **required** integer 1..5 |
| `layanan_fasilitas` | **required** integer 1..5 |
| `layanan_konsumsi` | **required** integer 1..5 |
| `fasilitator` | nullable array min:1 |
| `fasilitator[].nama` | nullable string max:255 (di-trim + `strip_tags`) |
| `fasilitator[].penguasaan_materi` | nullable integer 1..5 (default 0 jika null) |
| `fasilitator[].sistematika` | nullable integer 1..5 |
| `fasilitator[].sikap` | nullable integer 1..5 |
| `saran` | nullable string max:5000 (di-trim + `strip_tags`) |

Efek samping: simpan `ip_address`, `user_agent` (max 255), `tanggal_evaluasi = now()`, `id_evaluasi` auto format `EVAL-YYYYMMDDHHmmss-XXXX`.
Response 201: `{"success":true,"message":"Evaluasi berhasil dikirim","data":{"id_evaluasi","id_kegiatan","id_tpk","tanggal_evaluasi":"<ISO-8601>"}}`.

### 7.2 `GET /v1/evaluasi/check/{id_kegiatan}/{id_tpk?}` (PUBLIK)
`{"success":true,"data":{"sudah_evaluasi":true|false}}` — cek apakah ADA evaluasi (per kegiatan, opsional per TPK). CATATAN: ini cek keberadaan evaluasi apa pun, bukan per-user.

### 7.3 `GET /v1/evaluasi/{id_kegiatan}/{id_tpk?}/statistik` (PUBLIK)
Response `data`:
```
total_evaluasi, rata_rata_program (float 2), rata_rata_fasilitator, rata_rata_layanan,
detail_fasilitator: [{nama, jumlah_penilaian, rata_rata_penguasaan, rata_rata_sistematika, rata_rata_sikap,
                       distribusi_penguasaan:{'1'..'5'}, distribusi_sistematika, distribusi_sikap}],
grafik_penilaian: {5_bintang,4_bintang,3_bintang,2_bintang,1_bintang}
```

### 7.4 `GET /v1/evaluasi/{id_kegiatan}/{id_tpk?}` (auth + role admin-list) — daftar evaluasi
Kolom: `id_evaluasi, id_kegiatan, id_tpk, tanggal_evaluasi, program_*, fasilitator, layanan_*, saran`. Urut `tanggal_evaluasi` desc.

### 7.5 `GET /v1/evaluasi/random-fasilitator` (PUBLIK!!) — utility berbahaya
Mengubah data: semua nilai fasilitator yang `== 1` diganti random 4–5, lalu simpan. Mengembalikan string `"Selesai"`. JANGAN dipanggil dari aplikasi Android (hanya util migrasi data internal).

---

## 8. SERTIFIKAT

### 8.1 Bentuk resource
- `SertifikatResource` (batch): `id_batch, id_kegiatan, nomor_sertifikat, id_penandatangan, tanggal_ttd (Y-m-d|null), template_file, template_file_url (https://<host>/storage/...), status, kegiatan (whenLoaded), penandatangan (whenLoaded), peserta (collection SertifikatPesertaResource whenLoaded), total_peserta (saat relasi dimuat), created_at, updated_at`.
- `SertifikatPesertaResource`: `id, id_batch, id_peserta, qr_token, status, batch (SertifikatBatchResource), peserta, lokasi {id_tpk, lokasi, kabupaten_kota}, kelas [{id_kelas, nama_kelas, deskripsi}], kegiatan, penandatangan, created_at, updated_at`.
- `SertifikatBatchResource`: seperti SertifikatResource tanpa `peserta`/`total_peserta`.
- Status sertifikat peserta/batch: `draft` | `terbit` | `dicabut`.

### 8.2 `GET /v1/sertifikat` (PUBLIK)
Semua batch + relasi `kegiatan, penandatangan, sertifikatPeserta.peserta.tpk, sertifikatPeserta.peserta.kelas`, urut `created_at` desc → `{"success":true,"data":[SertifikatResource...]}`.

### 8.3 `GET /v1/sertifikat/{id}` (PUBLIK) — **PENTING: `{id}` = `id_peserta`, BUKAN id batch!**
Query `sertifikat_peserta where id_peserta = {id}` (dengan relasi lengkap). 404 `{"success":false,"message":"Sertifikat untuk peserta tidak ditemukan."}`. Response `{"success":true,"data":[SertifikatPesertaResource...]}`.

### 8.4 `GET /v1/sertifikat/peserta/{id_peserta}` (PUBLIK) — sama dengan §8.3, dengan try/catch 500 `{"success":false,"message":"Terjadi kesalahan: ..."}`.

### 8.5 `GET /v1/sertifikat/cek/{id}` (PUBLIK) — verifikasi by `qr_token` (UUID)
`{id}` = `qr_token`. Bentuk response sama §8.3 (array).

### 8.6 `GET /v1/sertifikat-batch` (PUBLIK) — semua batch (`SertifikatBatchResource`, relasi kegiatan+penandatangan), urut `created_at` desc.
### 8.7 `GET /v1/sertifikat-batch/{id}` (PUBLIK) — detail batch + `sertifikatPeserta.peserta`. 404 `"Batch sertifikat tidak ditemukan."`.

### 8.8 `POST /v1/sertifikat-batch` (auth) — create/update batch (multipart)
`StoreSertifikatBatchRequest`:
| Field | Rule |
|---|---|
| `id_kegiatan` | **required** exists kegiatan |
| `nomor_sertifikat` | **required** string max:150 |
| `id_batch` | sometimes integer exists:`sertifikat_batch.id_batch` → **jika dikirim, request ini jadi UPDATE batch tsb** (kompatibilitas form edit) |
| `id_penandatangan` | nullable exists:`pegawai.id_pegawai` |
| `tanggal_ttd` | nullable date |
| `template_file` | nullable file mimes:doc,docx max:10120 |
| `status` | sometimes in:`draft`,`terbit`,`dicabut` |

- Template baru → file lama dihapus; `template_file` dikirim kosong → set null + hapus file lama.
- Response create 201 / update 200: `{"success":true,"message":"Batch sertifikat berhasil dibuat./diperbarui.","data":SertifikatBatchResource}`.

### 8.9 `POST /v1/sertifikat` (auth) — endpoint TWIN dari §8.8 (SertifikatController::store)
Validasi & perilaku identik §8.8 (request yang sama), response `data` = `SertifikatResource` (bukan BatchResource) dengan `kegiatan, penandatangan` termuat. Gunakan salah satu; web memakai keduanya di konteks berbeda.

### 8.10 `PUT/PATCH /v1/sertifikat-batch/{id}` & `PUT/PATCH /v1/sertifikat/{id}` (auth) — update batch
`UpdateSertifikatBatchRequest`: semua `sometimes` (`id_kegiatan` exists, `nomor_sertifikat` max:150, `id_penandatangan` nullable exists, `tanggal_ttd` nullable date, `template_file` nullable file doc/docx max:10120, `status` in draft/terbit/dicabut). Perilaku file sama §8.8. 404 `"Sertifikat tidak ditemukan."` / `"Batch sertifikat tidak ditemukan."`.

### 8.11 `DELETE /v1/sertifikat-batch/{id}` & `DELETE /v1/sertifikat/{id}` (auth)
Hapus `template_file` dari storage + hapus batch. 200 `"Sertifikat berhasil dihapus."` / `"Batch sertifikat berhasil dihapus."`.

### 8.12 `POST /v1/sertifikat/generate` (auth) — generate 1 sertifikat peserta
`GenerateSertifikatRequest`: `id_batch` **required** exists:`sertifikat_batch,id_batch`; `id_peserta` **required** exists:`peserta,id_peserta`; `status` sometimes in:`draft`,`terbit`,`dicabut` (default `draft`).
- **Validasi silang**: semua peserta harus dari `id_kegiatan` yang sama dengan batch → 422 `errors.peserta_ids = ["Semua peserta harus berasal dari kegiatan yang sama dengan batch sertifikat."]`.
- `updateOrCreate` per (id_batch, id_peserta) — jika baru: `qr_token` = UUID v4, `status` dari request/default `draft`. Jika sudah ada: hanya `updated_at` (status TIDAK diubah oleh endpoint ini).
- Response 201: `{"success":true,"message":"Sertifikat peserta berhasil dibuat.","data":SertifikatPesertaResource}`.

### 8.13 `POST /v1/sertifikat/generate-massal` (auth)
`GenerateMassalSertifikatRequest`: `id_batch` **required** exists; `peserta_ids` **required** array min:1; item integer `distinct` exists:`peserta,id_peserta`.
- Validasi silang sama §8.12 (dicek terhadap jumlah setelah unique-cast).
- Upsert massal (unique `id_batch`+`id_peserta`), `qr_token` baru untuk baris baru, `status = 'draft'`, baris lama hanya `updated_at` disentuh.
- Response 201: `{"success":true,"message":"Generate sertifikat massal berhasil diproses.","data":[SertifikatPesertaResource...]}` (milik batch tsb, yang di-submit saja).

### 8.14 `PATCH /v1/sertifikat-peserta/{id}/status` (auth) — ubah status 1 sertifikat peserta
Body: `status` **required** in:`draft`,`terbit`,`dicabut`. `{id}` = PK `sertifikat_peserta.id`. 404 `"Sertifikat peserta tidak ditemukan."`. Response: `{"success":true,"message":"Status sertifikat peserta berhasil diperbarui.","data":SertifikatPesertaResource}`.

### 8.15 `PATCH /v1/sertifikat/update-status` (auth) — ubah status by peserta (tanpa id sertifikat)
Body: `id_peserta` **required** integer; `id_kegiatan` nullable integer (filter via batch); `status` **required** in:`draft`,`terbit`,`dicabut`. Ambil `first()` sertifikat yang cocok (prioritas `id` terkecil) lalu update. 404 `"Sertifikat peserta tidak ditemukan. Buat sertifikat terlebih dahulu."`.

### 8.16 `DELETE /v1/sertifikat-peserta/{id}` (auth) — hapus 1 sertifikat peserta
200 `"Sertifikat peserta berhasil dihapus."`.

---

## 9. TEST / PAKET SOAL (PaketSoal, Soal, JawabanPeserta)

### 9.1 Publik (tanpa token)
| Endpoint | Keterangan |
|---|---|
| `GET /v1/test/peserta/{id_kegiatan}` | Daftar peserta kegiatan (kolom: id_peserta, id_tpk, nama_lengkap, nip, pangkat, gol, jabatan, nama_instansi, kab_kota, provinsi), filter `?id_tpk=`, urut nama. |
| `GET /v1/test/peserta-detail/{id_peserta}` | Detail peserta + `kegiatan` (id_kegiatan, nama_kegiatan) + `tpk` (id_tpk, lokasi, kabupaten_kota). 404 `"Peserta tidak ditemukan"`. |
| `GET /v1/test/paket/{id_kegiatan}` | Paket soal AKTIF (`is_active = true`) milik kegiatan (pivot `kegiatans`), dengan `soals_count`, urut `nama_paket`. |
| `GET /v1/test/soal/{id_paket_soal}` | Paket + `soals` (id_soal, id_paket_soal, pertanyaan, pilihan_a..d, **`jawaban_benar` ikut terkirim** — keamanan lemah, sudah begitu adanya di backend), urut `urutan`. 404 `"Paket soal tidak ditemukan"`. |
| `GET /v1/test/template` | Download file `template-import-soal.xlsx` (200 file stream; 404 `"Template tidak ditemukan"`). |

### 9.2 `POST /v1/test/submit` (PUBLIK) — kumpulkan jawaban
Body:
```json
{ "id_peserta": 1, "id_paket_soal": 1,
  "jawaban": [ { "id_soal": 1, "jawaban": "a" }, ... ] }
```
- `id_peserta` required exists peserta; `id_paket_soal` required exists paket_soal; `jawaban` required array min:1; item: `id_soal` required exists soal, `jawaban` required in:`a,b,c,d`.
- **Sekali attempt**: jika sudah ada jawaban peserta untuk paket itu → 422 `{"success":false,"message":"Peserta sudah mengerjakan paket soal ini."}`.
- Skor = `round(benar/total*100, 1)`.
- Response 201: `{"success":true,"message":"Jawaban berhasil disimpan","data":{"total_soal","jawaban_benar","jawaban_salah","skor"}}`.

### 9.3 `GET /v1/test/hasil/{id_peserta}/{id_paket_soal}` (PUBLIK)
404 `"Data jawaban tidak ditemukan"`. Response `data`: `{ringkasan:{total_soal,jawaban_benar,jawaban_salah,skor}, detail:[{id_soal,pertanyaan,pilihan_a..d,jawaban_benar,jawaban_peserta,is_correct}]}`.

### 9.4 Admin (auth + role admin-list — `ensureAdminAccess`): Laporan
| Endpoint | Keterangan |
|---|---|
| `GET /v1/test/laporan/{id_kegiatan}` | Rekap per (peserta × paket): filter `?id_peserta=`, `?id_paket_soal=`. Item: `id_peserta, nama_peserta, nip, jabatan, nama_instansi, id_paket_soal, nama_paket, total_soal, jawaban_benar, jawaban_salah, skor`. |
| `GET /v1/test/laporan/{id_kegiatan}/{id_peserta}/{id_paket_soal}` | Detail: `{peserta, ringkasan, detail[...]}`. 404 `"Peserta tidak ditemukan"` / `"Data jawaban tidak ditemukan"`. |

### 9.5 Admin: CRUD Paket Soal
| Endpoint | Validasi / aturan |
|---|---|
| `GET /v1/test/paket-all` | Semua paket + `soals_count` + `kegiatans`, filter `?search=` (LIKE `nama_paket`), urut `created_at` desc. |
| `POST /v1/test/paket` | `nama_paket` **required** string max:255; `deskripsi` nullable max:1000; `is_active` boolean. 201. |
| `GET /v1/test/paket-manage/{id_paket_soal}` | Detail + `soals_count` + `kegiatans`. 404 `"Paket soal tidak ditemukan"`. |
| `PUT /v1/test/paket/{id_paket_soal}` | `nama_paket` sometimes; `deskripsi` nullable; `is_active` boolean. |
| `DELETE /v1/test/paket/{id_paket_soal}` | **Ditolak 422** jika sudah ada `jawaban_peserta`: `"Paket soal tidak dapat dihapus karena sudah memiliki jawaban peserta."` |

### 9.6 Admin: CRUD Soal
| Endpoint | Validasi / aturan |
|---|---|
| `GET /v1/test/paket/{id_paket_soal}/soal` | Semua soal paket, urut `urutan`, `id_soal`. |
| `POST /v1/test/paket/{id_paket_soal}/soal` | Body `{soal:[{pertanyaan required, pilihan_a..d required max:1000, jawaban_benar required in:a,b,c,d, urutan sometimes integer min:0}]}` — `urutan` default = max+index+1. 201, `data` = semua soal paket. |
| `POST /v1/test/paket/{id_paket_soal}/import` | Upload `file` xlsx/xls/csv max:10240. Header WAJIB: `pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar` (opsional `urutan`). `jawaban_benar` boleh huruf a–d ATAU teks persis salah satu pilihan. Baris invalid dilewati (`import_errors`). 201: `{success, message:"N soal berhasil diimpor.", data, import_errors}`. 422 jika file kosong/tidak valid/tidak ada baris valid (`errors` array). |
| `PUT /v1/test/paket/{id_paket_soal}/soal-replace` | Ganti SEMUA soal (delete + insert, transaksi). **Ditolak 422** jika sudah ada jawaban peserta. Body sama seperti storeSoal (`urutan` default i+1). |
| `PUT /v1/test/soal/{id_soal}` | Semua field `sometimes` (pertanyaan, pilihan_a..d max:1000, jawaban_benar in:a,b,c,d, urutan min:0). 404 `"Soal tidak ditemukan"`. |
| `DELETE /v1/test/soal/{id_soal}` | **Ditolak 422** jika soal sudah punya jawaban peserta. 200 `"Soal berhasil dihapus"`. |

---

## 10. KELAS (auth:sanctum, tanpa cek role)

| Endpoint | Keterangan |
|---|---|
| `GET /v1/kelas/{id_kegiatan}` | Kelas kegiatan + `anggotas_count`, urut `nama_kelas`. |
| `POST /v1/kelas` | `id_kegiatan` **required** exists kegiatan; `nama_kelas` **required** string max:255; `deskripsi` nullable max:1000. 201. |
| `GET /v1/kelas-detail/{id_kelas}` | Detail + `pesertas` (id_peserta, id_kegiatan, nama_lengkap, nip, nama_instansi, kab_kota, tanda_tangan — urut nama) + `anggotas_count`. 404 `"Kelas tidak ditemukan"`. |
| `PUT /v1/kelas/{id_kelas}` | `nama_kelas` sometimes max:255; `deskripsi` nullable max:1000. |
| `DELETE /v1/kelas/{id_kelas}` | 200 `"Kelas berhasil dihapus"`. |
| `POST /v1/kelas/{id_kelas}/anggota` | Body `id_peserta` **required** exists peserta. Duplikat → 422 `"Peserta sudah ada di kelas ini"`. 201 `data` = kelas + `anggotas_count` terbaru. |
| `DELETE /v1/kelas/{id_kelas}/anggota/{id_peserta}` | 404 `"Anggota tidak ditemukan"` jika tak ada; 200 `"Anggota berhasil dihapus"`. |

---

## 11. LAPORAN (draft + jadwal harian)

| Endpoint | Auth | Keterangan |
|---|---|---|
| `GET /v1/laporan/{id_kegiatan}/jadwal` | PUBLIK | `{jadwal_per_kelas:[{id_kelas,nama_kelas,jadwal:[{hari,tanggal:'Y-m-d',items}]}], jadwal_harian:[{hari,tanggal,items}], kelas:[...]}`. Urut `urutan`, `tanggal`. |
| `GET /v1/laporan/{id_kegiatan}/draft` | auth | `{success, data:<objek data draft>|null, updated_at}` (data = JSON bebas `laporan_drafts.data`). |
| `POST /v1/laporan/{id_kegiatan}/draft` | auth | Body `data` **required** array (JSON bebas). `updateOrCreate` per kegiatan. Response `{success, data, updated_at}`. |
| `POST /v1/laporan/{id_kegiatan}/jadwal` | auth | Body `jadwal` **required** array; item: `hari` **required** string max:50, `tanggal` **required** date, `items` nullable array, `id_kelas` nullable integer exists:`kelas,id_kelas`, `urutan` nullable integer (default=index). **SELURUH jadwal lama kegiatan dihapus lalu dibuat ulang** (replace penuh). Response `{success, data:[JadwalHarian...], message:"Jadwal berhasil disimpan"}`. |

---

## 12. PUSH NOTIFICATION (auth:sanctum)

### 12.1 `POST /v1/push/register` — daftarkan FCM token
Body:
| Field | Rule |
|---|---|
| `token` | **required** string max:500 (FCM registration token) |
| `id_pegawai` | nullable exists:`pegawai,id_pegawai` |
| `id_akun_peserta` | nullable exists:`akun_peserta,id_akun_peserta` |
| `device_type` | nullable in:`android`,`ios`,`web` (default `android`) |

`updateOrCreate` by `token` (token sama → update pemilik/device, `is_active = true`). Response `{"success":true,"message":"Token berhasil didaftarkan.","data":<PushToken>}`.

### 12.2 `POST /v1/push/remove`
Body: `token` **required** string max:500. Soft-deactivate (`is_active = false`). Response `{"success":true,"message":"Token berhasil dihapus."}`.

### 12.3 Sisi server (efek samping)
- Notifikasi dikirim via **FCM HTTP v1** (`https://fcm.googleapis.com/v1/projects/{project}/messages:send`) memakai service account di `config('services.fcm.service_account')` (file `firebase-service-account.json`).
- Channel Android: `simaik_notifications`, priority high, sound default, `click_action: FLUTTER_NOTIFICATION_CLICK`.
- Pemicu yang ada di kode: **hanya `POST /v1/penugasan-pegawai`** (lihat §6.8). Token target = semua `push_tokens` milik `id_pegawai` dengan `is_active = true`.
- ⚠️ CATATAN IMPLEMENTASI: header otorisasi di kode memakai `'Authorization: *** ' . $accessToken` (bukan `Bearer ...`) — kemungkinan FCM selalu 401 sehingga push tidak terkirim; pastikan diuji/klarifikasi sebelum mengandalkan push dari Android.

---

## 13. DASHBOARD

### 13.1 `GET /v1/dashboard/stats` (PUBLIK)
Response **TANPA envelope** `success` (array langsung):
```json
{
  "total_kegiatan": 0,
  "kegiatan_berjalan": 0,
  "total_peserta": 0,
  "total_sertifikat_terbit": 0,
  "available_tahun": ["2026", "2025"],
  "available_unit_kerja": ["Nama Unit", ...]
}
```
`kegiatan_berjalan` = tanggal hari ini antara `tanggal_mulai`..`tanggal_selesai` dan `status != dibatalkan`. `total_sertifikat_terbit` = jumlah `sertifikat` (tabel `sertifikat`) berstatus `terbit`.

---

## 14. ROUTE OG (link preview, `routes/og.php`, di luar `/api`)

Dilindungi `throttle:60,1`, mengembalikan shell SPA + meta OG (crawler). Path web:
`/kegiatan/{kode}/{slugJudul?}`, `/daftar-peserta/{kode}/{slugJudul?}`, `/unduh-sertifikat/{kode}/{slugJudul?}`, `/evaluasi/{kode}/{idTpk?}/{slugJudul?}`, `/formulir/{kode}/{peran}/{idTpk?}/{slugJudul?}`, `/laporan-evaluasi/{kode}/{idTpk?}/{slugJudul?}` — semua `GET` ke `OgMetaController::show`. Tidak perlu dipanggil Android.

---

## 15. CATATAN PERILAKU LAIN (WAJIB DIPERHATIKAN ANDROID)

1. **Envelope tidak seragam** — cek setiap endpoint di atas. `GET /v1/unit-kerja` & `/v1/unit-kerja/user/{id}` & `/v1/dashboard/stats` & `/v1/evaluasi/random-fasilitator` TANPA `success` wrapper; `GET /v1/kegiatan/{id}/sertifikat-batch` pakai key `exists`.
2. **`GET /v1/sertifikat/{id}` menerima `id_peserta`**, bukan id sertifikat/batch.
3. **`GET /v1/kegiatan` & `GET /v1/peserta` tanpa token** dibatasi (kegiatan → `[]`; peserta → wajib `kegiatan_id`, kolom sensitif disembunyikan).
4. **Registrasi peserta** butuh `password_confirmation`; login peserta memakai **username** (bukan email) di `POST /v1/auth/login`.
5. **Zona waktu**: simpan tanggal lokal sebagai `YYYY-MM-DD` string; jangan andalkan parsing ISO untuk field tanggal kegiatan (mentah dari DB).
6. **File URL**: field `flyer`, `template_biodata`, `tanda_tangan`, `template_file` berisi RELATIVE PATH (`flyers/xxx.jpg`); URL penuh = `https://<host>/storage/<path>` (kecuali `template_file_url` yang sudah penuh di resource sertifikat).
7. **Update kegiatan**: kirim `daftar_tpk` minimal 1 item bila ingin mempertahankan TPK (kirim `id_tpk` agar tidak dianggap baru); `daftar_atk` bersifat replace penuh.
8. **`peran` penugasan** string bebas; nilai resmi ada di `GET /v1/peran`.
9. **`POST /v1/test/submit` hanya sekali** per peserta per paket — tangani 422 sebagai "sudah mengerjakan".
10. **`PATCH /v1/sertifikat/update-status`** memakai `id_peserta` + optional `id_kegiatan` — hanya mengubah sertifikat PERTAMA yang cocok.
11. Role admin-list untuk endpoint §9.4–§9.6 & §7.4: `admin`, `super_admin`, `operator`, `verifikator`, `kepala` — peserta akan 403.
12. Semua endpoint mutasi (kecuali auth/evaluasi/test-submit) mengembalikan 401 `{"message":"Unauthenticated."}` tanpa token yang valid.
