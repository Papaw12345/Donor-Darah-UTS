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
        Schema::create('pertanyaan_kuesioner', function (Blueprint $table) {
            $table->bigInteger('id_pertanyaan', autoIncrement: true);
            $table->text('teks_pertanyaan');
            $table->string('kategori', 100)->nullable();
            $table->enum('jenis_jawaban', ['YA_TIDAK', 'TEKS']);
            $table->integer('urutan');
            $table->boolean('status_aktif')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_kuesioner');
    }
};
