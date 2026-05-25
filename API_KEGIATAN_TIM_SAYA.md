# Dokumentasi API Kegiatan Tim Saya

Endpoint ini mengambil daftar kegiatan yang dapat diakses oleh user login berdasarkan tim/unit kerja dan penugasan pegawai.

## Base URL

```text
/api/v1
```

## Auth

Endpoint ini membutuhkan token Sanctum.

Header minimum:

```http
Accept: application/json
Authorization: Bearer {token}
```

Jika token tidak ada atau tidak valid:

```json
{
  "message": "Unauthenticated."
}
```

## Endpoint

### Ambil kegiatan berdasarkan tim user login dan penugasan

`GET /api/v1/kegiatan/tim-saya`

Query parameter: tidak ada.

Request body: tidak ada.

Contoh request:

```http
GET /api/v1/kegiatan/tim-saya
Accept: application/json
Authorization: Bearer {token}
```

## Rule Data Yang Ditampilkan

Backend akan mengambil user dari token login, lalu:

- membaca `users.id_tim` milik user login;
- membaca semua `keanggotan_tim.unit_kerja_id` berdasarkan `users.id_pegawai`;
- menampilkan kegiatan dengan `kegiatan.unit_kerja_id` yang sesuai dengan daftar tim/unit kerja tersebut;
- menambahkan kegiatan lain jika pegawai user login terdaftar di `penugasan_pegawai.id_pegawai`.

Contoh:

- User login memiliki `id_tim` `001` dan `002`.
- Pegawai user login juga terdaftar di `penugasan_pegawai` untuk `id_kegiatan` `46`.
- Response berisi kegiatan dari unit kerja `001`, kegiatan dari unit kerja `002`, ditambah kegiatan dengan `id_kegiatan` `46`.

## Format `users.id_tim`

Backend mendukung beberapa format:

```text
001
001,002
["001","002"]
```

Jika `id_tim` berisi `kode_unit`, backend akan mencari `unit_kerja.id` berdasarkan `unit_kerja.kode_unit`.

## Response Sukses

```json
{
  "success": true,
  "data": [
    {
      "id_kegiatan": 46,
      "nama_kegiatan": "Workshop Penyusunan Program",
      "tanggal_mulai": "2026-05-20",
      "tanggal_selesai": "2026-05-21",
      "lokasi": "Mataram",
      "status": "berjalan",
      "id_pegawai": 85,
      "unit_kerja_id": 1,
      "created_at": "2026-05-20T08:00:00.000000Z",
      "updated_at": "2026-05-20T08:00:00.000000Z"
    }
  ]
}
```

Data diurutkan dari `tanggal_mulai` terbaru.

Jika tidak ada kegiatan yang cocok:

```json
{
  "success": true,
  "data": []
}
```

## Contoh Frontend

```js
const response = await api.get('/api/v1/kegiatan/tim-saya')
const kegiatan = response.data.data
```
