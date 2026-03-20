<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpjs\Referensi\DokterDpjpRequest;
use App\Http\Requests\Bpjs\Referensi\FaskesRequest;
use App\Http\Requests\Bpjs\Referensi\KabupatenRequest;
use App\Http\Requests\Bpjs\Referensi\KecamatanRequest;
use App\Http\Requests\Bpjs\Referensi\KeywordRequest;
use App\Services\Bpjs\BridgingV3;
use App\Support\Api\ExternalIntegrationResponder;
use Illuminate\Http\JsonResponse;

class BpjsReferensiController extends Controller
{
    public function poli(KeywordRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetListPoli($request->keyword));
    }

    public function diagnosa(KeywordRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetListDiagnosa($request->keyword));
    }

    public function faskes(FaskesRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetListFaskes(
            $request->keyword,
            (int) ($request->tipe ?? 1)
        ));
    }

    public function dokterDpjp(DokterDpjpRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetDokterDpjp(
            $request->kode,
            $request->jenis,
            $request->tgl
        ));
    }

    public function provinsi(BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetProvinsi());
    }

    public function kabupaten(KabupatenRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetKabupaten($request->prov));
    }

    public function kecamatan(KecamatanRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetKecamatan($request->kab));
    }

    public function prosedur(KeywordRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->getProsedur($request->keyword));
    }
}
