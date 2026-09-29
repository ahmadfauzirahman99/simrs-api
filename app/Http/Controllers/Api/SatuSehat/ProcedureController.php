<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendProcedureRequest;
use App\Services\SatuSehat\ProcedureSubmissionService;
use Illuminate\Http\JsonResponse;

class ProcedureController extends Controller
{
    public function send(SendProcedureRequest $request, ProcedureSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
