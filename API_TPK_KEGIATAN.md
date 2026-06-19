# API TPK Kegiatan

Dokumen ini menjelaskan perubahan fitur Tambah Data TPK pada modul Kegiatan.

## Analisis Database

Sebelumnya data tempat kegiatan disimpan langsung di tabel `kegiatan` melalui kolom:

- `lokasi`
- `kabupaten_kota`

Kebutuhan baru memindahkan data tersebut menjadi relasi satu-ke-banyak:

- Satu `kegiatan` memiliki satu atau lebih data `tpk`.
- Satu baris `tpk` berisi `lokasi` dan `kabupaten_kota`.
- Relasi memakai foreign key `tpk.id_kegiatan -> kegiatan.id_kegiatan`.
- Delete kegiatan akan menghapus data TPK terkait melalui `onDelete('cascade')`.

Migration `2026_06_19_000000_create_tpk_table.php` juga memigrasikan data lama:

- Jika `kegiatan.lokasi` berisi data, data tersebut dibuat menjadi baris awal di tabel `tpk`.
- Setelah migrasi, kolom `lokasi` dan `kabupaten_kota` di tabel `kegiatan` dihapus.

## Struktur Tabel TPK

```php
Schema::create('tpk', function (Blueprint $table) {
    $table->id('id_tpk');
    $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->onDelete('cascade');
    $table->string('lokasi', 255);
    $table->string('kabupaten_kota', 255)->nullable();
    $table->timestamps();
});
```

## Model dan Relasi

Model `App\Models\Tpk`:

- `$table = 'tpk'`
- `$primaryKey = 'id_tpk'`
- Fillable: `id_kegiatan`, `lokasi`, `kabupaten_kota`
- Relasi: `belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan')`

Model `App\Models\Kegiatan`:

- Relasi baru: `daftarTpk()`
- Response JSON menggunakan nama snake case `daftar_tpk`

## Validasi

Create kegiatan memakai `StoreKegiatanRequest`.

Field TPK:

```json
{
  "daftar_tpk": [
    {
      "lokasi": "Aula Kantor A",
      "kabupaten_kota": "Makassar"
    }
  ]
}
```

Aturan create:

- `daftar_tpk`: wajib, array, minimal 1 item
- `daftar_tpk.*.lokasi`: wajib, string, maksimal 255 karakter
- `daftar_tpk.*.kabupaten_kota`: opsional, nullable, string, maksimal 255 karakter

Aturan update:

- `daftar_tpk`: opsional, array, minimal 1 item jika dikirim
- Jika `daftar_tpk` dikirim, seluruh data TPK lama kegiatan akan diganti dengan payload baru.
- Jika `daftar_tpk` tidak dikirim, data TPK lama tidak berubah.

## Service dan Transaction

Orkestrasi create, update, delete kegiatan dipindahkan ke `App\Services\KegiatanService`.

Create kegiatan:

1. Validasi request.
2. Simpan file `flyer` dan `template_biodata` jika ada.
3. Jalankan `DB::transaction()`.
4. Simpan kegiatan.
5. Simpan `daftar_atk` jika ada.
6. Simpan `daftar_tpk`.
7. Load relasi `daftarAtk` dan `daftarTpk`.

Update kegiatan:

1. Validasi request.
2. Jalankan `DB::transaction()`.
3. Update kegiatan.
4. Jika `daftar_atk` dikirim, sinkronkan ulang ATK.
5. Jika `daftar_tpk` dikirim, sinkronkan ulang TPK.
6. Load relasi `daftarAtk` dan `daftarTpk`.

Rollback:

- Jika penyimpanan kegiatan gagal, ATK dan TPK tidak tersimpan.
- Jika penyimpanan ATK atau TPK gagal, perubahan kegiatan dibatalkan.
- File yang baru diunggah saat create/update akan dihapus kembali jika transaksi gagal.

## Endpoint

Base path: `/api/v1`

### Create Kegiatan + TPK

`POST /kegiatan`

Contoh request JSON:

```json
{
  "nama_kegiatan": "Pelatihan Pendamping TPK",
  "tanggal_mulai": "2026-07-01",
  "tanggal_selesai": "2026-07-03",
  "status": "draft",
  "id_pegawai": 1,
  "unit_kerja_id": 2,
  "metode_pelaksanaan": "luring",
  "daftar_tpk": [
    {
      "lokasi": "Aula Kantor A",
      "kabupaten_kota": "Makassar"
    },
    {
      "lokasi": "Balai Diklat",
      "kabupaten_kota": "Gowa"
    }
  ],
  "daftar_atk": [
    {
      "nama_barang": "Pulpen",
      "jumlah": 25,
      "satuan": "buah"
    }
  ]
}
```

Contoh response:

```json
{
  "success": true,
  "data": {
    "id_kegiatan": 10,
    "nama_kegiatan": "Pelatihan Pendamping TPK",
    "tanggal_mulai": "2026-07-01",
    "tanggal_selesai": "2026-07-03",
    "status": "draft",
    "id_pegawai": 1,
    "unit_kerja_id": 2,
    "daftar_atk": [
      {
        "id_kegiatan_atk": 5,
        "id_kegiatan": 10,
        "nama_barang": "Pulpen",
        "spesifikasi": null,
        "jumlah": 25,
        "satuan": "buah",
        "keterangan": null
      }
    ],
    "daftar_tpk": [
      {
        "id_tpk": 1,
        "id_kegiatan": 10,
        "lokasi": "Aula Kantor A",
        "kabupaten_kota": "Makassar"
      },
      {
        "id_tpk": 2,
        "id_kegiatan": 10,
        "lokasi": "Balai Diklat",
        "kabupaten_kota": "Gowa"
      }
    ]
  }
}
```

### Update Kegiatan + TPK

`PUT /kegiatan/{id}` atau `PATCH /kegiatan/{id}`

Contoh request:

```json
{
  "nama_kegiatan": "Pelatihan Pendamping TPK Revisi",
  "daftar_tpk": [
    {
      "lokasi": "Hotel Merdeka",
      "kabupaten_kota": "Makassar"
    }
  ]
}
```

Catatan: payload di atas mengganti seluruh daftar TPK kegiatan menjadi satu item.

### Detail Kegiatan Beserta TPK

`GET /kegiatan/{id}`

Response memuat:

- Data kegiatan
- `daftar_atk`
- `daftar_tpk`

### Delete Kegiatan

`DELETE /kegiatan/{id}`

Data `tpk` terkait ikut dihapus karena foreign key memakai cascade dan service juga menghapus relasi sebelum delete sesuai pola sinkronisasi project.

### CRUD TPK Terpisah

Endpoint resource tambahan:

- `GET /tpk`
- `GET /tpk?id_kegiatan=10`
- `POST /tpk`
- `GET /tpk/{id}`
- `PUT /tpk/{id}`
- `PATCH /tpk/{id}`
- `DELETE /tpk/{id}`

Contoh create TPK terpisah:

```json
{
  "id_kegiatan": 10,
  "lokasi": "Aula Kantor B",
  "kabupaten_kota": "Maros"
}
```

## Payload Frontend Vue.js

Contoh state form:

```js
const formKegiatan = reactive({
  nama_kegiatan: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  status: 'draft',
  id_pegawai: null,
  unit_kerja_id: null,
  metode_pelaksanaan: 'luring',
  daftar_tpk: [
    {
      lokasi: '',
      kabupaten_kota: ''
    }
  ],
  daftar_atk: []
})
```

Contoh payload submit:

```js
const payload = {
  ...formKegiatan,
  daftar_tpk: formKegiatan.daftar_tpk.map((item) => ({
    lokasi: item.lokasi,
    kabupaten_kota: item.kabupaten_kota || null
  }))
}
```

Untuk form multipart yang mengirim file, gunakan nama field array:

```js
formData.append('daftar_tpk[0][lokasi]', item.lokasi)
formData.append('daftar_tpk[0][kabupaten_kota]', item.kabupaten_kota || '')
```

## File yang Ditambahkan atau Diubah

- `database/migrations/2026_06_19_000000_create_tpk_table.php`
- `app/Models/Tpk.php`
- `app/Models/Kegiatan.php`
- `app/Http/Requests/Api/StoreKegiatanRequest.php`
- `app/Http/Requests/Api/UpdateKegiatanRequest.php`
- `app/Services/KegiatanService.php`
- `app/Http/Controllers/Api/KegiatanController.php`
- `app/Http/Controllers/Api/TpkController.php`
- `app/Http/Resources/KegiatanResource.php`
- `routes/api.php`
