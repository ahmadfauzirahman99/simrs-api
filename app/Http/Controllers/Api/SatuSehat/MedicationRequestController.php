<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendMedicationRequestRequest;
use App\Services\SatuSehat\MedicationRequestSubmissionService;
use Illuminate\Http\JsonResponse;

class MedicationRequestController extends Controller
{
    public function send(SendMedicationRequestRequest $request, MedicationRequestSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
