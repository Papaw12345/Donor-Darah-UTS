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
        Schema::create('akun', function (Blueprint $table) {
            $table->bigInteger('id_akun', autoIncrement: true);
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->enum('peran', ['PENDONOR', 'PETUGAS', 'ADMIN']);
            $table->enum('status_akun', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun');
    }
};
