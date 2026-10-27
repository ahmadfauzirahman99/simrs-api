<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antreans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking')->unique();
            $table->string('jenispasien', 20)->default('JKN'); // JKN / NON JKN
            $table->string('nomorkartu', 30)->nullable()->index();
            $table->string('nik', 20)->nullable()->index();
            $table->string('nama_pasien')->nullable();
            $table->string('nohp', 20)->nullable();
            $table->string('kodepoli', 20)->index();
            $table->string('namapoli')->nullable();
            $table->string('kodedokter', 30)->index();
            $table->string('namadokter')->nullable();
            $table->boolean('pasienbaru')->default(false);
            $table->string('norm', 30)->nullable();
            $table->date('tanggalperiksa')->index();
            $table->string('jampraktek', 30)->nullable();
            $table->unsignedSmallInteger('jeniskunjungan')->default(1); // 1: FKTP, 2: Internal, 3: Kontrol, 4: Antar Faskes
            $table->string('nomorreferensi', 50)->nullable();
            $table->string('nomorantrean', 20);
            $table->unsignedInteger('angkaantrean')->default(1);
            $table->bigInteger('estimasidilayani')->nullable(); // ms
            $table->unsignedInteger('sisakuotajkn')->default(0);
            $table->unsignedInteger('kuotajkn')->default(0);
            $table->unsignedInteger('sisakuotanonjkn')->default(0);
            $table->unsignedInteger('kuotanonjkn')->default(0);
            $table->string('status', 30)->default('Menunggu'); // Menunggu, Check-In, Dipanggil Poli, Selesai, Batal
            $table->string('nomor_sep', 50)->nullable();
            $table->string('sumber', 20)->default('APM'); // APM, Mobile JKN, Loket
            $table->string('keterangan_batal')->nullable();

            // BPJS Antrol Task ID timestamps (milliseconds)
            $table->bigInteger('task1_waktu')->nullable();
            $table->bigInteger('task2_waktu')->nullable();
            $table->bigInteger('task3_waktu')->nullable();
            $table->bigInteger('task4_waktu')->nullable();
            $table->bigInteger('task5_waktu')->nullable();
            $table->bigInteger('task6_waktu')->nullable();
            $table->bigInteger('task7_waktu')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antreans');
    }
};
