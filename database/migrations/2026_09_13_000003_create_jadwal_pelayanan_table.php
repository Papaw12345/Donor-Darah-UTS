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
        Schema::create('jadwal_pelayanan', function (Blueprint $table) {
            $table->bigInteger('id_jadwal', autoIncrement: true);
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('kapasitas');
            $table->enum('status_jadwal', ['DIBUKA', 'DITUTUP', 'DIBATALKAN'])->default('DIBUKA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelayanan');
    }
};
