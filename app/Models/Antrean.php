<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrean extends Model
{
    use HasFactory;

    protected $table = 'antreans';

    protected $fillable = [
        'kode_booking',
        'jenispasien',
        'nomorkartu',
        'nik',
        'nama_pasien',
        'nohp',
        'kodepoli',
        'namapoli',
        'kodedokter',
        'namadokter',
        'pasienbaru',
        'norm',
        'tanggalperiksa',
        'jampraktek',
        'jeniskunjungan',
        'nomorreferensi',
        'nomorantrean',
        'angkaantrean',
        'estimasidilayani',
        'sisakuotajkn',
        'kuotajkn',
        'sisakuotanonjkn',
        'kuotanonjkn',
        'status',
        'nomor_sep',
        'sumber',
        'keterangan_batal',
        'task1_waktu',
        'task2_waktu',
        'task3_waktu',
        'task4_waktu',
        'task5_waktu',
        'task6_waktu',
        'task7_waktu',
    ];

    protected $casts = [
        'pasienbaru' => 'boolean',
        'angkaantrean' => 'integer',
        'jeniskunjungan' => 'integer',
        'estimasidilayani' => 'integer',
        'sisakuotajkn' => 'integer',
        'kuotajkn' => 'integer',
        'sisakuotanonjkn' => 'integer',
        'kuotanonjkn' => 'integer',
        'task1_waktu' => 'integer',
        'task2_waktu' => 'integer',
        'task3_waktu' => 'integer',
        'task4_waktu' => 'integer',
        'task5_waktu' => 'integer',
        'task6_waktu' => 'integer',
        'task7_waktu' => 'integer',
    ];
}
