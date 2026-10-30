<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('gateway_logs')) {
            // Log lama tersimpan dalam UTC (sebelum APP_TIMEZONE disetel ke Asia/Jakarta).
            // Konversikan created_at & updated_at lama dari UTC ke WIB (+7 jam).
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement("UPDATE gateway_logs SET created_at = datetime(created_at, '+7 hours'), updated_at = datetime(updated_at, '+7 hours') WHERE created_at IS NOT NULL");
            } else {
                DB::statement("UPDATE gateway_logs SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR) WHERE created_at IS NOT NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('gateway_logs')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement("UPDATE gateway_logs SET created_at = datetime(created_at, '-7 hours'), updated_at = datetime(updated_at, '-7 hours') WHERE created_at IS NOT NULL");
            } else {
                DB::statement("UPDATE gateway_logs SET created_at = DATE_SUB(created_at, INTERVAL 7 HOUR), updated_at = DATE_SUB(updated_at, INTERVAL 7 HOUR) WHERE created_at IS NOT NULL");
            }
        }
    }
};
