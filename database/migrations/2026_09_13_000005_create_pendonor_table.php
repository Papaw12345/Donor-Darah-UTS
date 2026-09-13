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
        Schema::create('pendonor', function (Blueprint $table) {
            $table->bigInteger('id_pendonor', autoIncrement: true);
            $table->bigInteger('id_akun');
            $table->integer('id_golongan_darah')->nullable();
            $table->string('nik', 20);
            $table->string('nomor_donor', 50)->nullable();
            $table->string('nama_lengkap', 150);
            $table->enum('jenis_kelamin', ['LAKI_LAKI', 'PEREMPUAN']);
            $table->date('tanggal_lahir');
            $table->string('tempat_lahir', 100);
            $table->text('alamat');
            $table->string('nomor_telepon', 20);
            $table->string('pekerjaan', 100)->nullable();
            $table->text('alamat_kantor')->nullable();

            $table->unique('id_akun');
            $table->unique('nik');
            $table->unique('nomor_donor');

            $table->foreign('id_akun')->references('id_akun')->on('akun');
            $table->foreign('id_golongan_darah')->references('id_golongan_darah')->on('golongan_darah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendonor');
    }
};
