<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PostmanCollectionBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostmanCollectionController extends Controller
{
    public function download(Request $request): Response
    {
        $user = $request->user();
        $userApiKey = null;

        if ($user) {
            $userApiKey = $user->tokens()->latest()->first()?->tokenable_id ? null : null;
            // If user has a token, we can use their latest plain token if available, or placeholder
        }

        $collection = PostmanCollectionBuilder::build($userApiKey, url('/'));

        $filename = 'simrs-gateway-bpjs-postman-collection.json';

        return response()->json($collection, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json',
        ]);
    }
}
