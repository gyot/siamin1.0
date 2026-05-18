# Dashboard API

Dokumen ini menjelaskan endpoint backend yang dioptimalkan untuk dashboard frontend.

## Endpoint List Kegiatan

Route lama tetap dipertahankan:

`GET /api/v1/kegiatan/all`

Endpoint ini sekarang mendukung filter dan pagination di level database.

### Query Params

- `page`: integer, minimal `1`, default `1`
- `limit`: integer, minimal `1`, maksimal `100`, default `9`
- `search`: string, pencarian pada `nama_kegiatan`
- `tahun`: string 4 digit, filter berdasarkan `tanggal_mulai`
- `status`: `draft|berjalan|selesai|dibatalkan`
- `unit_kerja`: bisa `id`, `kode_unit`, atau potongan `nama_unit`

### Contoh Request

```http
GET /api/v1/kegiatan/all?page=1&limit=9&search=workshop&tahun=2026&status=berjalan&unit_kerja=BPMP
Accept: application/json
```

### Contoh Response

```json
{
  "success": true,
  "data": [
    {
      "id_kegiatan": 10,
      "nama_kegiatan": "Workshop Penyusunan Program",
      "tanggal_mulai": "2026-04-23",
      "tanggal_selesai": "2026-04-24",
      "lokasi": "Mataram",
      "status": "berjalan",
      "total_peserta": 120,
      "peserta_ringkasan": "120 peserta",
      "unit_kerja_id": 12,
      "unit_kerja": "BPMP"
    }
  ],
  "total": 123,
  "current_page": 1,
  "per_page": 9,
  "last_page": 14
}
```

### Validasi Query Params

Jika query param tidak valid, backend mengembalikan `422`.

Contoh:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "limit": [
      "The limit field must not be greater than 100."
    ]
  }
}
```

## Endpoint Statistik Dashboard

`GET /api/v1/dashboard/stats`

Endpoint ini disiapkan agar frontend tidak perlu menarik seluruh tabel besar hanya untuk menghitung kartu statistik dan opsi filter.

### Contoh Response

```json
{
  "total_kegiatan": 123,
  "kegiatan_berjalan": 8,
  "total_peserta": 4567,
  "total_sertifikat_terbit": 3200,
  "available_tahun": ["2026", "2025", "2024"],
  "available_unit_kerja": ["BPMP", "GTK", "Umum"]
}
```

### Definisi Nilai

- `total_kegiatan`: total row pada tabel `kegiatan`
- `kegiatan_berjalan`: kegiatan dengan `tanggal_mulai <= hari ini`, `tanggal_selesai >= hari ini`, dan `status != dibatalkan`
- `total_peserta`: hasil agregasi `count(*)` dari tabel `peserta`
- `total_sertifikat_terbit`: hasil agregasi `count(*)` dari tabel `sertifikat` dengan `status = terbit`
- `available_tahun`: daftar tahun unik dari `tanggal_mulai`
- `available_unit_kerja`: daftar nama unit kerja unik yang sudah memiliki kegiatan

## Struktur Implementasi

- Validasi query params:
  `app/Http/Requests/Api/DashboardKegiatanIndexRequest.php`
- Controller tipis:
  `app/Http/Controllers/Api/KegiatanController.php`
  `app/Http/Controllers/Api/DashboardController.php`
- Query/service:
  `app/Services/DashboardService.php`
- JSON transformer:
  `app/Http/Resources/DashboardKegiatanResource.php`
  `app/Http/Resources/DashboardKegiatanCollection.php`

## Catatan Performa

- Pagination memakai paginator database Laravel, bukan `get()` lalu slice di memory.
- List kegiatan hanya memilih kolom yang dipakai dashboard.
- Tidak ada eager loading relasi besar untuk endpoint list dashboard.
- Statistik memakai agregasi `count()` langsung di database.
- Index tambahan disiapkan di migration:
  `database/migrations/2026_04_23_000005_add_dashboard_indexes.php`

Index yang ditambahkan:

- `kegiatan.status`
- `kegiatan.tanggal_mulai`
- `kegiatan.unit_kerja_id`
- `kegiatan.nama_kegiatan`
- indeks gabungan `kegiatan(status, tanggal_mulai, unit_kerja_id)`
- `peserta.id_kegiatan`
- `sertifikat.status`
- `sertifikat.id_kegiatan`

## Catatan Kompatibilitas

- Route `GET /api/v1/kegiatan/all` tetap dipakai, jadi client lama tidak langsung putus.
- Response sekarang menambahkan metadata pagination di level atas.
- Jika client lama hanya memakai `data`, respons tetap kompatibel karena key `data` masih ada.
