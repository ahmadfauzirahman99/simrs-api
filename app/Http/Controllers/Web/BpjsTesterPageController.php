<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Bpjs\BpjsTesterCatalog;
use Inertia\Inertia;
use Inertia\Response;

class BpjsTesterPageController extends Controller
{
    public function __invoke(BpjsTesterCatalog $catalog): Response
    {
        return Inertia::render('Bpjs/Tester', [
            'endpointGroups' => $catalog->groups(),
            'systemLabel' => 'BPJS',
        ]);
    }
}
