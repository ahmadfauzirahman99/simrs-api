<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpjs\Rujukan\DetailRujukanRequest;
use App\Http\Requests\Bpjs\Rujukan\JumlahSepRujukanRequest;
use App\Http\Requests\Bpjs\Rujukan\ListRujukanRequest;
use App\Services\Bpjs\BridgingV3;
use App\Support\Api\ExternalIntegrationResponder;
use Illuminate\Http\JsonResponse;

class BpjsRujukanController extends Controller
{
    public function list(ListRujukanRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryMultiRujukan(
            $request->nomor,
            (int) ($request->tipe ?? 1)
        ));
    }

    public function detail(DetailRujukanRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetRujukan(
            $request->no_rujukan,
            (int) ($request->faskes ?? 1)
        ));
    }

    public function jumlahSep(JumlahSepRujukanRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->getDataJumlahSepRujukan(
            $request->no_rujukan,
            $request->jenis ?? '1'
        ));
    }
}
