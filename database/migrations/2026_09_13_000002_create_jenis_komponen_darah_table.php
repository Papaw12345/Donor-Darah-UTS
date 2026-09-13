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
        Schema::create('jenis_komponen_darah', function (Blueprint $table) {
            $table->integer('id_jenis_komponen', autoIncrement: true);
            $table->string('kode_komponen', 10)->unique();
            $table->string('nama_komponen', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_komponen_darah');
    }
};
