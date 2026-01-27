# SIAMIN Backend API

SIAMIN Backend adalah API server untuk sistem informasi manajemen kepegawaian yang dibangun menggunakan Laravel 12.

## Requirements

- PHP 8.2+
- Composer
- SQLite (included)

## Installation

### 1. Navigate to Project Directory
```bash
cd siamin-backend
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Setup Database
```bash
php artisan migrate:fresh --seed
```

Perintah ini akan:
- Membuat database SQLite
- Menjalankan semua migrations
- Seed data test (4 users dengan role berbeda)

### 5. Start Development Server
```bash
php artisan serve
```

Server akan berjalan pada `http://127.0.0.1:8000`

## Project Structure

### Models
- **User** (`app/Models/User.php`)
  - Model untuk user authentication
  - Memiliki relasi dengan Pegawai
  - Fields: id_user, id_pegawai, email, password, role, last_login, status

- **Pegawai** (`app/Models/Pegawai.php`)
  - Model untuk data pegawai
  - Memiliki relasi dengan User
  - Fields: id_pegawai, nip, nama, jabatan, golongan, pangkat, dll

### Controllers
- **AuthController** (`app/Http/Controllers/Api/AuthController.php`)
  - Menangani login endpoint
  - Menangani profile endpoint (protected)
  - Menangani logout endpoint (protected)

### Routes
- **API Routes** (`routes/api.php`)
  - POST `/api/v1/login` - Login user
  - GET `/api/v1/profile` - Get user profile (protected)
  - POST `/api/v1/logout` - Logout user (protected)

### Database
- **Migrations** (`database/migrations/`)
  - `0001_01_01_000000_create_pegawai_table.php` - Create pegawai table
  - `0001_01_01_000000_create_users_table.php` - Create users table
  - `2026_01_27_030512_create_personal_access_tokens_table.php` - Sanctum tokens

- **Seeders** (`database/seeders/`)
  - `DatabaseSeeder.php` - Seed test data

## Quick Start

### Login dengan Test Account

**Credentials:**
```
Email: admin@siamin.test
Password: password123
```

**Curl Request:**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@siamin.test","password":"password123"}'
```

**Response:**
```json
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "user": {
            "id_user": 1,
            "email": "admin@siamin.test",
            "role": "admin",
            "pegawai": { ... }
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

## API Documentation

Lihat [API_DOCUMENTATION.md](API_DOCUMENTATION.md) untuk dokumentasi lengkap semua endpoints.

## Available Test Accounts

| Email | Password | Role | Pegawai |
|-------|----------|------|---------|
| admin@siamin.test | password123 | admin | Adi Pratama Nugroho |
| verifikator@siamin.test | password123 | verifikator | Siti Nurhaliza |
| operator@siamin.test | password123 | operator | Budi Handoko |
| kepala@siamin.test | password123 | kepala | None |

## Common Commands

### Database Operations
```bash
# Fresh migrate with seed data
php artisan migrate:fresh --seed

# Run only seeders
php artisan db:seed

# Rollback all migrations
php artisan migrate:rollback

# Reset everything
php artisan migrate:reset
```

### Cache Operations
```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear all caches
php artisan optimize:clear
```

### Development
```bash
# Start development server
php artisan serve

# Open tinker (interactive shell)
php artisan tinker

# Run tests
php artisan test
```

## Project Features

✅ **Authentication**
- User login dengan email & password
- Token-based authentication (Sanctum)
- Session tracking (last_login)

✅ **User Management**
- Multiple roles (admin, operator, verifikator, kepala)
- User status tracking (aktif/nonaktif)
- User profile endpoint

✅ **Employee Data**
- Complete employee information
- NIP (Nomor Induk Pegawai) management
- Position and rank tracking
- Education history
- Employment status tracking

✅ **Security**
- Password hashing (Bcrypt)
- Input validation
- Protected endpoints
- CORS ready

## Configuration

### Database (.env)
```env
DB_CONNECTION=sqlite
```

### Authentication (config/sanctum.php)
- Token expiration configurable
- Multiple guard support

### CORS (config/cors.php)
Configure allowed origins, methods, headers.

## Troubleshooting

### Port 8000 Already in Use
```bash
php artisan serve --port 8001
```

### Database Lock
```bash
php artisan migrate:refresh
```

### Clear All Caches
```bash
php artisan optimize:clear
```

### Composer Issues
```bash
composer update
composer dump-autoload
```

## Project Status

✅ Complete

- Database schema created
- Models and relationships established
- Authentication controller implemented
- API routes configured
- Test data seeded
- API documentation provided

## Next Steps (Optional)

Untuk pengembangan lebih lanjut:

1. **Tambah Endpoints**
   - Employee CRUD endpoints
   - Advanced filtering & search
   - Pagination support

2. **Security Enhancements**
   - Rate limiting
   - CORS configuration
   - Request validation middleware

3. **Testing**
   - Unit tests
   - Feature tests
   - API integration tests

4. **Documentation**
   - OpenAPI/Swagger docs
   - Postman collection

## Support

Untuk informasi lebih lanjut, lihat dokumentasi resmi:
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [SQLite](https://www.sqlite.org/)

## License

MIT License

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
