<?php

namespace App\Http\Controllers\Api\Antrol;

use App\Http\Controllers\Controller;

class AntreanRsController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'List data Antrean RS',
            'data' => [],
        ]);
    }

    public function poli()
    {
        return response()->json([
            'success' => true,
            'message' => 'List data Poli',
            'data' => [],
        ]);
    }

    public function dokter()
    {
        return response()->json([
            'success' => true,
            'message' => 'List data Dokter',
            'data' => [],
        ]);
    }

    public function jadwalDokter()
    {
        return response()->json([
            'success' => true,
            'message' => 'List data Jadwal Dokter',
            'data' => [],
        ]);
    }
}
