<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendClaimRequest;
use App\Services\SatuSehat\ClaimSubmissionService;
use Illuminate\Http\JsonResponse;

class ClaimController extends Controller
{
    public function send(SendClaimRequest $request, ClaimSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error($result['message'], $result['code'], $result['response']);
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
