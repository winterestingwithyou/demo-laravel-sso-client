<div align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
  
  <h1 align="center">✨ Demo Client Laravel - SSO FASILKOM UNSRI ✨</h1>

  <p align="center">
    <strong>Aplikasi Referensi Integrasi SSO (Single Sign-On) Modern berbasis OAuth2 PKCE.</strong><br>
    Dibangun dari awal tanpa <em>legacy auth</em> untuk mendemonstrasikan kapabilitas SSO tersentralisasi.
  </p>

  <p align="center">
    <img alt="Laravel" src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
    <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white">
    <img alt="OAuth2" src="https://img.shields.io/badge/OAuth2.0-EB5424?style=for-the-badge&logo=auth0&logoColor=white">
    <img alt="Vite" src="https://img.shields.io/badge/Vite-B73BFE?style=for-the-badge&logo=vite&logoColor=FFD62E">
  </p>
</div>

---

## 🎯 Tentang Proyek

Aplikasi ini adalah **Demo Client** untuk mendemonstrasikan cara mengintegrasikan sistem pihak ketiga (dalam hal ini, aplikasi internal kampus seperti SIMLAB) ke dalam **SSO FASILKOM UNSRI**. 

Tidak seperti aplikasi Laravel pada umumnya, aplikasi ini **TIDAK memiliki fitur login lokal** (tanpa form email/password) dan **TIDAK memiliki tabel `users`**. Seluruh otentikasi, manajemen profil, dan hak akses sepenuhnya dikendalikan oleh *SSO Server* sebagai *Single Source of Truth*.

### ✨ Fitur Unggulan
- 🔒 **Keamanan Tingkat Tinggi**: Menggunakan *Authorization Code Flow with PKCE (Proof Key for Code Exchange)*.
- 🎨 **Estetika Premium**: Antarmuka UI/UX modern bergaya *Glassmorphism* menggunakan **Tailwind CSS v4**.
- 🎭 **Manajemen Multi-Identity**: Menangani pengguna yang memiliki banyak peran (misal: sebagai Mahasiswa sekaligus Mentor) dengan halaman pemilihan identitas yang elegan.
- ⚡ **Stateless Backend (Relatif)**: Menghindari pemanggilan API secara konstan (*over-fetching*) dengan meng-*cache* data profil dan *role* di sesi lokal (`auth_sessions`).
- 🗑️ **Remote Token Revocation**: Saat logout, aplikasi akan mencabut (*revoke*) akses token secara langsung dari server SSO.

---

## 🛠️ Teknologi yang Digunakan

Aplikasi ini dirancang seminimalis dan semodern mungkin:
- **Framework**: [Laravel 13](https://laravel.com/)
- **Frontend Styling**: [Tailwind CSS v4](https://tailwindcss.com/)
- **Bundler**: [Vite](https://vitejs.dev/)
- **Database**: PostgreSQL / SQLite (Untuk tabel *sessions*)
- **Otentikasi**: Custom OAuth2 Client via Laravel HTTP Client

---

## 🚀 Cara Menjalankan Aplikasi Lokal

Ikuti langkah-langkah di bawah ini untuk menjalankan demo klien ini di komputer Anda:

### 1. Kloning Repositori
```bash
git clone <url-repo-ini>
cd demo-client-laravel/demo-app
```

### 2. Instalasi Dependensi
Pastikan Anda telah menginstal PHP dan Composer, serta Node.js untuk aset *frontend*.
```bash
composer install
npm install
```

### 3. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`.
```bash
cp .env.example .env
```
Lalu *generate* kunci aplikasi:
```bash
php artisan key:generate
```
**PENTING**: Buka file `.env` dan atur konfigurasi SSO sesuai dengan kredensial klien Anda yang telah didaftarkan di SSO FASILKOM UNSRI.
```env
SSO_BASE_URL="https://sso-unsri.winterest.workers.dev"
SSO_ISSUER="sso-unsri"
SSO_JWKS_URL="${SSO_BASE_URL}/.well-known/jwks.json"
SSO_CLIENT_ID="<client-id-anda>"
SSO_CLIENT_SECRET="<client-secret-anda>"
SSO_REDIRECT_URI="http://localhost:8000/callback"
SSO_PROFILE_URL="${SSO_BASE_URL}/profile"
```

### 4. Migrasi Database
Aplikasi ini hanya membutuhkan satu tabel yaitu `auth_sessions`.
```bash
php artisan migrate:fresh
```

### 5. Kompilasi Aset Frontend (Tailwind)
```bash
npm run build
# Atau jika sedang tahap pengembangan: npm run dev
```

### 6. Jalankan Server
```bash
php artisan serve
```
Aplikasi kini dapat diakses melalui browser pada `http://localhost:8000`.

---

## 🏗️ Struktur Arsitektur Singkat

- `app/Http/Controllers/SsoAuthController.php`: Jantung utama dari alur SSO (Redirect, Callback pertukaran Token, Ekstraksi Identitas, dan Logout).
- `app/Http/Middleware/SsoAuthMiddleware.php`: Penjaga halaman *protected* (seperti `/dashboard`) untuk memastikan keberadaan sesi *user* di `auth_sessions`.
- `resources/views/auth/`: Berisi antarmuka estetik untuk *Login* dan *Select Identity*.
- `database/migrations/*_create_auth_sessions_table.php`: Skema *database* modern pengganti tabel `users`.

---

<div align="center">
  <p>Dibuat untuk keperluan demonstrasi standar SIKP & RPS terintegrasi SSO Fasilkom Unsri.</p>
</div>
