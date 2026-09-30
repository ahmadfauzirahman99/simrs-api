<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    // Bukti transfer disimpan di disk privat; hanya bisa dibuka lewat route payment-proofs.image.
    public const DISK = 'local';
    public const DIRECTORY = 'payment_proofs';

    protected $fillable = [
        'user_id',
        'plan_name',
        'amount',
        'proof_image',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'bank_source',
        'notes',
        'status',
        'admin_notes',
        'uploaded_by_role',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'file_size_bytes' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    protected $appends = [
        'proof_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getProofUrlAttribute(): ?string
    {
        if (!$this->proof_image) {
            return null;
        }

        return route('payment-proofs.image', $this);
    }
}
