<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/', function () {
        return Inertia::render('Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'staffCount' => \App\Models\Staff::count()
        ]);
    });

    Route::resource('staff', \App\Http\Controllers\StaffController::class);
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'reportExcel'])->name('staff.export');
    Route::get('/certification/download/{staff_ids}', [\App\Http\Controllers\StaffController::class, 'downloadCertificates'])->name('certification.download');
    Route::post('/certification/install', [\App\Http\Controllers\StaffController::class, 'installCertificates'])->name('certification.install');
//    Route::resource('journal', \App\Http\Controllers\JournalController::class);
    Route::get('/journals', function () {
        return Inertia::render('Journals/Index');
    })->name('journals.index');
    Route::get('/journals/patient-falling', [\App\Http\Controllers\JournalController::class, 'patientFalling'])->name('journals.patient-falling.index');
    Route::post('/journals/patient-falling', [\App\Http\Controllers\JournalEventPatientFallingController::class, 'store'])->name('journals.patient-falling.store');

    Route::prefix('admin')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Index', [
                'userCount' => \App\Models\User::count(),
                'roleCount' => \App\Models\Role::count(),
            ]);
        })->name('admin.index');

        Route::get('/users', function () {
            return Inertia::render('Admin/Users/Index', [
                'usersCount' => \App\Models\User::count(),
            ]);
        })->name('admin.users');
        Route::get('/roles', function () {
            return Inertia::render('Admin/Roles/Index', [
                'roles' => \App\Models\Role::all(),
            ]);
        })->name('admin.roles');
    });
});
