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

Route::get('/downloads/postman-collection', [\App\Http\Controllers\Web\PostmanCollectionController::class, 'download'])->name('downloads.postman');

use App\Http\Controllers\Web\AdminClientController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminPricingSettingController;

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
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
