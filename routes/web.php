<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Index', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::resource('staff', \App\Http\Controllers\StaffController::class);
//    Route::resource('journal', \App\Http\Controllers\JournalController::class);
    Route::get('/journals', function () {
        return Inertia::render('Journals/Index');
    })->name('journals.index');
    Route::get('/journals/patient-falling', [\App\Http\Controllers\JournalController::class, 'patientFalling'])->name('journals.patient-falling.index');
    Route::post('/journals/patient-falling', [\App\Http\Controllers\JournalEventPatientFallingController::class, 'store'])->name('journals.patient-falling.store');
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
