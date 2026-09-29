<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendImmunizationRequest;
use App\Services\SatuSehat\ImmunizationSubmissionService;
use Illuminate\Http\JsonResponse;

class ImmunizationController extends Controller
{
    public function send(SendImmunizationRequest $request, ImmunizationSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
