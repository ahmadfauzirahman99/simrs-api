<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\SystemVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AdminVersionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Version/Index', [
            'currentVersion' => SystemVersion::CURRENT_VERSION,
            'releaseDate' => SystemVersion::RELEASE_DATE,
            'releaseName' => SystemVersion::RELEASE_NAME,
            'releases' => SystemVersion::releases(),
            'repoUrl' => 'https://github.com/ahmadfauzirahman99/simrs-api',
        ]);
    }

    /**
     * Cek status update terbaru ke remote GitHub API
     */
    public function checkUpdate(): JsonResponse
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SIMRS-Gateway-Updater',
                'Accept' => 'application/vnd.github.v3+json',
            ])->timeout(8)->get('https://api.github.com/repos/ahmadfauzirahman99/simrs-api/commits/pmaster');

            if ($response->successful()) {
                $commit = $response->json();
                $remoteSha = substr((string) ($commit['sha'] ?? ''), 0, 7);
                $remoteMessage = (string) ($commit['commit']['message'] ?? '');
                $remoteDate = (string) ($commit['commit']['committer']['date'] ?? '');

                return response()->json([
                    'success' => true,
                    'status' => 'up_to_date',
                    'current_version' => SystemVersion::CURRENT_VERSION,
                    'latest_commit' => [
                        'sha' => $remoteSha,
                        'message' => strtok($remoteMessage, "\n"),
                        'date' => $remoteDate,
                    ],
                    'message' => 'Sistem Anda saat ini menggunakan versi dan komit terbaru dari repositori.',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 'ok',
                'current_version' => SystemVersion::CURRENT_VERSION,
                'message' => 'Versi lokal aktif adalah ' . SystemVersion::CURRENT_VERSION . ' (' . SystemVersion::RELEASE_NAME . ').',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => true,
                'status' => 'local_only',
                'current_version' => SystemVersion::CURRENT_VERSION,
                'message' => 'Berjalan di mode intranet offline. Versi lokal: ' . SystemVersion::CURRENT_VERSION,
            ]);
        }
    }
}
