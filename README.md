<div align="center">
  <h1>180DC UNAIR 2026 - Marcom - IT Analyst - Backend Test Case</h1>
  
  ![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)
  ![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
  ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?logo=postgresql&logoColor=white)
  ![JWT](https://img.shields.io/badge/JWT-Auth-000000?logo=jsonwebtokens&logoColor=white)
  ![Tests](https://img.shields.io/badge/Tests-45%20passed-brightgreen)
  
</div>

**Nama Kandidat:** Naufal Bagaskara  
**Divisi yang Dilamar:** Marketing and Communication - IT Analyst - Backend  
**Tech Stack:** Laravel 13.8, PHP 8.3, PostgreSQL 18, JWT Authentication

## Daftar Isi
1. [Tentang Proyek](#tentang-proyek)
2. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
3. [Instalasi dan Setup](#instalasi-dan-setup)
4. [Database Schema](#database-schema)
5. [API Endpoints Documentation](#api-endpoints-documentation)
6. [Unit Tests](#menjalankan-unit-tests)
7. [Technical Reasoning](#technical-reasoning)

## Tentang Proyek
REST API untuk manajemen produk dengan autentikasi **stateless JWT**.
Proyek ini mengimplementasikan 7 test case yang mencakup:

- Autentikasi (Register & Login)
- CRUD Produk dengan authorization
- Pagination, search, dan sorting
- Health check endpoint

## Teknologi yang Digunakan
- **Framework:** Laravel 13.8
- **PHP Version:** 8.3+
- **Database:** PostgreSQL 18
- **Authentication:** JWT (php-open-source-saver/jwt-auth ^2.9)
- **Testing:** PHPUnit 11.x
- **Password Hashing:** Bcrypt

## Status Test Case
- [x] **TC1** – Register User (validasi email unik, password hash, return JWT)
- [x] **TC2** – Login User (JWT payload mengandung `user_id` & `exp`)
- [x] **TC3** – Create Product (wajib auth, owner_id dari token, validasi price > 0)
- [x] **TC4** – Get Products List (pagination, search case‑insensitive, sort)
- [x] **TC5** – Get Product by ID (validasi UUID, 404 jika tidak ada)
- [x] **TC6** – Update Product (ownership check, partial update, 403 for non owner)
- [x] **TC7** – Delete Product (soft delete, tidak muncul di list) & Health Check

## Instalasi dan Setup
### Prasyarat
- PHP 8.3 atau lebih tinggi
- Composer
- PostgreSQL 16+
- Git

### Langkah Instalasi
1. Clone repository

```bash
git clone https://github.com/naufalbagaskaraaaa/bismillah_be_180dc_unair_naufal_bagaskara
```

2. Install dependencies

```bash
composer install
```

3. Setup environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

4. Konfigurasi database di file `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sesuain_mas
DB_USERNAME=sesuain_mas
DB_PASSWORD="sesuain_mas"
```

5. Jalankan migration

```bash
php artisan migrate
```

6. Jalankan server

```bash
php artisan serve
```

Server akan berjalan di `http://localhost:8000`

## Database Schema
### Tabel `users`

| Field      | Type         | Constraint       |
| ---------- | ------------ | ---------------- |
| id         | UUID         | Primary Key      |
| name       | VARCHAR(255) | NOT NULL         |
| email      | VARCHAR(255) | UNIQUE, NOT NULL |
| password   | VARCHAR(255) | NOT NULL         |
| created_at | TIMESTAMP    |                  |
| updated_at | TIMESTAMP    |                  |

### Tabel `products`

| Field      | Type          | Constraint             |
| ---------- | ------------- | ---------------------- |
| id         | UUID          | Primary Key            |
| name       | VARCHAR(255)  | NOT NULL               |
| price      | DECIMAL(10,2) | NOT NULL, > 0          |
| owner_id   | UUID          | Foreign Key → users.id |
| deleted_at | TIMESTAMP     | Nullable (soft delete) |
| created_at | TIMESTAMP     |                        |
| updated_at | TIMESTAMP     |                        |

## API Endpoints Documentation
## Ringkasan Endpoint

| Method | Endpoint                | Auth             | Fungsi                                   |
| ------ | ----------------------- | ---------------- | ---------------------------------------- |
| POST   | `/api/v1/auth/register` | No need          | Registrasi user                          |
| POST   | `/api/v1/auth/login`    | No need          | Login & mendapatkan JWT                  |
| POST   | `/api/v1/products`      | Yes (bearer)     | Buat produk baru                         |
| GET    | `/api/v1/products`      | No need (publik) | Daftar produk (pagination, search, sort) |
| GET    | `/api/v1/products/{id}` | Yes              | Detail produk                            |
| PATCH  | `/api/v1/products/{id}` | Yes              | Update produk (owner only)               |
| DELETE | `/api/v1/products/{id}` | Yes              | Hapus produk (owner only)                |
| GET    | `/health`               | No need          | Health check                             |

### TC1 - Register User
**POST** `/api/v1/auth/register`

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": "9d4f5e6a-7b8c-9d0e-1f2a-3b4c5d6e7f8g",
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2024-01-15T10:30:00Z"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
    }
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Validation Error",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password must be at least 6 characters."]
    }
}
```

### TC2 - Login User
**POST** `/api/v1/auth/login`

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

**Error Response (401):**

```json
{
    "success": false,
    "message": "Invalid token"
}
```

### TC3 - Create Product
**POST** `/api/v1/products`

**Headers:**

```
Authorization: Bearer {token}
```

**Request Body:**

```json
{
    "name": "Laptop ASUS ROG",
    "price": 15000000
}
```

**Success Response (201):**

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "id": "8c3d2e1f-0a9b-8c7d-6e5f-4a3b2c1d0e9f",
        "name": "Laptop ASUS ROG",
        "price": 15000000,
        "owner_id": "9d4f5e6a-7b8c-9d0e-1f2a-3b4c5d6e7f8g",
        "created_at": "2024-01-15T11:00:00Z"
    }
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Validation Error",
    "errors": {
        "price": ["The price must be greater than 0."]
    }
}
```

### TC4 - Get Products List
**GET** `/api/v1/products`

**Query Parameters:**

- `page` (integer, default: 1)
- `limit` (integer, default: 10, max: 100)
- `search` (string, optional) - search by product name
- `sort_by` (string, optional) - name, price, created_at
- `sort_order` (string, optional) - asc, desc

**Example Request:**

```
GET /api/v1/products?page=1&limit=10&search=laptop&sort_by=price&sort_order=desc
```

**Success Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": "8c3d2e1f-0a9b-8c7d-6e5f-4a3b2c1d0e9f",
            "name": "Laptop ASUS ROG",
            "price": 15000000,
            "owner_id": "9d4f5e6a-7b8c-9d0e-1f2a-3b4c5d6e7f8g",
            "created_at": "2024-01-15T11:00:00Z"
        }
    ],
    "pagination": {
        "total": 25,
        "current_page": 1,
        "total_pages": 3,
        "per_page": 10
    }
}
```

### TC5 - Get Product by ID
**GET** `/api/v1/products/{id}`

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "id": "8c3d2e1f-0a9b-8c7d-6e5f-4a3b2c1d0e9f",
        "name": "Laptop ASUS ROG",
        "price": 15000000,
        "owner_id": "9d4f5e6a-7b8c-9d0e-1f2a-3b4c5d6e7f8g",
        "created_at": "2024-01-15T11:00:00Z"
    }
}
```

**Error Response (404):**

```json
{
    "success": false,
    "message": "Product not found"
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Invalid product ID format"
}
```

---

### TC6 - Update Product
**PATCH** `/api/v1/products/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Request Body (partial update):**

```json
{
    "price": 14500000
}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Product updated successfully",
    "data": {
        "id": "8c3d2e1f-0a9b-8c7d-6e5f-4a3b2c1d0e9f",
        "name": "Laptop ASUS ROG",
        "price": 14500000,
        "owner_id": "9d4f5e6a-7b8c-9d0e-1f2a-3b4c5d6e7f8g",
        "updated_at": "2024-01-15T12:00:00Z"
    }
}
```

**Error Response (403):**

```json
{
    "success": false,
    "message": "Forbidden: You can only update your own products"
}
```

### TC7 - Delete Product
**DELETE** `/api/v1/products/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Success Response (200):**

```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

**Error Response (403):**

```json
{
    "success": false,
    "message": "Forbidden: You can only delete your own products"
}
```

**Error Response (404):**

```json
{
    "success": false,
    "message": "Product not found"
}
```

---

### Health Check
**GET** `/health`

**Success Response (200):**

```json
{
    "status": "ok",
    "timestamp": "2024-01-15T12:30:00Z"
}
```

## Menjalankan Unit Tests
### Menjalankan Semua Test
```bash
php artisan test
```

### Menjalankan Test Per Test Case
**TC1 - Register Test:**
```bash
php artisan test tests/Feature/Auth/RegisterTest.php
```

**TC2 - Login Test:**
```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

**TC3 - Create Product Test:**
```bash
php artisan test tests/Feature/Product/CreateProductTest.php
```

**TC4 - Get Products List Test:**
```bash
php artisan test tests/Feature/Product/ProductIndexTest.php
```

**TC5 - Get Product by ID Test:**
```bash
php artisan test tests/Feature/Product/ProductShowTest.php
```

**TC6 - Update Product Test:**
```bash
php artisan test tests/Feature/Product/UpdateProductTest.php
```

**TC7 - Delete Product & Health Check Test:**
```bash
php artisan test tests/Feature/Product/DeleteProductTest.php
php artisan test tests/Feature/HealthCheckTest.php
```

## Technical Reasoning
### 1. Pemilihan Laravel sebagai Framework
Laravel dipilih karena:

- **Ecosystem yang mature**: Built-in support untuk authentication, validation, testing
- **Eloquent ORM**: Memudahkan implementasi soft delete dan relasi antar tabel
- **Middleware system**: Ideal untuk JWT authentication dan authorization checks
- **PHPUnit integration**: Memudahkan pembuatan unit test yang comprehensive

### 2. Implementasi JWT Authentication
saya menggunakan `php-open-source-saver/jwt-auth` untuk:

- **Stateless authentication**: Sesuai dengan prinsip REST API
- **Payload customization**: Menyimpan `user_id` dan `exp` di token
- **Token expiration**: Keamanan dengan auto-expire 1 jam
- **Refresh token support**: User dapat memperpanjang sesi tanpa re-login

### 3. Penggunaan UUID v7 sebagai Primary Key
alasan menggunakan **UUID v7** supaya id tidak menjadi auto-increment atau memiliki UUID v4 karena:

- **Mencegah resource enumeration** – ID tidak mudah ditebak seperti integer.
- **Time-ordered** – UUID v7 menyematkan timestamp, sehingga indeks B-tree pada PostgreSQL lebih optimal dibanding UUID v4 yang acak.
- **Distributed systems** – Unik secara global tanpa koordinasi pusat.

### 4. BOLA (Broken Object Level Authorization) Prevention
Implementasi authorization check di TC6 dan TC7:

```php
if ($product->owner_id !== $authenticatedUser->id) {
    return response()->json(['message' => 'Forbidden'], 403);
}
```

Memastikan user hanya bisa update/delete produk miliknya sendiri.

### 5. Soft Delete Implementation
Menggunakan Laravel's `SoftDeletes` trait untuk:

- **Data recovery**: Produk yang dihapus bisa di-restore jika diperlukan
- **Audit trail**: Menyimpan history untuk keperluan logging
- **Query performance**: `GET /products` secara otomatis exclude produk yang sudah dihapus

### 6. Password Security
- **Bcrypt hashing**: Laravel default, cost factor 10 (2^10 iterations)
- **Never return password**: Menggunakan `$hidden` property di User model
- **Validation**: Minimal 6 karakter (sesuai TC1 requirement)

### 7. Pagination & Performance Optimization
- **Limit maksimal 100**: Mencegah memory exhaustion dari query terlalu besar
- **Eager loading**: Menggunakan `with()` untuk mencegah N+1 query problem
- **Index pada kolom**: `email` (unique index), `owner_id` (foreign key index)

### 8. Pagination dengan Offset & Batas Keamanan
Meskipun cursor-based pagination lebih efisien untuk dataset sangat besar, saya memilih **offset pagination** dengan batas `limit` maksimal 100 karena:

- Total data produk dalam lingkup test case ini terbatas (< 10.000).
- Offset pagination lebih sederhana dan kompatibel dengan berbagai frontend.
- Batas maksimal 100 mencegah _memory exhaustion_ akibat query terlalu besar.

### 9. Quality Assurance dengan Feature Testing
- Menggunakan **PHPUnit Feature Testing** untuk mensimulasikan request HTTP nyata.
- Setiap endpoint diuji mencakup:
    - Skenario sukses (200/201)
    - Validasi error (422) untuk input salah
    - Otorisasi (401 tanpa token, 403 untuk non‑owner)
    - Not found (404)
    - Token expired (401)
- Total **45 test** dengan **229 assertions**, semuanya passing.

### 10. Health Check sebagai Readiness Probe
Endpoint `GET /health` tanpa autentikasi berfungsi untuk memverifikasi bahwa aplikasi dan koneksi database PostgreSQL dalam kondisi siap menerima trafik

## Kontak
- Nama: Naufal Bagaskara
- Email: naufal.bagaskara-2024@vokasi.unair.ac.id  
- LinkedIn: [linkedin.com/in/naufalbagaskara](https://linkedin.com/in/naufalbagaskara)  
- GitHub: [github.com/naufalbagaskara](https://github.com/naufalbagaskara)
