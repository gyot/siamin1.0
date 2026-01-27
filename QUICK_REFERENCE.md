# SIAMIN Backend - Quick Reference

## 🚀 Start Here

```bash
# 1. Navigate to project
cd "c:\Users\MyPC One Pro L\Documents\website\siamin-backend\siamin-backend"

# 2. Start server
php artisan serve

# 3. Open test page
# Browser: http://127.0.0.1:8000/test-login.html
```

## 🔑 Quick Login

**Email:** `admin@siamin.test`  
**Password:** `password123`

## 📝 Test Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@siamin.test | password123 | admin |
| verifikator@siamin.test | password123 | verifikator |
| operator@siamin.test | password123 | operator |
| kepala@siamin.test | password123 | kepala |

## 🔗 API Endpoints

**Login:**
```
POST http://127.0.0.1:8000/api/v1/login
Content-Type: application/json

{
  "email": "admin@siamin.test",
  "password": "password123"
}
```

**Profile (requires token):**
```
GET http://127.0.0.1:8000/api/v1/profile
Authorization: Bearer {token}
```

**Logout (requires token):**
```
POST http://127.0.0.1:8000/api/v1/logout
Authorization: Bearer {token}
```

## 📂 Important Files

| File | Purpose |
|------|---------|
| `README.md` | Project setup guide |
| `API_DOCUMENTATION.md` | Complete API reference |
| `PROJECT_COMPLETION.md` | Project completion summary |
| `POSTMAN_COLLECTION.json` | Postman collection for testing |
| `app/Models/User.php` | User model |
| `app/Models/Pegawai.php` | Employee model |
| `app/Http/Controllers/Api/AuthController.php` | Authentication controller |
| `routes/api.php` | API routes |
| `database/migrations/` | Database migrations |
| `database/seeders/DatabaseSeeder.php` | Test data seeder |

## 🛠️ Common Commands

```bash
# Install dependencies
composer install

# Setup database
php artisan migrate:fresh --seed

# Start server
php artisan serve

# Clear caches
php artisan cache:clear
php artisan config:clear

# Database seeding
php artisan db:seed

# Tinker shell
php artisan tinker
```

## 📋 Database Schema

### users table
- id_user (PK)
- id_pegawai (FK to pegawai.id_pegawai)
- email (unique)
- password
- role (admin, operator, verifikator, kepala)
- last_login
- status (aktif, nonaktif)
- timestamps

### pegawai table
- id_pegawai (PK)
- nip (unique, nullable)
- nama
- tempat_lahir
- tanggal_lahir
- tmt_cpns
- tmt_pangkat
- pangkat
- golongan
- nama_jabatan
- tmt_jabatan
- pendidikan_terakhir
- jurusan
- tempat_pendidikan
- tahun_lulus
- latihan_jabatan
- status_kepegawaian (PNS, PPPK)
- status (aktif, nonaktif)
- timestamps

## 🧪 Testing

### Browser Test Page
```
http://127.0.0.1:8000/test-login.html
```

### cURL Test
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@siamin.test","password":"password123"}'
```

### Postman
1. Import `POSTMAN_COLLECTION.json` into Postman
2. Set `base_url` variable to `http://127.0.0.1:8000`
3. Run requests from collection

## ✅ Features

✓ User authentication  
✓ Token-based API access  
✓ User profile with employee data  
✓ Multiple user roles  
✓ Password hashing  
✓ Session tracking  
✓ Status management  
✓ Error handling  
✓ Input validation  

## 🔐 Default Configuration

- **Database:** SQLite (database.sqlite)
- **Server:** http://127.0.0.1:8000
- **API Version:** v1
- **Authentication:** Laravel Sanctum (Bearer tokens)
- **Password Hashing:** Bcrypt

## 📚 Documentation

1. **README.md** - Setup & usage guide
2. **API_DOCUMENTATION.md** - API reference
3. **PROJECT_COMPLETION.md** - Project details
4. **POSTMAN_COLLECTION.json** - Postman requests

## 🎯 Next Steps

1. Test login with test account
2. Review API documentation
3. Import Postman collection
4. Test all endpoints
5. Ready for development!

## 📞 Support

For issues or questions:
1. Check README.md for setup
2. Review API_DOCUMENTATION.md for endpoint details
3. Check database migrations for schema
4. View AuthController for implementation

## 🎉 Ready to Go!

Your SIAMIN Backend API is ready for:
- User authentication
- Token-based access control
- Employee data management
- Web/Mobile applications

Start with login endpoint and explore from there!
