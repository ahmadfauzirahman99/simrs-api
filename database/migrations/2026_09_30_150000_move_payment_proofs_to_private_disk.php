<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Pindahkan bukti transfer lama dari disk public (bisa diakses via URL /storage)
     * ke disk privat. Path di database tidak berubah karena nama foldernya sama.
     */
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        foreach ($public->files('payment_proofs') as $path) {
            if (! $private->exists($path)) {
                $private->writeStream($path, $public->readStream($path));
            }
            $public->delete($path);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke disk public.
    }
};
