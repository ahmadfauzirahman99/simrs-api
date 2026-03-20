<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpjs\SuratKontrol\InsertSpriRequest;
use App\Http\Requests\Bpjs\SuratKontrol\InsertSuratKontrolRequest;
use App\Http\Requests\Bpjs\SuratKontrol\ListRencanaKontrolRequest;
use App\Http\Requests\Bpjs\SuratKontrol\ListRencanaKontrolTanggalRequest;
use App\Http\Requests\Bpjs\SuratKontrol\SearchSepSuratKontrolRequest;
use App\Http\Requests\Bpjs\SuratKontrol\SearchSuratKontrolRequest;
use App\Http\Requests\Bpjs\SuratKontrol\UpdateSpriRequest;
use App\Http\Requests\Bpjs\SuratKontrol\UpdateSuratKontrolRequest;
use App\Services\Bpjs\BridgingV3;
use App\Support\Api\ExternalIntegrationResponder;
use Illuminate\Http\JsonResponse;

class BpjsSukonController extends Controller
{
    public function cariSep(SearchSepSuratKontrolRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryCarisepSuratKontrol($request->no_sep));
    }

    public function cariSuratKontrol(SearchSuratKontrolRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->querySuratKontrol($request->no_surat_kontrol));
    }

    public function listRencanaKontrol(ListRencanaKontrolRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryListRencanaKontrolByNoKartu(
            $request->bulan,
            $request->tahun,
            $request->no_kartu,
            $request->filter
        ));
    }

    public function listRencanaKontrolByTanggal(ListRencanaKontrolTanggalRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryListRencanaKontrolByTanggal(
            $request->tgl_awal,
            $request->tgl_akhir,
            $request->filter
        ));
    }

    public function insertSuratKontrol(InsertSuratKontrolRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->insertSuratKontrol($request->payload()));
    }

    public function updateSuratKontrol(UpdateSuratKontrolRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->updateSuratKontrol($request->payload()));
    }

    public function insertSpri(InsertSpriRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->insertSpri($request->payload()));
    }

    public function updateSpri(UpdateSpriRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->updateSpri($request->payload()));
    }
}
