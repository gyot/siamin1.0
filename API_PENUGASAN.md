# Dokumentasi API Penugasan Pegawai

Dokumen ini membantu implementasi frontend untuk data pegawai yang ditugaskan pada kegiatan. Struktur terbaru tidak lagi memakai tabel `penugasan`; relasi dibuat langsung dari `penugasan_pegawai` ke `kegiatan`.

## Base URL

```text
/api/v1
```

## Auth

Endpoint `penugasan-pegawai` ada di grup protected, jadi frontend wajib mengirim token.

Header minimum:

```http
Accept: application/json
Authorization: Bearer {token}
```

Jika token tidak ada atau tidak valid, backend akan mengembalikan:

```json
{
  "message": "Unauthenticated."
}
```

## Struktur Data

### Tabel `penugasan_pegawai`

Kolom yang dipakai backend:

- `id`
- `id_kegiatan`
- `id_pegawai`
- `peran`

Nilai `peran` yang diterima:

- `penanggung_jawab`
- `ketua_panitia`
- `panitia`
- `peserta`
- `narasumber`

Satu baris `penugasan_pegawai` berarti satu pegawai ditugaskan pada satu kegiatan dengan peran tertentu.

## Endpoint Penugasan Pegawai

### 1. List penugasan pegawai

`GET /api/v1/penugasan-pegawai`

Filter opsional:

```text
?id_kegiatan=44
?id_pegawai=83
?id_kegiatan=44&id_pegawai=83
```

Response sukses:

```json
{
  "success": true,
  "data": [
    {
      "id": 7,
      "id_kegiatan": 44,
      "id_pegawai": 83,
      "peran": "panitia",
      "pegawai": {
        "id_pegawai": 83,
        "nama": "Nama Pegawai"
      },
      "kegiatan": {
        "id_kegiatan": 44,
        "nama_kegiatan": "Workshop Penyusunan Program"
      }
    }
  ]
}
```

### 2. Detail penugasan pegawai

`GET /api/v1/penugasan-pegawai/{id}`

Response sukses:

```json
{
  "success": true,
  "data": {
    "id": 7,
    "id_kegiatan": 44,
    "id_pegawai": 83,
    "peran": "panitia",
    "pegawai": {
      "id_pegawai": 83,
      "nama": "Nama Pegawai"
    },
    "kegiatan": {
      "id_kegiatan": 44,
      "nama_kegiatan": "Workshop Penyusunan Program"
    }
  }
}
```

### 3. Tambah pegawai ke kegiatan

`POST /api/v1/penugasan-pegawai`

Payload minimum:

```json
{
  "id_kegiatan": 44,
  "id_pegawai": 83
}
```

Payload lengkap:

```json
{
  "id_kegiatan": 44,
  "id_pegawai": 83,
  "peran": "panitia"
}
```

Validasi:

- `id_kegiatan`: wajib, harus ada di tabel `kegiatan`
- `id_pegawai`: wajib, harus ada di tabel `pegawai`
- `peran`: opsional, jika dikirim harus salah satu dari daftar peran yang diizinkan

Jika pegawai yang sama sudah pernah ditambahkan ke kegiatan yang sama:

```json
{
  "success": false,
  "message": "Pegawai sudah ditambahkan pada kegiatan ini"
}
```

HTTP status untuk kasus duplikat: `422`.

### 4. Ubah data penugasan pegawai

`PUT /api/v1/penugasan-pegawai/{id}`

atau

`PATCH /api/v1/penugasan-pegawai/{id}`

Contoh payload:

```json
{
  "peran": "ketua_panitia"
}
```

Jika ingin memindahkan pegawai ke kegiatan lain:

```json
{
  "id_kegiatan": 45
}
```

Backend tetap mencegah kombinasi duplikat `id_kegiatan + id_pegawai`.

### 5. Hapus penugasan pegawai

`DELETE /api/v1/penugasan-pegawai/{id}`

Response sukses:

```json
{
  "success": true,
  "message": "Deleted"
}
```

## Endpoint Pendukung Untuk Frontend

### Ambil kegiatan yang bisa dikelola user pada unit kerja tertentu

`GET /api/v1/kegiatan/tim/{unit_kerja_id}`

Rule akses:

- Jika pegawai login adalah anggota `unit_kerja` tersebut, semua kegiatan di unit itu akan tampil.
- Jika pegawai login bukan anggota unit kerja itu, kegiatan hanya tampil jika pegawai tersebut tercatat di `penugasan_pegawai` untuk kegiatan terkait.
- Jika tidak login, response `401`.

### Ambil kegiatan milik pegawai login

`GET /api/v1/kegiatan`

Rule akses:

- Kegiatan muncul jika `kegiatan.id_pegawai` sama dengan `id_pegawai` user login.
- Kegiatan juga muncul jika pegawai login terdaftar di `penugasan_pegawai`.

## Saran Alur Frontend

1. Login user dan simpan token.
2. Panggil `GET /api/v1/me` untuk mengambil `id_pegawai` dan daftar `unit_kerja`.
3. Tampilkan kegiatan yang bisa dikelola dengan `GET /api/v1/kegiatan/tim/{unit_kerja_id}` atau `GET /api/v1/kegiatan`.
4. Saat user memilih kegiatan, tambahkan pegawai langsung lewat `POST /api/v1/penugasan-pegawai`.
5. Untuk halaman detail kegiatan, ambil daftar pegawai dengan `GET /api/v1/penugasan-pegawai?id_kegiatan={id_kegiatan}`.

## Contoh Request Frontend

### Tambah pegawai ke kegiatan

```js
await api.post('/api/v1/penugasan-pegawai', {
  id_kegiatan: 44,
  id_pegawai: 83,
  peran: 'ketua_panitia'
})
```

### Update peran pegawai

```js
await api.patch('/api/v1/penugasan-pegawai/7', {
  peran: 'penanggung_jawab'
})
```

### Hapus pegawai dari kegiatan

```js
await api.delete('/api/v1/penugasan-pegawai/7')
```

## Catatan Migrasi

- Tabel `penugasan` dihapus.
- Kolom lama `penugasan_pegawai.id_penugasan` diganti menjadi `penugasan_pegawai.id_kegiatan`.
- Data lama dikonversi dengan mengambil `id_kegiatan` dari tabel `surat_tugas` atau `penugasan` sebelum tabel tersebut dihapus.
