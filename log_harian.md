# Log Harian — Prompt Otomatis

Gunakan prompt ini setiap akhir hari kerja untuk membuat rekap perubahan dan mengunggahnya.

## Prompt

```
Rekap perubahan hari ini baik di frontend (siamin-ui) dan backend (siamin1.0) jadikan file MD dan PDF agar jadi rujukan saat ingin melanjutkan pekerjaan nanti, kemudian setelah file PDFnya jadi langsung upload ke https://bpmpntb.id/assets_back/login.php dengan user 199502102025211047 dan password 123456 sesuaikan judul dan tanggalnya dengan kegiatan yang sudah dilaksanakan.
```

## Cara Cepat

Ketik: `jalankan log_harian.md`

## Yang Dilakukan Otomatis

1. **Ambil diff** dari kedua repo:
   - Backend: `C:\Users\MyPC One Pro L\Documents\website\siamin1.0`
   - Frontend: `C:\Users\MyPC One Pro L\Documents\website\siamin-ui`

2. **Buat file MD** di `REKAP/YYYY-MM-DD.md` dengan format:
   - Judul: `Rekap Perubahan — [Tanggal Lengkap]`
   - Section Backend: tabel per file + penjelasan
   - Section Frontend: tabel per file + penjelasan
   - Ringkasan perubahan file
   - Catatan teknis

3. **Convert ke PDF** menggunakan `npx md-to-pdf REKAP/YYYY-MM-DD.md`

4. **Upload PDF** ke bpmpntb.id:
   - Login: POST ke `auth.php` dengan `username=199502102025211047&password=123456`
   - Upload: POST ke `upload.php` dengan field:
     - `nm_dok`: judul deskriptif (contoh: "Log Harian 2 Juli 2026 - [ringkasan singkat]")
     - `tanggal`: format `YYYY-MM-DD`
     - `file`: file PDF

## Struktur Folder

```
siamin1.0/
├── REKAP/
│   ├── 2026-06-01.md
│   ├── 2026-06-01.pdf
│   ├── 2026-06-03.md
│   ├── ...
│   └── 2026-MM-DD.md    ← file baru setiap hari
└── log_harian.md         ← file ini
```
