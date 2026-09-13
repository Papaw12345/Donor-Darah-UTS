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
        Schema::create('petugas', function (Blueprint $table) {
            $table->bigInteger('id_petugas', autoIncrement: true);
            $table->bigInteger('id_akun');
            $table->string('nomor_petugas', 50);
            $table->string('nama_petugas', 150);

            $table->unique('id_akun');
            $table->unique('nomor_petugas');

            $table->foreign('id_akun')->references('id_akun')->on('akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas');
    }
};
