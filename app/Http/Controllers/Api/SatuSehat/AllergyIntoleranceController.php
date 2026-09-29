<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendAllergyIntoleranceRequest;
use App\Services\SatuSehat\AllergyIntoleranceSubmissionService;
use Illuminate\Http\JsonResponse;

class AllergyIntoleranceController extends Controller
{
    public function send(SendAllergyIntoleranceRequest $request, AllergyIntoleranceSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
