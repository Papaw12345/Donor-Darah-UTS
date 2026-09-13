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
        Schema::create('golongan_darah', function (Blueprint $table) {
            $table->integer('id_golongan_darah', autoIncrement: true);
            $table->enum('abo', ['A', 'B', 'AB', 'O']);
            $table->enum('rhesus', ['POSITIF', 'NEGATIF']);

            $table->unique(['abo', 'rhesus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('golongan_darah');
    }
};
