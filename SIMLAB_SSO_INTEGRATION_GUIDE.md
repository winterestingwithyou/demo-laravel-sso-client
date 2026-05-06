# Panduan Pembuatan Aplikasi Client SIMLAB (Integrasi SSO FASILKOM UNSRI)

Terakhir diperbarui: 2026-05-06

Dokumen ini adalah panduan komprehensif untuk membangun aplikasi demo SIMLAB dari awal (Laravel Kosongan) yang sepenuhnya terintegrasi dengan SSO FASILKOM UNSRI. Panduan ini mengadopsi standar arsitektur dari RPS dan SIKP, dengan fokus pada pembuatan aplikasi _client_ baru tanpa beban kode _legacy_ (tanpa fitur auth lokal).

---

## 1) Tujuan

- Membangun aplikasi demo SIMLAB menggunakan kerangka kerja Laravel dasar (tanpa starter kit seperti Breeze/Fortify).
- Menerapkan desain antarmuka modern menggunakan **Tailwind CSS**.
- Menjadikan SSO FASILKOM UNSRI sebagai satu-satunya _Source of Truth_ (sumber kebenaran) untuk autentikasi dan otorisasi.
- Menggunakan pola _Authorization Code Flow with PKCE_ yang aman.
- Menyimpan sesi pengguna di sisi server (via tabel `auth_sessions`), tanpa menggunakan tabel `users` tradisional berserta kolom `password`.

---

## 2) Stack dan Konteks

- **Backend/Framework:** Laravel (Kosongan) + PHP.
- **Frontend:** Laravel Blade + Tailwind CSS + Vite.
- **Database:** PostgreSQL / MySQL (sesuai ketersediaan).
- **SSO Integration:** Custom OAuth2 Client di Controller (bisa memanfaatkan library HTTP `Http::` bawaan Laravel atau Laravel Socialite dengan custom provider).

---

## 3) Kriteria Wajib (Hard Constraints)

1. **Aplikasi SIMLAB tidak boleh mengelola autentikasi lokal.** Form input _email_ dan _password_ dilarang keras ada di aplikasi ini.
2. **Tidak ada tabel identitas lokal.** Migrasi bawaan Laravel (`users`, `password_reset_tokens`) wajib dihapus sebelum `php artisan migrate` dijalankan pertama kali.
3. **Session Server-Side:** Sebagai ganti tabel `users`, gunakan tabel `auth_sessions` untuk melacak siapa yang sedang login.
4. **Pertukaran Token (Token Exchange):** Wajib dilakukan di sisi backend Laravel (pada route `/callback`), bukan di frontend.
5. **PKCE Wajib Aktif:** _Code challenge_ (S256) dan _code verifier_ wajib disertakan.
6. **Role & Permission:** Hak akses untuk melihat Dashboard sepenuhnya diambil dari _claims_ access token SSO, bukan di-_hardcode_ di database SIMLAB.

---

## 4) Konfigurasi Environment Minimum (.env)

Tambahkan variabel berikut pada file `.env` Laravel:

```env
# Konfigurasi SSO FASILKOM UNSRI
SSO_BASE_URL=https://sso.fasilkom.unsri.ac.id
SSO_ISSUER=sso-unsri
SSO_JWKS_URL=https://sso.fasilkom.unsri.ac.id/.well-known/jwks.json
SSO_CLIENT_ID=client-id-simlab-di-sso
SSO_CLIENT_SECRET=client-secret-simlab-di-sso
SSO_REDIRECT_URI=http://localhost:8000/callback

# URL untuk diarahkan jika user ingin mengedit profilnya
SSO_PROFILE_URL=https://sso.fasilkom.unsri.ac.id/profile
```

---

## 5) Arsitektur Target (Flow Autentikasi)

1. User mengakses `/login` di aplikasi SIMLAB. Halaman hanya menampilkan tombol "Login with SSO FASILKOM UNSRI".
2. Saat tombol diklik, aplikasi mengarahkan (_redirect_) ke endpoint `SSO_BASE_URL/oauth/authorize` beserta parameter `client_id`, `redirect_uri`, `response_type=code`, `state`, dan `code_challenge`.
3. User login di SSO (atau langsung dialihkan jika sesi SSO masih aktif).
4. SSO mengalihkan kembali ke `http://localhost:8000/callback` dengan parameter `code` dan `state`.
5. Backend SIMLAB memvalidasi `state` untuk mencegah serangan CSRF.
6. Backend SIMLAB menukar `code` menjadi _Access Token_ dengan memanggil `SSO_BASE_URL/oauth/token` (mengirim `client_secret` dan `code_verifier`).
7. Backend SIMLAB melakukan _decode_ pada JWT Access Token yang didapat untuk mengekstrak data profil user, _roles_, _permissions_, dan _scopes_ (tanpa perlu memanggil endpoint `/oauth/userinfo`).
8. Backend SIMLAB memeriksa _identities_ dari profil tersebut. Jika terdapat lebih dari 1 _identity_ (misal: DOSEN dan KAPRODI), backend menyimpan data profil ke sesi sementara dan mengalihkan user ke halaman `/select-identity`.
9. Setelah user memilih _identity_, backend menyimpan data sesi utuh (termasuk _identities_ yang di-cache) ke tabel `auth_sessions` dan membuat sesi login Laravel agar tidak melakukan _hit_ `/profile` ke SSO secara berulang.
10. User dialihkan ke halaman `/dashboard`.

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
4. Tambahkan _directives_ Tailwind di `resources/css/app.css`.
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
            $table->json('identities_cache')->nullable(); // Cache profil/identitas dari SSO
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
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
Route::get('/select-identity', [SsoAuthController::class, 'selectIdentityView'])->name('sso.select_identity_view');
Route::post('/select-identity', [SsoAuthController::class, 'selectIdentitySubmit'])->name('sso.select_identity_submit');
Route::post('/logout', [SsoAuthController::class, 'logout'])->name('logout');

// Halaman Protected (Butuh Custom Middleware)
Route::middleware(['sso.auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Endpoint API internal untuk frontend SIMLAB jika dibutuhkan
    Route::get('/api/auth/me', [SsoAuthController::class, 'me']);
});
```

### 6.4 Pembuatan SSO Controller

Buat `SsoAuthController` untuk menangani logika PKCE dan pertukaran token.

_Tugas Controller:_

- `redirect()`: Generate string acak untuk `state` dan `code_verifier`. Simpan di session Laravel. Hasilkan `code_challenge` (SHA-256 dari `code_verifier`). Lakukan `redirect()` ke URL authorize SSO.
- `callback()`:
    - Verifikasi `state` dari request sama dengan yang ada di session.
    - Lakukan HTTP POST ke `SSO_BASE_URL/oauth/token` dengan payload: `grant_type=authorization_code`, `client_id`, `client_secret`, `redirect_uri`, `code`, dan `code_verifier`.
    - Jika berhasil dapat _Access Token_, lakukan _decode_ pada token tersebut (JWT) untuk mengekstrak data _claims_ yang berisi profil, _roles_, _permissions_, _scopes_, dan daftar _identities_.
    - Cek jumlah _identities_ dari hasil _decode_ token.
    - **Jika identity > 1:** Simpan data _profil_, _access token_, dan _refresh token_ ke Laravel `Session` sementara, lalu _redirect_ ke `route('sso.select_identity_view')`.
    - **Jika identity = 1:** Langsung set `active_identity`, simpan _cache_ identitas, masukkan data ke `auth_sessions`, set `Session::put('auth_session_id', $session_id)`, dan redirect ke `/dashboard`.
- `selectIdentitySubmit()`: Menerima _identity_ pilihan user. Pindahkan data dari sesi sementara ke tabel `auth_sessions` (termasuk menyimpan _identities_cache_ agar tidak perlu me-request ulang ke SSO saat butuh profil). Hapus sesi sementara, set auth session aktif, lalu redirect ke `/dashboard`.
- `logout()`:
    - Lakukan HTTP POST ke `SSO_BASE_URL/oauth/revoke` (mengirim `token` berupa access_token/refresh_token dan `client_secret`) untuk melakukan _revoke_ token secara remote di sisi SSO.
    - Hapus data di `auth_sessions` dan jalankan `Session::flush()`.
    - _Redirect_ ke halaman login.
- `me()`: Endpoint internal SIKP untuk mengembalikan data session/profile user saat ini dalam format JSON (berguna jika bagian frontend SIMLAB menggunakan komponen reaktif/fetch API mandiri).

### 6.5 Pembuatan Custom Middleware (`sso.auth`)

Karena kita tidak menggunakan tabel `users` dan model `User` bawaan Laravel untuk guard `web`, kita harus membuat custom middleware.

```bash
php artisan make:middleware SsoAuthMiddleware
```

_Logika Middleware:_

- Cek apakah `Session::has('auth_user_id')`.
- Jika ya, ambil record dari tabel `auth_sessions` berdasarkan ID tersebut.
- Simpan instance object session ini ke _Request_ agar bisa diakses di Blade via `$request->ssoUser->name`.
- Jika tidak ada session, redirect ke `route('login')`.

---

## 7) Antarmuka Frontend (Blade + Tailwind)

Aplikasi membutuhkan minimal 2 _views_:

1. **`resources/views/auth/login.blade.php`**
    - Layar penuh (_h-screen_), menggunakan sentuhan warna gradien atau estetika modern.
    - Tidak ada input form.
    - Satu tombol besar (CTA): **"Login via SSO FASILKOM UNSRI"** yang mengarah ke `route('sso.redirect')`.

2. **`resources/views/auth/select-identity.blade.php`**
    - Halaman ini dirender jika user memiliki lebih dari satu identitas (misalnya MAHASISWA dan MENTOR).
    - Menampilkan daftar identitas dalam bentuk _cards_ atau _radio button_ yang diekstrak dari _session_ sementara.
    - Tombol _Submit_ yang mengirim form POST ke `route('sso.select_identity_submit')`.

3. **`resources/views/dashboard.blade.php`**
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
- [ ] Proses _Logout_ menghancurkan sesi di Laravel dengan bersih.
- [ ] Tampilan menggunakan Tailwind CSS dengan estetika yang rapi (layak untuk demo ke klien SIMLAB).

---

_Dokumen ini merupakan panduan spesifik integrasi ke aplikasi baru (greenfield) merujuk pada standar SIKP dan RPS._
