<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SatuSehat\SendEncounterRequest;
use App\Services\SatuSehat\EncounterSubmissionService;
use Illuminate\Http\JsonResponse;

class EncounterController extends Controller
{
    public function send(SendEncounterRequest $request, EncounterSubmissionService $service): JsonResponse
    {
        $result = $service->send($request->validated());

        if (!$result['ok']) {
            return ApiResponse::error(
                $result['message'],
                $result['code'],
                $result['response']
            );
        }

        return ApiResponse::success($result['response'], $result['message'], $result['code']);
    }
}
