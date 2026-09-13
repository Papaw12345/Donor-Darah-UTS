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
        Schema::create('ambang_persediaan', function (Blueprint $table) {
            $table->bigInteger('id_ambang', autoIncrement: true);
            $table->integer('id_jenis_komponen');
            $table->integer('id_golongan_darah');
            $table->integer('jumlah_minimum');

            $table->unique(['id_jenis_komponen', 'id_golongan_darah']);

            $table->foreign('id_jenis_komponen')->references('id_jenis_komponen')->on('jenis_komponen_darah');
            $table->foreign('id_golongan_darah')->references('id_golongan_darah')->on('golongan_darah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambang_persediaan');
    }
};
