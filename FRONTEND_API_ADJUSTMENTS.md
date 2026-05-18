# Penyesuaian Frontend Setelah Perbaikan API

Dokumen ini merangkum perubahan backend yang perlu diikuti frontend agar proses login dan simpan kegiatan berjalan stabil.

## Perubahan Utama

### 1. Endpoint protected sekarang selalu mengembalikan `401`

Jika token tidak ada, tidak valid, atau sudah kedaluwarsa, backend sekarang mengembalikan JSON `401` dan tidak lagi meledak ke error `Route [login] not defined`.

Contoh response:

```json
{
  "message": "Unauthenticated."
}
```

Penyesuaian frontend:

- Selalu kirim header `Accept: application/json`.
- Jika menerima `401`, hapus token lokal lalu arahkan user ke halaman login.

### 2. Flow auth API sekarang fokus ke Bearer token

Backend ini dipakai sebagai token-based API, bukan session/cookie-based SPA auth.

Penyesuaian frontend:

- Simpan token hasil login lalu kirim di header `Authorization: Bearer {token}`.
- Untuk request API berbasis token, **jangan** kirim `credentials: 'include'` atau `credentials: 'same-origin'`.
- Jangan bergantung pada cookie session browser untuk akses endpoint `/api/v1/*`, kecuali backend sengaja diaktifkan dengan mode stateful Sanctum.

## Endpoint Auth Yang Dipakai Frontend

### Login admin

`POST /api/v1/auth/login-admin`

Request:

```json
{
  "email": "admin@contoh.go.id",
  "password": "rahasia"
}
```

### Login peserta

`POST /api/v1/auth/login-peserta`

### Ambil profil login

`GET /api/v1/me`

Header:

```http
Authorization: Bearer {token}
Accept: application/json
```

Response penting untuk frontend:

```json
{
  "success": true,
  "data": {
    "user": {
      "id_user": 1,
      "email": "admin@contoh.go.id",
      "role": "admin",
      "status": "aktif",
      "unit_kerja_id": ["12", "15"],
      "unit_kerja": [
        {
          "unit_kerja_id": "12",
          "nama_unit": "Unit A",
          "kode_unit": "001"
        }
      ]
    },
    "pegawai": {
      "id_pegawai": 83,
      "nama": "Nama Pegawai"
    },
    "unit_kerja": [
      {
        "unit_kerja_id": "12",
        "nama_unit": "Unit A",
        "kode_unit": "001"
      }
    ]
  }
}
```

Catatan:

- `id_pegawai` ambil dari `data.pegawai.id_pegawai`.
- Pilihan unit kerja bisa diambil dari `data.user.unit_kerja` atau `data.unit_kerja`.

## Perubahan Penting Untuk Simpan Kegiatan

### Endpoint

`POST /api/v1/kegiatan`

Backend sekarang melakukan dua hal:

- Jika `unit_kerja_id` dikirim, backend langsung memakai nilai itu.
- Jika `unit_kerja_id` tidak dikirim dan pegawai hanya punya satu unit kerja di `keanggotan_tim`, backend akan mengisi otomatis.

Tetapi jika pegawai punya lebih dari satu unit kerja, frontend sekarang **wajib** mengirim `unit_kerja_id`.

### Payload minimum yang aman

Gunakan payload berikut saat create:

```http
Content-Type: multipart/form-data
Accept: application/json
Authorization: Bearer {token}
```

Field minimal:

```text
nama_kegiatan
tanggal_mulai
tanggal_selesai
lokasi
status
id_pegawai
unit_kerja_id
```

Contoh field:

```text
nama_kegiatan=Workshop Penyusunan Program
tanggal_mulai=2026-04-23
tanggal_selesai=2026-04-24
lokasi=Mataram
status=draft
id_pegawai=83
unit_kerja_id=12
```

### Response sukses

```json
{
  "success": true,
  "data": {
    "id_kegiatan": 10,
    "nama_kegiatan": "Workshop Penyusunan Program",
    "id_pegawai": 83,
    "unit_kerja_id": 12,
    "status": "draft"
  }
}
```

### Response validasi jika `unit_kerja_id` belum dikirim

Kasus pegawai punya lebih dari satu unit kerja:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "unit_kerja_id": [
      "Pegawai memiliki lebih dari satu unit kerja. Frontend wajib mengirim unit_kerja_id."
    ]
  }
}
```

Kasus unit kerja pegawai tidak ditemukan:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "unit_kerja_id": [
      "Unit kerja tidak ditemukan untuk pegawai ini. Frontend wajib mengirim unit_kerja_id yang valid."
    ]
  }
}
```

## Rekomendasi Implementasi Di Frontend

Pada halaman form kegiatan:

1. Ambil `id_pegawai` dari hasil login atau `/api/v1/me`.
2. Ambil daftar unit kerja dari `/api/v1/me`.
3. Jika hanya ada satu unit kerja, isi `unit_kerja_id` otomatis.
4. Jika unit kerja lebih dari satu, tampilkan dropdown wajib pilih.
5. Saat submit, selalu kirim `id_pegawai` dan `unit_kerja_id`.

Contoh logika sederhana:

```js
const me = await api.get('/api/v1/me')
const pegawaiId = me.data.data.pegawai?.id_pegawai ?? null
const unitKerjaList = me.data.data.user?.unit_kerja ?? []

const payload = new FormData()
payload.append('nama_kegiatan', form.nama_kegiatan)
payload.append('tanggal_mulai', form.tanggal_mulai)
payload.append('tanggal_selesai', form.tanggal_selesai)
payload.append('lokasi', form.lokasi)
payload.append('status', form.status)
payload.append('id_pegawai', pegawaiId)

if (form.unit_kerja_id) {
  payload.append('unit_kerja_id', form.unit_kerja_id)
} else if (unitKerjaList.length === 1) {
  payload.append('unit_kerja_id', unitKerjaList[0].unit_kerja_id)
}
```

## Perbaikan Relasi Keanggotaan Tim

Backend juga sudah ditambah alias relasi `unitKerja` pada model `KeanggotaanTim`.

Artinya jika ada bagian frontend atau backend yang sebelumnya memakai nama relasi:

- `unit`
- `unitKerja`

keduanya sekarang tetap bisa dipakai tanpa memicu error relation not found.
