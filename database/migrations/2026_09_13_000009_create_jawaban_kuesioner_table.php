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
        Schema::create('jawaban_kuesioner', function (Blueprint $table) {
            $table->bigInteger('id_jawaban', autoIncrement: true);
            $table->bigInteger('id_kuesioner');
            $table->bigInteger('id_pertanyaan');
            $table->text('jawaban');

            $table->unique(['id_kuesioner', 'id_pertanyaan']);

            $table->foreign('id_kuesioner')->references('id_kuesioner')->on('kuesioner_pradonasi');
            $table->foreign('id_pertanyaan')->references('id_pertanyaan')->on('pertanyaan_kuesioner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jawaban_kuesioner');
    }
};
