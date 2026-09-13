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
        Schema::create('pemesanan_donor', function (Blueprint $table) {
            $table->bigInteger('id_pemesanan', autoIncrement: true);
            $table->bigInteger('id_pendonor');
            $table->bigInteger('id_jadwal');
            $table->dateTime('waktu_pemesanan');
            $table->string('kode_checkin', 50)->nullable();
            $table->dateTime('waktu_checkin')->nullable();
            $table->enum('status_pemesanan', ['TERJADWAL', 'CHECK_IN', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'])
                ->default('TERJADWAL');

            $table->unique('kode_checkin');

            $table->foreign('id_pendonor')->references('id_pendonor')->on('pendonor');
            $table->foreign('id_jadwal')->references('id_jadwal')->on('jadwal_pelayanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemesanan_donor');
    }
};
