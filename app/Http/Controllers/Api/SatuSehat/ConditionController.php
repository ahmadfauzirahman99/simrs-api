<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendConditionRequest;
use App\Services\SatuSehat\ConditionSubmissionService;
use Illuminate\Http\JsonResponse;

class ConditionController extends Controller
{
    public function send(SendConditionRequest $request, ConditionSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
