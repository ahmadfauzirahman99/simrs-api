<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendMedicationDispenseRequest;
use App\Services\SatuSehat\MedicationDispenseSubmissionService;
use Illuminate\Http\JsonResponse;

class MedicationDispenseController extends Controller
{
    public function send(SendMedicationDispenseRequest $request, MedicationDispenseSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
