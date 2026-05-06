<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SsoAuthController;

// Halaman Public
Route::get('/', function () {
    return redirect()->route('login');
});

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
