<?php

use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\BpjsTesterPageController;
use App\Http\Controllers\Web\SatuSehatTesterPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.bpjs-tester')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/admin/bpjs-tester', BpjsTesterPageController::class)->name('admin.bpjs-tester');
    Route::get('/admin/satusehat-tester', SatuSehatTesterPageController::class)->name('admin.satusehat-tester');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
