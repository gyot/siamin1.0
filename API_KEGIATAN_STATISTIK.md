# Dokumentasi API Statistik Kegiatan

Dokumen ini menjelaskan endpoint statistik ringkas untuk kebutuhan kartu dashboard atau ringkasan kegiatan.

## Base URL

```text
/api/v1
```

## Auth

Endpoint ini berada di grup publik, jadi tidak membutuhkan token login.

Header yang disarankan:

```http
Accept: application/json
```

## Endpoint

### Ambil statistik kegiatan

`GET /api/v1/kegiatan/statistik`

Endpoint ini mengambil seluruh data dari view/tabel `info_dashboard`.

Query parameter: tidak ada.

Request body: tidak ada.

Contoh request:

```http
GET /api/v1/kegiatan/statistik
Accept: application/json
```

Response sukses:

```json
{
  "success": true,
  "data": [
    {
      "total_peserta": 28,
      "total_kegiatan": 9,
      "total_sertifikat": 0,
      "total_kegiatan_berjalan": 0
    }
  ]
}
```

## Struktur Field

- `total_peserta`: total peserta yang tercatat.
- `total_kegiatan`: total kegiatan yang tercatat.
- `total_sertifikat`: total sertifikat yang tercatat.
- `total_kegiatan_berjalan`: total kegiatan yang sedang berjalan.

## Catatan Frontend

- Data dikembalikan dalam bentuk array karena backend memakai `DB::table('info_dashboard')->get()`.
- Jika `info_dashboard` hanya berisi satu baris ringkasan, frontend dapat mengambil item pertama dari `data`.
- Endpoint ini cocok untuk statistik global, bukan statistik per kegiatan.

Contoh penggunaan:

```js
const response = await api.get('/api/v1/kegiatan/statistik')
const statistik = response.data.data[0] ?? {
  total_peserta: 0,
  total_kegiatan: 0,
  total_sertifikat: 0,
  total_kegiatan_berjalan: 0
}
```

## Kemungkinan Error

Jika view/tabel `info_dashboard` belum tersedia di database, backend akan mengembalikan error server.

Contoh kondisi:

```json
{
  "message": "SQLSTATE[42S02]: Base table or view not found..."
}
```

HTTP status untuk kondisi tersebut biasanya `500`.
