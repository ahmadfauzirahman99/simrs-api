<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satusehat_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('environment', 20)->nullable();
            $table->longText('token');
            $table->dateTime('created_at_token')->nullable();
            $table->integer('expired')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satusehat_tokens');
    }
};
