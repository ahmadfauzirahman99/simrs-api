<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\SatuSehat\SatuSehatTesterCatalog;
use Inertia\Inertia;
use Inertia\Response;

class SatuSehatTesterPageController extends Controller
{
    public function __invoke(SatuSehatTesterCatalog $catalog): Response
    {
        return Inertia::render('SatuSehat/Tester', [
            'endpointGroups' => $catalog->groups(),
        ]);
    }
}
