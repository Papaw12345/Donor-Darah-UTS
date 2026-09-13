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
        Schema::create('penyumbangan', function (Blueprint $table) {
            $table->bigInteger('id_penyumbangan', autoIncrement: true);
            $table->bigInteger('id_seleksi');
            $table->bigInteger('id_petugas_pencatat');
            $table->dateTime('waktu_pengambilan');
            $table->smallInteger('volume_ml')->nullable();
            $table->enum('hasil_penyumbangan', ['BERHASIL', 'GAGAL']);
            $table->text('alasan_gagal')->nullable();

            $table->unique('id_seleksi');

            $table->foreign('id_seleksi')->references('id_seleksi')->on('seleksi_donor');
            $table->foreign('id_petugas_pencatat')->references('id_petugas')->on('petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyumbangan');
    }
};
