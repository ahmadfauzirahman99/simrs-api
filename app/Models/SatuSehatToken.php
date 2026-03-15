<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuSehatToken extends Model
{
    protected $table = 'satusehat_tokens';

    protected $fillable = [
        'environment',
        'token',
        'created_at_token',
        'expired',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at_token' => 'datetime',
        'expired' => 'integer',
    ];
}
