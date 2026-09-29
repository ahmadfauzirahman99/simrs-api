<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_medical_record_payloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')
                ->constrained('bpjs_medical_record_jobs')
                ->cascadeOnDelete();
            $table->longText('bundle_json')->nullable();
            $table->longText('normalized_bundle_json')->nullable();
            $table->longText('gzip_payload')->nullable();
            $table->longText('encrypted_payload')->nullable();
            $table->longText('request_json')->nullable();
            $table->longText('response_json')->nullable();
            $table->string('bpjs_response_code', 20)->nullable()->index();
            $table->text('bpjs_response_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_medical_record_payloads');
    }
};
