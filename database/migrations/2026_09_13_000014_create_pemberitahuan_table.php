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
        Schema::create('pemberitahuan', function (Blueprint $table) {
            $table->bigInteger('id_pemberitahuan', autoIncrement: true);
            $table->bigInteger('id_pendonor');
            $table->bigInteger('id_petugas_pengirim');
            $table->text('isi_pesan');
            $table->dateTime('waktu_dibuat');
            $table->dateTime('waktu_dibaca')->nullable();

            $table->foreign('id_pendonor')->references('id_pendonor')->on('pendonor');
            $table->foreign('id_petugas_pengirim')->references('id_petugas')->on('petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemberitahuan');
    }
};
