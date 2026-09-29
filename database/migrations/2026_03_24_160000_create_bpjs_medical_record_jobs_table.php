<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_medical_record_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('encounter_id')->nullable()->index();
            $table->string('patient_id')->nullable()->index();
            $table->string('sep_no', 50)->index();
            $table->string('jns_pelayanan', 5);
            $table->string('bulan', 2);
            $table->string('tahun', 4);
            $table->string('status', 20)->default('draft')->index();
            $table->text('last_error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_medical_record_jobs');
    }
};
