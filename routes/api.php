<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/certificate/read', [\App\Http\Controllers\CertificateController::class, 'read'])->name('certificate.read');
