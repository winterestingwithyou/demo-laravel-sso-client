# Panduan Pembuatan Aplikasi Client SIMLAB (Integrasi SSO FASILKOM UNSRI)

Terakhir diperbarui: 2026-05-06

Dokumen ini adalah panduan komprehensif untuk membangun aplikasi demo SIMLAB dari awal (Laravel Kosongan) yang sepenuhnya terintegrasi dengan SSO FASILKOM UNSRI. Panduan ini mengadopsi standar arsitektur dari RPS dan SIKP, dengan fokus pada pembuatan aplikasi *client* baru tanpa beban kode *legacy* (tanpa fitur auth lokal).

---

## 1) Tujuan

- Membangun aplikasi demo SIMLAB menggunakan kerangka kerja Laravel dasar (tanpa starter kit seperti Breeze/Fortify).
- Menerapkan desain antarmuka modern menggunakan **Tailwind CSS**.
- Menjadikan SSO FASILKOM UNSRI sebagai satu-satunya *Source of Truth* (sumber kebenaran) untuk autentikasi dan otorisasi.
- Menggunakan pola *Authorization Code Flow with PKCE* yang aman.
- Menyimpan sesi pengguna di sisi server (via tabel `auth_sessions`), tanpa menggunakan tabel `users` tradisional berserta kolom `password`.

---

## 2) Stack dan Konteks

- **Backend/Framework:** Laravel (Kosongan) + PHP.
- **Frontend:** Laravel Blade + Tailwind CSS + Vite.
- **Database:** PostgreSQL / MySQL (sesuai ketersediaan).
- **SSO Integration:** Custom OAuth2 Client di Controller (bisa memanfaatkan library HTTP `Http::` bawaan Laravel atau Laravel Socialite dengan custom provider).

---

## 3) Kriteria Wajib (Hard Constraints)

1. **Aplikasi SIMLAB tidak boleh mengelola autentikasi lokal.** Form input *email* dan *password* dilarang keras ada di aplikasi ini.
2. **Tidak ada tabel identitas lokal.** Migrasi bawaan Laravel (`users`, `password_reset_tokens`) wajib dihapus sebelum `php artisan migrate` dijalankan pertama kali.
3. **Session Server-Side:** Sebagai ganti tabel `users`, gunakan tabel `auth_sessions` untuk melacak siapa yang sedang login.
4. **Pertukaran Token (Token Exchange):** Wajib dilakukan di sisi backend Laravel (pada route `/callback`), bukan di frontend.
5. **PKCE Wajib Aktif:** *Code challenge* (S256) dan *code verifier* wajib disertakan.
6. **Role & Permission:** Hak akses untuk melihat Dashboard sepenuhnya diambil dari *claims* access token SSO, bukan di-*hardcode* di database SIMLAB.

---

## 4) Konfigurasi Environment Minimum (.env)

Tambahkan variabel berikut pada file `.env` Laravel:

```env
# Konfigurasi SSO FASILKOM UNSRI
SSO_BASE_URL=https://sso.fasilkom.unsri.ac.id
SSO_ISSUER=https://sso.fasilkom.unsri.ac.id
SSO_CLIENT_ID=client-id-simlab-di-sso
SSO_CLIENT_SECRET=client-secret-simlab-di-sso
SSO_REDIRECT_URI=http://localhost:8000/callback

# URL untuk diarahkan jika user ingin mengedit profilnya
SSO_PROFILE_URL=https://sso.fasilkom.unsri.ac.id/profile
```

---

## 5) Arsitektur Target (Flow Autentikasi)

1. User mengakses `/login` di aplikasi SIMLAB. Halaman hanya menampilkan tombol "Login with SSO FASILKOM UNSRI".
2. Saat tombol diklik, aplikasi mengarahkan (*redirect*) ke endpoint `SSO_BASE_URL/oauth/authorize` beserta parameter `client_id`, `redirect_uri`, `response_type=code`, `state`, dan `code_challenge`.
3. User login di SSO (atau langsung dialihkan jika sesi SSO masih aktif).
4. SSO mengalihkan kembali ke `http://localhost:8000/callback` dengan parameter `code` dan `state`.
5. Backend SIMLAB memvalidasi `state` untuk mencegah serangan CSRF.
6. Backend SIMLAB menukar `code` menjadi *Access Token* dengan memanggil `SSO_BASE_URL/oauth/token` (mengirim `client_secret` dan `code_verifier`).
7. Backend SIMLAB memanggil endpoint `SSO_BASE_URL/api/auth/me` menggunakan Access Token untuk mendapatkan data profil user dan rolenya.
8. Backend menyimpan data tersebut ke tabel `auth_sessions` dan membuat sesi login Laravel.
9. User dialihkan ke halaman `/dashboard`.

---

## 6) Panduan Pembuatan (Step-by-Step)

### 6.1 Setup Awal (Tailwind & Kosongan)

1. Install Laravel: `composer create-project laravel/laravel simlab-demo`
2. Install Tailwind:
   ```bash
   npm install -D tailwindcss postcss autoprefixer
   npx tailwindcss init -p
   ```
3. Konfigurasi `tailwind.config.js` untuk memindai file `.blade.php`.
4. Tambahkan *directives* Tailwind di `resources/css/app.css`.
5. Hapus file migrasi bawaan di `database/migrations/` (`create_users_table`, `create_password_reset_tokens_table`).

### 6.2 Skema Database Target

Buat migrasi tunggal untuk mengelola sesi login SSO. 

```bash
php artisan make:migration create_auth_sessions_table
```

Isi migrasi:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Bisa menggunakan custom ID atau UUID
            $table->string('auth_user_id'); // ID user dari SSO
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('active_identity')->nullable(); // cth: DOSEN / MAHASISWA
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->text('access_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
```

### 6.3 Route Plan

Di `routes/web.php`:

```php
use App\Http\Controllers\SsoAuthController;

// Halaman Public
Route::get('/', function () { return view('welcome'); });

// Flow SSO
Route::get('/login', [SsoAuthController::class, 'loginView'])->name('login');
Route::get('/auth/redirect', [SsoAuthController::class, 'redirect'])->name('sso.redirect');
Route::get('/callback', [SsoAuthController::class, 'callback'])->name('sso.callback');
Route::post('/logout', [SsoAuthController::class, 'logout'])->name('logout');

// Halaman Protected (Butuh Custom Middleware)
Route::middleware(['sso.auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

### 6.4 Pembuatan SSO Controller

Buat `SsoAuthController` untuk menangani logika PKCE dan pertukaran token.

*Tugas Controller:*
- `redirect()`: Generate string acak untuk `state` dan `code_verifier`. Simpan di session Laravel. Hasilkan `code_challenge` (SHA-256 dari `code_verifier`). Lakukan `redirect()` ke URL authorize SSO.
- `callback()`: 
  - Verifikasi `state` dari request sama dengan yang ada di session.
  - Lakukan HTTP POST ke `SSO_BASE_URL/oauth/token` dengan payload: `grant_type=authorization_code`, `client_id`, `client_secret`, `redirect_uri`, `code`, dan `code_verifier`.
  - Jika berhasil dapat *Access Token*, lakukan HTTP GET ke `SSO_BASE_URL/api/auth/me` (dengan header `Authorization: Bearer <token>`).
  - Simpan/Update data dari API `/me` ke tabel `auth_sessions`.
  - Set `Session::put('auth_user_id', $user->id)`.
  - Redirect ke `/dashboard`.
- `logout()`: Hapus data di `auth_sessions` dan jalankan `Session::flush()`. Panggil juga endpoint revoke di SSO jika perlu.

### 6.5 Pembuatan Custom Middleware (`sso.auth`)

Karena kita tidak menggunakan tabel `users` dan model `User` bawaan Laravel untuk guard `web`, kita harus membuat custom middleware.

```bash
php artisan make:middleware SsoAuthMiddleware
```

*Logika Middleware:*
- Cek apakah `Session::has('auth_user_id')`.
- Jika ya, ambil record dari tabel `auth_sessions` berdasarkan ID tersebut.
- Simpan instance object session ini ke *Request* agar bisa diakses di Blade via `$request->ssoUser->name`.
- Jika tidak ada session, redirect ke `route('login')`.

---

## 7) Antarmuka Frontend (Blade + Tailwind)

Aplikasi membutuhkan minimal 2 *views*:

1. **`resources/views/auth/login.blade.php`**
   - Layar penuh (*h-screen*), menggunakan sentuhan warna gradien atau estetika modern.
   - Tidak ada input form.
   - Satu tombol besar (CTA): **"Login via SSO FASILKOM UNSRI"** yang mengarah ke `route('sso.redirect')`.

2. **`resources/views/dashboard.blade.php`**
   - Tampilan Dashboard standar (sidebar, topbar).
   - Menampilkan Nama, Email, dan Role (diambil dari session SSO).
   - Dropdown profile hanya berisi tombol **Logout**. Menu "Edit Profile" diset menjadi link eksternal yang mengarah ke `env('SSO_PROFILE_URL')`.

---

## 8) Done Criteria (Kriteria Selesai)

- [ ] Aplikasi Laravel berhasil dijalankan tanpa error terkait tabel `users` bawaan.
- [ ] Klik "Login" akan memicu flow PKCE dan redirect ke SSO FASILKOM UNSRI.
- [ ] Pertukaran token berhasil di `/callback` dan data profile terekstrak ke tabel `auth_sessions`.
- [ ] User berhasil diarahkan ke `/dashboard` setelah sukses.
- [ ] Halaman `/dashboard` menampilkan nama dan role yang sah dari SSO.
- [ ] Proses *Logout* menghancurkan sesi di Laravel dengan bersih.
- [ ] Tampilan menggunakan Tailwind CSS dengan estetika yang rapi (layak untuk demo ke klien SIMLAB).

---
*Dokumen ini merupakan panduan spesifik integrasi ke aplikasi baru (greenfield) merujuk pada standar SIKP dan RPS.*
