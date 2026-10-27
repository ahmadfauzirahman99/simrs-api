<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ApmKioskPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Apm/Kiosk', [
            'rsName' => config('app.name', 'HAURA MEDICAL'),
            'today' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ]);
    }
}
