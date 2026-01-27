# SIAMIN Backend API Documentation

## Proyek: siamin-backend
Backend API untuk sistem informasi SIAMIN yang menyediakan fitur login user dan manajemen pegawai.

## Setup Awal

### 1. Konfigurasi Database
Database menggunakan SQLite dengan struktur 2 tabel utama:

#### Tabel `pegawai`
- `id_pegawai`: ID primary key (auto increment)
- `nip`: Nomor Induk Pegawai (unique, nullable)
- `nama`: Nama lengkap pegawai
- `tempat_lahir`: Tempat lahir
- `tanggal_lahir`: Tanggal lahir
- `tmt_cpns`: Tanggal mulai CPNS
- `tmt_pangkat`: Tanggal mulai pangkat
- `pangkat`: Nama pangkat
- `golongan`: Golongan kepegawaian (IV/d, III/b, dll)
- `nama_jabatan`: Nama jabatan
- `tmt_jabatan`: Tanggal mulai jabatan
- `pendidikan_terakhir`: Tingkat pendidikan terakhir
- `jurusan`: Jurusan pendidikan
- `tempat_pendidikan`: Nama institusi pendidikan
- `tahun_lulus`: Tahun kelulusan
- `latihan_jabatan`: Riwayat latihan jabatan
- `status_kepegawaian`: Enum (PNS, PPPK)
- `status`: Enum (aktif, nonaktif) - default: aktif
- `created_at`, `updated_at`: Timestamps

#### Tabel `users`
- `id_user`: ID primary key (auto increment)
- `id_pegawai`: Foreign key ke pegawai (nullable, cascade delete)
- `email`: Email unik (150 char)
- `password`: Password (hashed)
- `role`: Enum (admin, operator, verifikator, kepala) - default: operator
- `last_login`: Timestamp login terakhir (nullable)
- `status`: Enum (aktif, nonaktif) - default: aktif
- `created_at`, `updated_at`: Timestamps
- `personal_access_tokens`: Tabel untuk Sanctum tokens

### 2. Hubungan Model
- User **belongsTo** Pegawai (many-to-one)
- Pegawai **hasMany** Users (one-to-many)

## API Endpoints

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### 1. Login (Public)
**Endpoint:** `POST /login`

**Request Body:**
```json
{
    "email": "admin@siamin.test",
    "password": "password123"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "user": {
            "id_user": 1,
            "email": "admin@siamin.test",
            "role": "admin",
            "status": "aktif",
            "last_login": "2026-01-27T10:30:45.000000Z",
            "pegawai": {
                "id_pegawai": 1,
                "nip": "19700101200001100001",
                "nama": "Adi Pratama Nugroho",
                "nama_jabatan": "Kepala Dinas",
                "golongan": "IV/d",
                "pangkat": "Pembina Utama Madya"
            }
        },
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "token_type": "Bearer"
    }
}
```

**Error Response (422):**
```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email atau password tidak valid."]
    }
}
```

**Validation Rules:**
- `email`: Required, valid email format
- `password`: Required, string

### 2. Get Profile (Protected)
**Endpoint:** `GET /profile`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Data profil berhasil diambil",
    "data": {
        "id_user": 1,
        "email": "admin@siamin.test",
        "role": "admin",
        "status": "aktif",
        "last_login": "2026-01-27T10:30:45.000000Z",
        "pegawai": {
            "id_pegawai": 1,
            "nip": "19700101200001100001",
            "nama": "Adi Pratama Nugroho",
            "tempat_lahir": "Jakarta",
            "tanggal_lahir": "1970-01-01",
            "nama_jabatan": "Kepala Dinas",
            "golongan": "IV/d",
            "pangkat": "Pembina Utama Madya",
            "status_kepegawaian": "PNS"
        }
    }
}
```

### 3. Logout (Protected)
**Endpoint:** `POST /logout`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logout berhasil"
}
```

## Test Data

Berikut adalah test data yang tersedia di database:

### Users
1. **Admin**
   - Email: `admin@siamin.test`
   - Password: `password123`
   - Role: `admin`
   - Pegawai: Adi Pratama Nugroho

2. **Verifikator**
   - Email: `verifikator@siamin.test`
   - Password: `password123`
   - Role: `verifikator`
   - Pegawai: Siti Nurhaliza

3. **Operator**
   - Email: `operator@siamin.test`
   - Password: `password123`
   - Role: `operator`
   - Pegawai: Budi Handoko

4. **Kepala**
   - Email: `kepala@siamin.test`
   - Password: `password123`
   - Role: `kepala`
   - Pegawai: None (nullable)

## Testing API

### Menggunakan Test Page HTML
1. Buka `http://127.0.0.1:8000/test-login.html`
2. Masukkan email dan password
3. Klik tombol "Test Login"

### Menggunakan cURL
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@siamin.test","password":"password123"}'
```

### Menggunakan Postman
1. Set request type: POST
2. URL: `http://127.0.0.1:8000/api/v1/login`
3. Headers: `Content-Type: application/json`
4. Body (raw JSON):
```json
{
    "email": "admin@siamin.test",
    "password": "password123"
}
```

## File Structure

```
siamin-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── AuthController.php
│   │   │   └── Controller.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   └── Pegawai.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_pegawai_table.php
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   └── 2026_01_27_030512_create_personal_access_tokens_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php (API routes)
│   ├── web.php
│   └── console.php
├── bootstrap/
│   └── app.php (Main app config)
├── config/
│   ├── app.php
│   ├── database.php
│   ├── sanctum.php
│   └── auth.php
├── public/
│   ├── index.php
│   └── test-login.html (Test page)
├── .env
├── composer.json
└── README.md
```

## Development Commands

### Install Dependencies
```bash
composer install
```

### Run Migrations
```bash
php artisan migrate
```

### Fresh Migrations with Seed Data
```bash
php artisan migrate:fresh --seed
```

### Start Development Server
```bash
php artisan serve
```

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
```

### Generate App Key
```bash
php artisan key:generate
```

## Technologies Used

- **Laravel 12**: PHP web framework
- **Laravel Sanctum**: API token authentication
- **SQLite**: Lightweight database
- **Composer**: PHP dependency manager

## Security Features

1. **Password Hashing**: Bcrypt hashing untuk password user
2. **Token Authentication**: Menggunakan Laravel Sanctum untuk API tokens
3. **Validation**: Input validation untuk semua request
4. **Status Check**: Verifikasi status user (aktif/nonaktif)
5. **Last Login Tracking**: Mencatat waktu login terakhir user

## Error Handling

API mengembalikan response dengan struktur yang konsisten:

**Success Response:**
```json
{
    "success": true,
    "message": "Deskripsi hasil",
    "data": { /* data */ }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Deskripsi error",
    "errors": { /* validation errors */ }
}
```

HTTP Status Codes:
- `200`: OK - Request berhasil
- `422`: Validation Error - Input tidak valid
- `500`: Server Error - Kesalahan sistem

## Notes

- Semua password di test data adalah `password123`
- User yang tidak aktif tidak bisa login
- Token expiration dapat dikonfigurasi di `config/sanctum.php`
- Database otomatis di-reset dengan perintah `migrate:fresh --seed`
