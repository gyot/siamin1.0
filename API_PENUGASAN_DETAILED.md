# Dokumentasi API Penugasan Pegawai - List dengan Detail

Dokumentasi ini menjelaskan endpoint untuk mendapatkan daftar penugasan pegawai dengan informasi lengkap termasuk data pegawai, kegiatan, dan lokasi. Endpoint ini dilengkapi dengan pagination, filtering, dan pencarian untuk performa optimal.

## Base URL

```text
/api/v1
```

## Authentication

Endpoint ini memerlukan authentication via Bearer token:

```http
Accept: application/json
Authorization: Bearer {token}
```

## Endpoint: Penugasan Pegawai dengan Detail

### GET `/api/v1/penugasan-pegawai-detailed`

Mengambil daftar penugasan pegawai dengan detail lengkap (pegawai, kegiatan, tanggal, lokasi). Supports pagination, filtering, dan pencarian.

#### Query Parameters

| Parameter | Type | Required | Default | Keterangan |
|-----------|------|----------|---------|-----------|
| `per_page` | integer | No | 15 | Jumlah data per halaman (max 100) |
| `page` | integer | No | 1 | Nomor halaman |
| `id_kegiatan` | integer | No | - | Filter by kegiatan ID |
| `id_pegawai` | integer | No | - | Filter by pegawai ID |
| `peran` | string | No | - | Filter by peran (penanggung_jawab, ketua_panitia, panitia, peserta, narasumber) |
| `search` | string | No | - | Cari berdasarkan nama pegawai atau nama kegiatan |
| `sort_by` | string | No | id | Sort by field: `id`, `id_kegiatan`, `id_pegawai`, `peran`, `tanggal_mulai` |
| `sort_order` | string | No | desc | Sort order: `asc` atau `desc` |

#### Response Format

Status: **200 OK**

```json
{
  "success": true,
  "data": [
    {
      "id": 7,
      "id_penugasan": 7,
      "id_kegiatan": 44,
      "id_pegawai": 83,
      "peran": "panitia",
      "nama_pegawai": "Budi Santoso",
      "nama_kegiatan": "Workshop Penyusunan Program",
      "tanggal_mulai": "2024-03-15",
      "tanggal_selesai": "2024-03-16",
      "kabupaten_kota": "Jakarta Pusat",
      "lokasi": "Hotel Grand Indonesia"
    },
    {
      "id": 8,
      "id_penugasan": 8,
      "id_kegiatan": 44,
      "id_pegawai": 84,
      "peran": "ketua_panitia",
      "nama_pegawai": "Siti Nurhaliza",
      "nama_kegiatan": "Workshop Penyusunan Program",
      "tanggal_mulai": "2024-03-15",
      "tanggal_selesai": "2024-03-16",
      "kabupaten_kota": "Jakarta Pusat",
      "lokasi": "Hotel Grand Indonesia"
    }
  ],
  "pagination": {
    "total": 125,
    "per_page": 15,
    "current_page": 1,
    "last_page": 9,
    "from": 1,
    "to": 15
  }
}
```

### Response Fields Explanation

| Field | Type | Keterangan |
|-------|------|-----------|
| `id` / `id_penugasan` | integer | ID penugasan |
| `id_kegiatan` | integer | ID kegiatan terkait |
| `id_pegawai` | integer | ID pegawai terkait |
| `peran` | string | Peran pegawai: penanggung_jawab, ketua_panitia, panitia, peserta, narasumber |
| `nama_pegawai` | string | Nama lengkap pegawai |
| `nama_kegiatan` | string | Nama kegiatan |
| `tanggal_mulai` | date | Tanggal mulai kegiatan (format: YYYY-MM-DD) |
| `tanggal_selesai` | date | Tanggal selesai kegiatan (format: YYYY-MM-DD) |
| `kabupaten_kota` | string | Kabupaten/Kota tempat kegiatan dilaksanakan |
| `lokasi` | string | Lokasi detail kegiatan (alamat/venue) |

### Pagination Info

| Field | Keterangan |
|-------|-----------|
| `total` | Total jumlah record |
| `per_page` | Jumlah record per halaman |
| `current_page` | Halaman saat ini |
| `last_page` | Halaman terakhir |
| `from` | Record index mulai (untuk halaman saat ini) |
| `to` | Record index akhir (untuk halaman saat ini) |

## Contoh Request

### 1. Get All Penugasan dengan Pagination Default (15 items)

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 2. Get Penugasan untuk Kegiatan Tertentu

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed?id_kegiatan=44" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 3. Get Penugasan dengan Pagination Custom (25 items per page, halaman 2)

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed?per_page=25&page=2" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 4. Cari Penugasan by Nama Pegawai atau Kegiatan

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed?search=Workshop" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 5. Filter by Peran

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed?peran=ketua_panitia&per_page=20" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### 6. Filter by Pegawai + Sort by Tanggal Mulai (Ascending)

```bash
curl -X GET "http://localhost/api/v1/penugasan-pegawai-detailed?id_pegawai=83&sort_by=tanggal_mulai&sort_order=asc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

## Contoh Request dalam JavaScript/Frontend

### Basic Fetch

```javascript
const token = localStorage.getItem('token');

fetch('/api/v1/penugasan-pegawai-detailed', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
})
.then(res => res.json())
.then(json => {
  console.log('Data:', json.data);
  console.log('Pagination:', json.pagination);
})
.catch(err => console.error('Error:', err));
```

### Dengan Filter dan Pagination

```javascript
const params = new URLSearchParams({
  per_page: 25,
  page: 1,
  id_kegiatan: 44,
  sort_by: 'tanggal_mulai',
  sort_order: 'asc'
});

fetch(`/api/v1/penugasan-pegawai-detailed?${params}`, {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
})
.then(res => res.json())
.then(json => {
  json.data.forEach(penugasan => {
    console.log(`${penugasan.nama_pegawai} - ${penugasan.nama_kegiatan}`);
  });
  console.log(`Total: ${json.pagination.total}, Halaman: ${json.pagination.current_page}/${json.pagination.last_page}`);
})
.catch(err => console.error('Error:', err));
```

### Dengan Search

```javascript
const searchTerm = 'Workshop';

fetch(`/api/v1/penugasan-pegawai-detailed?search=${encodeURIComponent(searchTerm)}&per_page=20`, {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${token}`
  }
})
.then(res => res.json())
.then(json => {
  console.log(`Found ${json.pagination.total} results`);
  console.log(json.data);
})
.catch(err => console.error('Error:', err));
```

### Using Axios (jika pakai axios)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
});

// Get all dengan pagination
api.get('/penugasan-pegawai-detailed?per_page=15&page=1')
  .then(res => {
    console.log(res.data.data);
    console.log(res.data.pagination);
  })
  .catch(err => console.error(err));

// With filters
api.get('/penugasan-pegawai-detailed', {
  params: {
    id_kegiatan: 44,
    peran: 'panitia',
    per_page: 20
  }
})
.then(res => console.log(res.data))
.catch(err => console.error(err));
```

## Performance Notes

✅ **Optimizations:**
- Query menggunakan `select()` untuk hanya fetch kolom yang diperlukan
- Relationships di-load dengan eager loading (menghindari N+1 queries)
- Pagination default 15 items, max 100 per page untuk performance
- Index pada `id_kegiatan`, `id_pegawai`, dan `peran` columns di database

⚡ **Rekomendasi Frontend:**
- Implement infinite scroll atau pagination UI
- Cache responses jika diperlukan
- Debounce search input agar tidak request terlalu sering
- Show loading indicator saat fetch data

## Error Responses

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

**Keterangan:** Token tidak dikirim atau tidak valid. Pastikan header `Authorization: Bearer {token}` dikirim dengan benar.

### 400 Bad Request (Invalid Filter)

```json
{
  "success": false,
  "message": "Invalid parameter",
  "errors": {
    "per_page": ["per_page must be an integer"]
  }
}
```

## Catatan Teknis

1. **Database Column:** Endpoint ini menggunakan field `kabupaten_kota` yang baru ditambahkan pada tabel `kegiatan`.
2. **Field Compatibility:** Jika `pegawai` atau `kegiatan` tidak ditemukan, API akan tetap mengembalikan record dengan value `"N/A"`.
3. **Sorting:** Sorting by `tanggal_mulai` menggunakan `LEFT JOIN` untuk fetch dari tabel `kegiatan`.
4. **Search:** Search mencari di field `nama` (pegawai) dan `nama_kegiatan` (kegiatan) menggunakan LIKE query.

## Migrations & Setup

Pastikan migration untuk `kabupaten_kota` column sudah di-run:

```bash
php artisan migrate --path=database/migrations/2026_05_24_000000_add_kabupaten_kota_to_kegiatan_table.php
```

## Changelog

### v1.0 (May 24, 2026)
- Initial release of detailed penugasan endpoint
- Added pagination support
- Added search functionality
- Added filtering by peran
- Added kabupaten_kota field display
