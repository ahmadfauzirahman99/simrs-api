<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendDiagnosticReportRequest;
use App\Services\SatuSehat\DiagnosticReportSubmissionService;
use Illuminate\Http\JsonResponse;

class DiagnosticReportController extends Controller
{
    public function send(SendDiagnosticReportRequest $request, DiagnosticReportSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
