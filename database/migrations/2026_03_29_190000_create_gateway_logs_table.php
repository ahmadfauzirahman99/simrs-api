<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10)->default('GET');
            $table->string('service', 30)->default('BPJS'); // BPJS, SATUSEHAT, GATEWAY
            $table->integer('status_code')->default(200);
            $table->integer('response_time_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_summary')->nullable();
            $table->boolean('is_mock')->default(false);
            $table->timestamps();

            $table->index(['created_at', 'status_code']);
            $table->index(['user_id', 'created_at']);
            $table->index('endpoint');
            $table->index('service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_logs');
    }
};
