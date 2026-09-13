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
        Schema::create('unit_komponen_darah', function (Blueprint $table) {
            $table->bigInteger('id_unit', autoIncrement: true);
            $table->string('nomor_unit', 50);
            $table->bigInteger('id_penyumbangan');
            $table->integer('id_jenis_komponen');
            $table->integer('id_golongan_darah');
            $table->bigInteger('id_petugas_pencatat');
            $table->bigInteger('id_petugas_pelulus')->nullable();
            $table->date('tanggal_pembuatan');
            $table->date('tanggal_kedaluwarsa');
            $table->dateTime('waktu_pelulusan')->nullable();
            $table->enum('status_unit', ['MENUNGGU_PELULUSAN', 'TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'])
                ->default('MENUNGGU_PELULUSAN');
            $table->text('catatan_pelulusan')->nullable();
            $table->dateTime('waktu_distribusi')->nullable();

            $table->unique('nomor_unit');

            $table->foreign('id_penyumbangan')->references('id_penyumbangan')->on('penyumbangan');
            $table->foreign('id_jenis_komponen')->references('id_jenis_komponen')->on('jenis_komponen_darah');
            $table->foreign('id_golongan_darah')->references('id_golongan_darah')->on('golongan_darah');
            $table->foreign('id_petugas_pencatat')->references('id_petugas')->on('petugas');
            $table->foreign('id_petugas_pelulus')->references('id_petugas')->on('petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_komponen_darah');
    }
};
