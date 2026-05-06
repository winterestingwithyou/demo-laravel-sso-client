<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
            $table->json('identities_cache')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->json('profilemetadata')->nullable(); // Tambahan data profil agar tidak hit API terus
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_sessions');
    }
};
