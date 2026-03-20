<?php

namespace App\Http\Controllers\Api\Bpjs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpjs\HistoryPelayananPesertaRequest;
use App\Http\Requests\Bpjs\MonitoringKlaimRequest;
use App\Http\Requests\Bpjs\MonitoringKunjunganRequest;
use App\Http\Requests\Bpjs\PesertaRequest;
use App\Http\Requests\Bpjs\SearchSepRequest;
use App\Http\Requests\Bpjs\Sep\DeleteSepRequest;
use App\Http\Requests\Bpjs\Sep\InsertSepRequest;
use App\Http\Requests\Bpjs\Sep\UpdateSepRequest;
use App\Services\Bpjs\BridgingV3;
use App\Support\Api\ExternalIntegrationResponder;
use Illuminate\Http\JsonResponse;

class BpjsVClaimController extends Controller
{
    // tipe: 1 = Nomor Kartu, 2 = Nomor KTP
    public function peserta(PesertaRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetPeserta(
            $request->nomor,
            (int) ($request->tipe ?? 1)
        ));
    }

    public function cariSep(SearchSepRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->querySearchSEP($request->no_sep));
    }

    public function riwayatSep(HistoryPelayananPesertaRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryGetRiwayatSEP($request->no_kartu));
    }

    public function insertSep(InsertSepRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryInsertSEP($request->payload()), 'SEP berhasil dibuat');
    }

    public function updateSep(UpdateSepRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryUpdateSEP($request->payload()), 'SEP berhasil diupdate');
    }

    public function hapusSep(DeleteSepRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryHapusSEP($request->payload()), 'SEP berhasil dihapus');
    }

    public function monitoringKlaim(MonitoringKlaimRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->monitoringKlaim(
            $request->tanggal,
            $request->tipe,
            $request->status
        ));
    }

    public function monitoringKunjungan(MonitoringKunjunganRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->monitoringKunjungan($request->tanggal, $request->tipe));
    }

    public function historyPelayananPeserta(HistoryPelayananPesertaRequest $request, BridgingV3 $bpjs, ExternalIntegrationResponder $responder): JsonResponse
    {
        return $responder->bpjs($bpjs, $bpjs->queryHistoryPelayananPeserta($request->no_kartu));
    }
}
