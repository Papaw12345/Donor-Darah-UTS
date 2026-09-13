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
        Schema::create('kuesioner_pradonasi', function (Blueprint $table) {
            $table->bigInteger('id_kuesioner', autoIncrement: true);
            $table->bigInteger('id_pemesanan');
            $table->dateTime('waktu_pengisian');

            $table->unique('id_pemesanan');

            $table->foreign('id_pemesanan')->references('id_pemesanan')->on('pemesanan_donor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kuesioner_pradonasi');
    }
};
