<?php

use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\BpjsMedicalRecordPageController;
use App\Http\Controllers\Web\BpjsTesterPageController;
use App\Http\Controllers\Web\SatuSehatTesterPageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ClientAuthController;
use App\Http\Controllers\Web\ClientDashboardController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing', [
        'whatsappNumber' => config('app.whatsapp_contact'),
        'supportEmail' => config('app.support_email'),
        'user' => auth()->user(),
    ]);
})->name('landing');

Route::get('/apm', [\App\Http\Controllers\Web\ApmKioskPageController::class, 'index'])->name('apm.kiosk');

Route::get('/display/antrean', [\App\Http\Controllers\Web\DisplayAntreanController::class, 'index'])->name('display.antrean');
Route::get('/poli/panggil', [\App\Http\Controllers\Web\PoliPanggilController::class, 'index'])->name('poli.panggil');
Route::post('/poli/panggil/{antrean}/panggil', [\App\Http\Controllers\Web\PoliPanggilController::class, 'panggil'])->name('poli.panggil.call');
Route::post('/poli/panggil/{antrean}/selesai', [\App\Http\Controllers\Web\PoliPanggilController::class, 'selesai'])->name('poli.panggil.done');



Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('/demo-login', [AdminAuthController::class, 'demoLogin'])
        ->middleware('throttle:demo')
        ->name('login.demo');

    Route::get('/register', [ClientAuthController::class, 'create'])->name('register');
    Route::post('/register', [ClientAuthController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::post('/client/api-keys/regenerate', [ClientDashboardController::class, 'regenerateToken'])
        ->middleware('throttle:api-key-regenerate')
        ->name('client.api-keys.regenerate');
    Route::post('/client/payment-proofs', [\App\Http\Controllers\Web\ClientPaymentProofController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('client.payment-proofs.store');
    Route::get('/payment-proofs/{paymentProof}/image', \App\Http\Controllers\Web\PaymentProofImageController::class)
        ->name('payment-proofs.image');
});

Route::get('/error/{status}', function (int $status) {
    return Inertia::render('Error', ['status' => $status]);
})->where('status', '403|404|429|500|503');

// robots.txt & sitemap.xml dibuat dari domain aktif (url()), karena protokolnya mewajibkan URL absolut
// dan tiap instalasi (VPS demo / server RS) punya domain berbeda. Jangan taruh versi statis di public/.
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin/',
        'Disallow: /client/',
        'Disallow: /api/',
        '',
        'Sitemap: ' . url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

Route::get('/sitemap.xml', function () {
    $pages = [
        ['/', 'daily', '1.0'],
        ['/register', 'weekly', '0.9'],
        ['/downloads/postman-collection', 'weekly', '0.8'],
        ['/login', 'monthly', '0.7'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($pages as [$path, $changefreq, $priority]) {
        $xml .= "    <url>\n"
            . '        <loc>' . e(url($path)) . "</loc>\n"
            . "        <changefreq>{$changefreq}</changefreq>\n"
            . "        <priority>{$priority}</priority>\n"
            . "    </url>\n";
    }
    $xml .= '</urlset>' . "\n";

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});

Route::get('/downloads/postman-collection', [\App\Http\Controllers\Web\PostmanCollectionController::class, 'download'])->name('downloads.postman');

use App\Http\Controllers\Web\AdminClientController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminPricingSettingController;

use App\Http\Controllers\Web\AdminAntrolMonitorController;
use App\Http\Controllers\Web\AdminSepController;
Route::middleware(['auth', 'subscribed'])->group(function () {
    Route::get('/admin', function () {
        if (auth()->user()?->hasRole('superadmin')) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.bpjs-tester');
    })->name('admin.home');

    // Superadmin-only management group (Defense-in-Depth)
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/admin/clients', [AdminClientController::class, 'index'])->name('admin.clients');
        Route::post('/admin/clients/{user}/extend', [AdminClientController::class, 'extendSubscription'])->name('admin.clients.extend');
        Route::post('/admin/clients/{user}/set-trial', [AdminClientController::class, 'setTrial'])->name('admin.clients.set-trial');
        Route::post('/admin/clients/{user}/revoke-tokens', [AdminClientController::class, 'revokeTokens'])->name('admin.clients.revoke-tokens');
        Route::delete('/admin/clients/{user}', [AdminClientController::class, 'destroy'])->name('admin.clients.destroy');

        Route::post('/admin/payment-proofs', [\App\Http\Controllers\Web\AdminPaymentProofController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('admin.payment-proofs.store');
        Route::post('/admin/payment-proofs/{paymentProof}/approve', [\App\Http\Controllers\Web\AdminPaymentProofController::class, 'approve'])->name('admin.payment-proofs.approve');
        Route::post('/admin/payment-proofs/{paymentProof}/reject', [\App\Http\Controllers\Web\AdminPaymentProofController::class, 'reject'])->name('admin.payment-proofs.reject');
        Route::delete('/admin/payment-proofs/{paymentProof}', [\App\Http\Controllers\Web\AdminPaymentProofController::class, 'destroy'])->name('admin.payment-proofs.destroy');

        Route::get('/admin/pricing', [AdminPricingSettingController::class, 'index'])->name('admin.pricing');
        Route::post('/admin/pricing', [AdminPricingSettingController::class, 'update'])->name('admin.pricing.update');
    });

        Route::get('/admin/antrol-monitor', [AdminAntrolMonitorController::class, 'index'])->name('admin.antrol-monitor');
        Route::post('/admin/antrol-monitor/{antrean}/advance-task', [AdminAntrolMonitorController::class, 'advanceTask'])->name('admin.antrol-monitor.advance');

        Route::get('/admin/sep', [AdminSepController::class, 'index'])->name('admin.sep');
        Route::post('/admin/sep/search', [AdminSepController::class, 'search'])->name('admin.sep.search');
        Route::post('/admin/sep/generate', [AdminSepController::class, 'generate'])->name('admin.sep.generate');

    // Developer & testing endpoints
    Route::get('/admin/bpjs-tester', BpjsTesterPageController::class)->name('admin.bpjs-tester');
    Route::post('/admin/bpjs-tester/encrypt-preview', [BpjsTesterPageController::class, 'encryptPreview'])
        ->middleware('throttle:simulation')
        ->name('admin.bpjs-tester.encrypt-preview');
    Route::post('/admin/bpjs-tester/decrypt-preview', [BpjsTesterPageController::class, 'decryptPreview'])
        ->middleware('throttle:simulation')
        ->name('admin.bpjs-tester.decrypt-preview');
    Route::post('/admin/bpjs-tester/log-simulation', [BpjsTesterPageController::class, 'logSimulation'])
        ->middleware('throttle:simulation')
        ->name('admin.bpjs-tester.log-simulation');
    Route::get('/admin/bpjs-medical-record', BpjsMedicalRecordPageController::class)->name('admin.bpjs-medical-record');
    Route::get('/admin/satusehat-tester', SatuSehatTesterPageController::class)->name('admin.satusehat-tester');
    Route::get('/admin/version', [\App\Http\Controllers\Web\AdminVersionController::class, 'index'])->name('admin.version');
    Route::get('/admin/version/check', [\App\Http\Controllers\Web\AdminVersionController::class, 'checkUpdate'])->name('admin.version.check');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
