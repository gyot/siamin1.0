# API Evaluasi Kegiatan

Endpoint evaluasi mengikuti pola versi API yang sudah dipakai project ini, yaitu prefix `/api/v1`.

## 1. Submit evaluasi

- Method: `POST`
- URL: `/api/v1/evaluasi`
- Auth: Public
- Rate limit: maksimal 3 submit per IP per 1 jam

Contoh request:

```json
{
  "id_kegiatan": 1,
  "id_tpk": 1,
  "program_tujuan": 5,
  "program_bahan_ajar": 4,
  "program_alokasi_waktu": 4,
  "fasilitator": [
    {
      "nama": "Dr. Ahmad Fauzi",
      "penguasaan_materi": 5,
      "sistematika": 5,
      "sikap": 4
    }
  ],
  "layanan_panitia": 5,
  "layanan_fasilitas": 4,
  "layanan_konsumsi": 4,
  "saran": "Kegiatan sangat bermanfaat."
}
```

Catatan:

- `id_kegiatan` mengikuti schema backend saat ini, yaitu numeric `id_kegiatan`.
- `id_tpk` opsional, mengacu pada `tpk.id_tpk`. Jika dikirim, evaluasi akan terkait dengan TPK tertentu.
- Evaluasi disimpan secara anonim tanpa identitas peserta.

Contoh response:

```json
{
  "success": true,
  "message": "Evaluasi berhasil dikirim",
  "data": {
    "id_evaluasi": "EVAL-20260326091530-ABCD",
    "id_kegiatan": 1,
    "id_tpk": 1,
    "tanggal_evaluasi": "2026-03-26T09:15:30.000000Z"
  }
}
```

## 2. List evaluasi per kegiatan (dan opsional per TPK)

- Method: `GET`
- URL: `/api/v1/evaluasi/{id_kegiatan}/{id_tpk?}`
- Auth: `auth:sanctum`
- Role: `admin`, `super_admin`, `operator`, `verifikator`, `kepala`

Parameter:
- `id_kegiatan` (required) — ID kegiatan
- `id_tpk` (optional) — ID TPK. Jika dikirim, hanya menampilkan evaluasi untuk TPK tersebut.

Contoh response:

```json
{
  "success": true,
  "data": [
    {
      "id_evaluasi": "EVAL-20260326091530-ABCD",
      "id_kegiatan": 1,
      "id_tpk": 1,
      "tanggal_evaluasi": "2026-03-26T09:15:30.000000Z",
      "program_tujuan": 5,
      "program_bahan_ajar": 4,
      "program_alokasi_waktu": 4,
      "fasilitator": [],
      "layanan_panitia": 5,
      "layanan_fasilitas": 4,
      "layanan_konsumsi": 4,
      "saran": "Kegiatan sangat bermanfaat."
    }
  ]
}
```

## 3. Statistik evaluasi

- Method: `GET`
- URL: `/api/v1/evaluasi/{id_kegiatan}/{id_tpk?}/statistik`
- Auth: Public

Parameter:
- `id_kegiatan` (required) — ID kegiatan
- `id_tpk` (optional) — ID TPK. Jika dikirim, statistik hanya untuk evaluasi pada TPK tersebut.

Contoh response:

```json
{
  "success": true,
  "data": {
    "total_evaluasi": 25,
    "rata_rata_program": 4.5,
    "rata_rata_fasilitator": 4.6,
    "rata_rata_layanan": 4.3,
    "detail_fasilitator": [
      {
        "nama": "Dr. Ahmad Fauzi",
        "jumlah_penilaian": 20,
        "rata_rata_penguasaan": 4.5,
        "rata_rata_sistematika": 4.4,
        "rata_rata_sikap": 4.7
      }
    ],
    "grafik_penilaian": {
      "5_bintang": 15,
      "4_bintang": 8,
      "3_bintang": 2,
      "2_bintang": 0,
      "1_bintang": 0
    }
  }
}
```

## 4. Check status evaluasi

- Method: `GET`
- URL: `/api/v1/evaluasi/check/{id_kegiatan}/{id_tpk?}`
- Auth: Public

Parameter:
- `id_kegiatan` (required) — ID kegiatan
- `id_tpk` (optional) — ID TPK. Jika dikirim, check hanya untuk evaluasi pada TPK tersebut.

Contoh response:

```json
{
  "success": true,
  "data": {
    "sudah_evaluasi": true
  }
}
```

## Validasi utama

- Nilai penilaian memakai rentang `1` sampai `5`
- `fasilitator` wajib berupa array dan minimal 1 item
- `id_tpk` opsional, jika dikirim harus valid di tabel `tpk`
- `saran` disanitasi dengan `strip_tags`
- Metadata `ip_address` dan `user_agent` otomatis direkam saat submit
