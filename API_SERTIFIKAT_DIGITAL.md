# Dokumentasi API Sertifikat Digital

Dokumen ini menjelaskan fitur Sertifikat Digital berbasis `sertifikat_batch` dan `sertifikat_peserta`.

## Base URL

```text
/api/v1
```

## Auth

Endpoint generate, update status, hapus sertifikat peserta, dan CRUD `sertifikat-batch` berada di grup protected. Frontend wajib mengirim token.

Header minimum:

```http
Accept: application/json
Authorization: Bearer {token}
```

Endpoint baca berikut tersedia tanpa middleware auth sesuai konfigurasi route saat ini:

```text
GET /api/v1/kegiatan/{id}/peserta-sertifikat
GET /api/v1/kegiatan/{id}/sertifikat-batch
```

## Struktur Data

### Tabel `sertifikat_batch`

Satu kegiatan dapat memiliki satu atau lebih batch sertifikat.

Kolom utama:

- `id_batch`
- `id_kegiatan`
- `nomor_sertifikat`
- `id_penandatangan`
- `tanggal_ttd`
- `template_file`
- `status`
- `created_at`
- `updated_at`

Nilai `status`:

- `draft`
- `terbit`
- `dicabut`

Relasi:

- `sertifikat_batch.id_kegiatan` ke `kegiatan.id_kegiatan`
- `sertifikat_batch.id_penandatangan` ke `pegawai.id_pegawai`

### Tabel `sertifikat_peserta`

Satu baris berarti satu peserta memiliki data sertifikat pada satu batch.

Kolom utama:

- `id`
- `id_batch`
- `id_peserta`
- `qr_token`
- `status`
- `created_at`
- `updated_at`

Nilai `status`:

- `draft`
- `terbit`
- `dicabut`

Relasi:

- `sertifikat_peserta.id_batch` ke `sertifikat_batch.id_batch`
- `sertifikat_peserta.id_peserta` ke `peserta.id_peserta`

Constraint duplikasi:

```text
UNIQUE (id_batch, id_peserta)
```

## Relationship Eloquent

Model yang ditambahkan:

- `App\Models\SertifikatBatch`
- `App\Models\SertifikatPeserta`

Relasi yang tersedia:

```php
// Kegiatan
$kegiatan->peserta()
$kegiatan->sertifikatBatch()

// Pegawai
$pegawai->sertifikatBatchDitandatangani()

// Peserta
$peserta->sertifikatPeserta()

// SertifikatBatch
$batch->kegiatan()
$batch->penandatangan()
$batch->sertifikatPeserta()

// SertifikatPeserta
$sertifikatPeserta->batch()
$sertifikatPeserta->peserta()
```

## Format Response

Response umum menggunakan format:

```json
{
  "success": true,
  "message": "Pesan opsional",
  "data": {}
}
```

Untuk endpoint cek batch, format sengaja dibuat sesuai kebutuhan frontend:

```json
{
  "exists": false
}
```

atau:

```json
{
  "exists": true,
  "data": []
}
```

## Endpoint Sertifikat Batch

### 1. Cek batch berdasarkan kegiatan

`GET /api/v1/kegiatan/{id}/sertifikat-batch`

Jika belum ada:

```json
{
  "exists": false
}
```

Jika ada:

```json
{
  "exists": true,
  "data": [
    {
      "id_batch": 3,
      "id_kegiatan": 10,
      "nomor_sertifikat": "800/123/BPSDM/2026",
      "id_penandatangan": 5,
      "tanggal_ttd": "2026-06-01",
      "template_file": "templates/sertifikat.docx",
      "status": "draft",
      "created_at": "2026-06-01T01:00:00.000000Z",
      "updated_at": "2026-06-01T01:00:00.000000Z"
    }
  ]
}
```

Catatan: karena satu kegiatan bisa memiliki beberapa batch, `data` berupa array.

### 2. List batch

`GET /api/v1/sertifikat-batch`

Response sukses:

```json
{
  "success": true,
  "data": [
    {
      "id_batch": 3,
      "id_kegiatan": 10,
      "nomor_sertifikat": "800/123/BPSDM/2026",
      "id_penandatangan": 5,
      "tanggal_ttd": "2026-06-01",
      "template_file": "templates/sertifikat.docx",
      "status": "draft"
    }
  ]
}
```

### 3. Buat batch

`POST /api/v1/sertifikat-batch`

Payload minimum:

```json
{
  "id_kegiatan": 10,
  "nomor_sertifikat": "800/123/BPSDM/2026"
}
```

Payload lengkap:

```json
{
  "id_kegiatan": 10,
  "nomor_sertifikat": "800/123/BPSDM/2026",
  "id_penandatangan": 5,
  "tanggal_ttd": "2026-06-01",
  "template_file": "templates/sertifikat.docx",
  "status": "draft"
}
```

Validasi:

- `id_kegiatan`: wajib, harus ada di tabel `kegiatan`
- `nomor_sertifikat`: wajib, string maksimal 150 karakter
- `id_penandatangan`: opsional, harus ada di tabel `pegawai`
- `tanggal_ttd`: opsional, format tanggal
- `template_file`: opsional, string maksimal 255 karakter
- `status`: opsional, salah satu dari `draft`, `terbit`, `dicabut`

Response sukses:

```json
{
  "success": true,
  "message": "Batch sertifikat berhasil dibuat.",
  "data": {
    "id_batch": 3,
    "id_kegiatan": 10,
    "nomor_sertifikat": "800/123/BPSDM/2026",
    "id_penandatangan": 5,
    "tanggal_ttd": "2026-06-01",
    "template_file": "templates/sertifikat.docx",
    "status": "draft"
  }
}
```

### 4. Detail batch

`GET /api/v1/sertifikat-batch/{id_batch}`

### 5. Update batch

`PATCH /api/v1/sertifikat-batch/{id_batch}`

Contoh payload:

```json
{
  "status": "terbit",
  "tanggal_ttd": "2026-06-01"
}
```

### 6. Hapus batch

`DELETE /api/v1/sertifikat-batch/{id_batch}`

Response sukses:

```json
{
  "success": true,
  "message": "Batch sertifikat berhasil dihapus."
}
```

Menghapus batch akan menghapus data `sertifikat_peserta` terkait karena foreign key memakai cascade.

## Endpoint Daftar Peserta Dengan Badge Sertifikat

### List peserta per kegiatan dengan status sertifikat

`GET /api/v1/kegiatan/{id}/peserta-sertifikat`

Response sukses:

```json
{
  "success": true,
  "data": [
    {
      "id_peserta": 1,
      "nama_lengkap": "Budi",
      "peran": "peserta",
      "status_sertifikat": "belum_ada",
      "id_batch": null
    },
    {
      "id_peserta": 2,
      "nama_lengkap": "Siti",
      "peran": "peserta",
      "status_sertifikat": "terbit",
      "id_batch": 3
    }
  ]
}
```

Nilai `status_sertifikat` untuk badge frontend:

- `belum_ada`
- `draft`
- `terbit`
- `dicabut`

Saran label badge:

```text
belum_ada = Belum Ada Sertifikat
draft     = Draft
terbit    = Sudah Terbit
dicabut   = Dicabut
```

## Endpoint Generate Sertifikat Peserta

### 1. Generate satu peserta

`POST /api/v1/sertifikat/generate`

Payload:

```json
{
  "id_batch": 1,
  "id_peserta": 25
}
```

Payload dengan status awal:

```json
{
  "id_batch": 1,
  "id_peserta": 25,
  "status": "terbit"
}
```

Validasi bisnis:

- `id_batch` harus ada di `sertifikat_batch`
- `id_peserta` harus ada di `peserta`
- peserta harus berasal dari kegiatan yang sama dengan batch
- jika kombinasi `id_batch + id_peserta` sudah ada, data tidak dibuat duplikat

Response sukses:

```json
{
  "success": true,
  "message": "Sertifikat peserta berhasil dibuat.",
  "data": {
    "id": 12,
    "id_batch": 1,
    "id_peserta": 25,
    "qr_token": "0e804a9f-135e-44d7-9386-fd67e5f2d7d8",
    "status": "draft"
  }
}
```

Jika peserta bukan bagian dari kegiatan batch:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "peserta_ids": [
      "Semua peserta harus berasal dari kegiatan yang sama dengan batch sertifikat."
    ]
  }
}
```

### 2. Generate massal

`POST /api/v1/sertifikat/generate-massal`

Payload:

```json
{
  "id_batch": 1,
  "peserta_ids": [1, 2, 3, 4, 5]
}
```

Validasi:

- `id_batch`: wajib, harus ada di tabel `sertifikat_batch`
- `peserta_ids`: wajib, array minimal 1 item
- `peserta_ids.*`: wajib, integer, distinct, harus ada di tabel `peserta`
- semua peserta harus berasal dari kegiatan yang sama dengan batch

Proses backend:

- menggunakan database transaction
- menggunakan `upsert`
- mencegah duplikasi melalui unique key `id_batch + id_peserta`
- record baru dibuat dengan status `draft`
- record lama tidak diubah statusnya

Response sukses:

```json
{
  "success": true,
  "message": "Generate sertifikat massal berhasil diproses.",
  "data": [
    {
      "id": 12,
      "id_batch": 1,
      "id_peserta": 1,
      "qr_token": "0e804a9f-135e-44d7-9386-fd67e5f2d7d8",
      "status": "draft"
    }
  ]
}
```

## Endpoint Status dan Hapus Sertifikat Peserta

### 1. Ubah status sertifikat peserta

`PATCH /api/v1/sertifikat-peserta/{id}/status`

Payload:

```json
{
  "status": "terbit"
}
```

Nilai `status` yang diterima:

- `draft`
- `terbit`
- `dicabut`

Response sukses:

```json
{
  "success": true,
  "message": "Status sertifikat peserta berhasil diperbarui.",
  "data": {
    "id": 12,
    "id_batch": 1,
    "id_peserta": 25,
    "qr_token": "0e804a9f-135e-44d7-9386-fd67e5f2d7d8",
    "status": "terbit"
  }
}
```

### 2. Hapus sertifikat peserta

`DELETE /api/v1/sertifikat-peserta/{id}`

Response sukses:

```json
{
  "success": true,
  "message": "Sertifikat peserta berhasil dihapus."
}
```

## Alur Frontend

### Alur tombol "Buat Sertifikat"

1. Frontend memanggil `GET /api/v1/kegiatan/{id_kegiatan}/sertifikat-batch`.
2. Jika response `exists: false`, tampilkan pesan bahwa batch sertifikat belum tersedia.
3. Jika response `exists: true`, pilih `id_batch` yang akan digunakan.
4. Frontend memanggil `POST /api/v1/sertifikat/generate`.
5. Refresh daftar peserta lewat `GET /api/v1/kegiatan/{id_kegiatan}/peserta-sertifikat`.

Contoh:

```js
const batchResponse = await api.get(`/api/v1/kegiatan/${idKegiatan}/sertifikat-batch`)

if (!batchResponse.data.exists) {
  throw new Error('Batch sertifikat belum tersedia')
}

const idBatch = batchResponse.data.data[0].id_batch

await api.post('/api/v1/sertifikat/generate', {
  id_batch: idBatch,
  id_peserta: idPeserta
})
```

### Alur generate massal

1. Operator memilih beberapa peserta.
2. Frontend memastikan batch tersedia.
3. Frontend mengirim `id_batch` dan `peserta_ids`.
4. Backend membuat data yang belum ada dan melewati duplikasi.
5. Frontend refresh daftar badge peserta.

Contoh:

```js
await api.post('/api/v1/sertifikat/generate-massal', {
  id_batch: 1,
  peserta_ids: [1, 2, 3, 4, 5]
})
```

### Ubah badge menjadi Terbit

```js
await api.patch(`/api/v1/sertifikat-peserta/${idSertifikatPeserta}/status`, {
  status: 'terbit'
})
```

### Cabut sertifikat

```js
await api.patch(`/api/v1/sertifikat-peserta/${idSertifikatPeserta}/status`, {
  status: 'dicabut'
})
```

## Query Badge Tanpa N+1

Endpoint daftar peserta menggunakan eager loading terfilter, bukan query per peserta.

Contoh query Eloquent:

```php
$peserta = Peserta::query()
    ->select(['id_peserta', 'id_kegiatan', 'nama_lengkap', 'peran'])
    ->where('id_kegiatan', $id)
    ->with(['sertifikatPeserta' => function ($query) use ($id) {
        $query->select(['id', 'id_batch', 'id_peserta', 'status'])
            ->whereHas('batch', fn ($batchQuery) => $batchQuery->where('id_kegiatan', $id))
            ->with(['batch:id_batch,id_kegiatan'])
            ->orderByDesc('id');
    }])
    ->orderBy('nama_lengkap')
    ->get();

$data = $peserta->map(function (Peserta $item) {
    $sertifikat = $item->sertifikatPeserta->first();

    return [
        'id_peserta' => $item->id_peserta,
        'nama_lengkap' => $item->nama_lengkap,
        'peran' => $item->peran,
        'status_sertifikat' => $sertifikat?->status ?? 'belum_ada',
        'id_batch' => $sertifikat?->id_batch,
    ];
});
```

## File Backend Terkait

```text
app/Models/SertifikatBatch.php
app/Models/SertifikatPeserta.php
app/Http/Controllers/Api/SertifikatBatchController.php
app/Http/Controllers/Api/SertifikatPesertaController.php
app/Http/Requests/Api/StoreSertifikatBatchRequest.php
app/Http/Requests/Api/UpdateSertifikatBatchRequest.php
app/Http/Requests/Api/GenerateSertifikatRequest.php
app/Http/Requests/Api/GenerateMassalSertifikatRequest.php
app/Http/Resources/SertifikatBatchResource.php
app/Http/Resources/SertifikatPesertaResource.php
database/migrations/2026_06_01_000000_create_sertifikat_batch_table.php
database/migrations/2026_06_01_000001_create_sertifikat_peserta_table.php
routes/api.php
```

## Perintah Verifikasi

```bash
php artisan migrate
php artisan route:list --path=sertifikat
```

Jika hanya ingin melihat SQL migration tanpa mengubah database:

```bash
php artisan migrate --pretend
```
