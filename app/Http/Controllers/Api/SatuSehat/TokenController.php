<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Http\Controllers\Controller;
use App\Services\SatuSehat\OAuth2Client;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    public function index(OAuth2Client $oauth2Client): JsonResponse
    {
        $token = $oauth2Client->getValidToken();

        if (is_array($token)) {
            return response()->json([
                'metaData' => [
                    'code' => '500',
                    'message' => $token['msg'] ?? 'Gagal mengambil token',
                ],
                'response' => (object) [],
            ], 500);
        }

        return response()->json([
            'metaData' => [
                'code' => '200',
                'message' => 'Sukses',
            ],
            'response' => [
                'token' => $token,
                'organization_id' => $oauth2Client->organization_id,
                'base_url' => $oauth2Client->base_url,
                'environment' => $oauth2Client->environment,
            ],
        ], 200);
    }
}
