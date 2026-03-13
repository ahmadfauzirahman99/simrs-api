<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Bpjs\BridgingV3;
use Illuminate\Http\Request;

class BpjsSukonController extends Controller
{

    public function insertSuratKontrol(Request $request, BridgingV3 $bpjs)
    {
        $validated = $request->validate([
            'request' => ['required', 'array'],
            'request.noSEP' => ['required', 'string'],
            'request.kodeDokter' => ['required', 'string'],
            'request.poliKontrol' => ['required', 'string'],
            'request.tglRencanaKontrol' => ['required', 'date'],
            'request.user' => ['required', 'string'],
        ]);


        $payload = $validated['request'];

        $ok = $bpjs->insertSuratKontrol($payload)->exec();

        if (!$ok) {
            return ApiResponse::error(
                $bpjs->getError()['message'],
                (int) $bpjs->getError()['code']
            );
        }

        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => $bpjs->getResponse(),
        ], 200);
    }

    public function updateSuratKontrol(Request $request, BridgingV3 $bpjs)
    {
        $validated = $request->validate([
            'request' => ['required', 'array'],
            'request.noSuratKontrol' => ['required', 'string'],
            'request.noSEP' => ['required', 'string'],
            'request.kodeDokter' => ['required', 'string'],
            'request.poliKontrol' => ['required', 'string'],
            'request.tglRencanaKontrol' => ['required', 'date'],
            'request.user' => ['required', 'string'],
        ]);


        $payload = $validated['request'];

        $ok = $bpjs->updateSuratKontrol($payload)->exec();

        if (!$ok) {
            return ApiResponse::error(
                $bpjs->getError()['message'],
                (int) $bpjs->getError()['code']
            );
        }

        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => $bpjs->getResponse(),
        ], 200);
    }


    public function insertSpri(Request $request, BridgingV3 $bpjs)
    {
        $validated = $request->validate([
            'request' => ['required', 'array'],
            'request.noKartu' => ['required', 'string'],
            'request.kodeDokter' => ['required', 'string'],
            'request.poliKontrol' => ['required', 'string'],
            'request.tglRencanaKontrol' => ['required', 'date'],
            'request.user' => ['required', 'string'],
        ]);

        $payload = $validated['request'];

        $ok = $bpjs->insertSpri($payload)->exec();

        if (!$ok) {
            return ApiResponse::error(
                $bpjs->getError()['message'],
                (int) $bpjs->getError()['code']
            );
        }

        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => $bpjs->getResponse(),
        ], 200);
    }

    public function updateSpri(Request $request, BridgingV3 $bpjs)
    {
        $validated = $request->validate([
            'request' => ['required', 'array'],
            'request.noSPRI' => ['required', 'string'],
            'request.kodeDokter' => ['required', 'string'],
            'request.poliKontrol' => ['required', 'string'],
            'request.tglRencanaKontrol' => ['required', 'date'],
            'request.user' => ['required', 'string'],
        ]);

        $payload = $validated['request'];

        $ok = $bpjs->updateSpri($payload)->exec();

        if (!$ok) {
            return ApiResponse::error(
                $bpjs->getError()['message'],
                (int) $bpjs->getError()['code']
            );
        }

        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => $bpjs->getResponse(),
        ], 200);
    }
}
