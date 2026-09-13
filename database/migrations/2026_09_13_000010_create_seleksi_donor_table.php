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
        Schema::create('seleksi_donor', function (Blueprint $table) {
            $table->bigInteger('id_seleksi', autoIncrement: true);
            $table->bigInteger('id_pemesanan');
            $table->bigInteger('id_petugas');
            $table->dateTime('waktu_seleksi');
            $table->decimal('berat_badan', 5, 2);
            $table->smallInteger('tekanan_sistolik');
            $table->smallInteger('tekanan_diastolik');
            $table->smallInteger('denyut_nadi');
            $table->decimal('suhu_tubuh', 4, 1);
            $table->decimal('kadar_hb', 4, 1);
            $table->text('hasil_pemeriksaan_kesehatan')->nullable();
            $table->enum('keputusan_seleksi', ['LAYAK', 'DITUNDA', 'DITOLAK']);
            $table->text('alasan_keputusan')->nullable();

            $table->unique('id_pemesanan');

            $table->foreign('id_pemesanan')->references('id_pemesanan')->on('pemesanan_donor');
            $table->foreign('id_petugas')->references('id_petugas')->on('petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seleksi_donor');
    }
};
