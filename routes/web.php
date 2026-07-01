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
    'password.fresh',
    'mfa',
    'audit',
])->group(function () {

    Route::get('/', function () {
        return redirect(route('dashboard'));
    });

    // index excluded: GET /staff is owned by CertificateController::staff() below
    // (Certificates/Staff.vue) — the page Staff/Index.vue used to serve is no
    // longer linked from navigation.
    Route::resource('staff', \App\Http\Controllers\StaffController::class)->except(['index']);
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'reportExcel'])->name('staff.export');
    Route::get('/certification/download/{staff_ids}', [\App\Http\Controllers\StaffController::class, 'downloadCertificates'])->name('certification.download');
    Route::post('/certification/install', [\App\Http\Controllers\StaffController::class, 'installCertificates'])->name('certification.install');

    Route::get('/workstation', [\App\Http\Controllers\WorkstationController::class, 'index'])->name('workstation');
    Route::post('/workstation-software', [\App\Http\Controllers\WorkstationSoftwareController::class, 'store'])->name('workstation-software.store');
    Route::delete('/workstation-software/{workstationSoftware}', [\App\Http\Controllers\WorkstationSoftwareController::class, 'destroy'])->name('workstation-software.destroy');
    Route::get('/workstation-software/{workstationSoftware}/download', [\App\Http\Controllers\WorkstationSoftwareController::class, 'download'])->name('workstation-software.download');
    Route::get('/dashboard', [\App\Http\Controllers\CertificateController::class, 'dashboard'])->name('dashboard');
    Route::get('/journal', [\App\Http\Controllers\CertificateController::class, 'journal'])->name('journal');
    Route::get('/staff', [\App\Http\Controllers\CertificateController::class, 'staff'])->name('staff');
    Route::get('/settings', [\App\Http\Controllers\CertificateController::class, 'settings'])->name('settings');

    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CertificateController::class, 'index'])->name('index');
        Route::get('/search', [\App\Http\Controllers\CertificateController::class, 'search'])->name('search');
        Route::post('/settings/test-parser', [\App\Http\Controllers\CertificateController::class, 'testParser'])->name('settings.test-parser');
        Route::post('/{certification}/revoke', [\App\Http\Controllers\CertificateController::class, 'revoke'])->name('revoke');

        Route::post('/trusted-ca', [\App\Http\Controllers\TrustedCertificateAuthorityController::class, 'store'])->name('trusted-ca.store');
        Route::delete('/trusted-ca/{trustedCertificateAuthority}', [\App\Http\Controllers\TrustedCertificateAuthorityController::class, 'destroy'])->name('trusted-ca.destroy');
        Route::get('/trusted-ca/{trustedCertificateAuthority}/content', [\App\Http\Controllers\TrustedCertificateAuthorityController::class, 'content'])->name('trusted-ca.content');
    });
//    Route::resource('journal', \App\Http\Controllers\JournalController::class);
    Route::get('/journals', function () {
        return Inertia::render('Journals/Index');
    })->name('journals.index');
    Route::get('/journals/patient-falling', [\App\Http\Controllers\JournalController::class, 'patientFalling'])->name('journals.patient-falling.index');
    Route::post('/journals/patient-falling', [\App\Http\Controllers\JournalEventPatientFallingController::class, 'store'])->name('journals.patient-falling.store');

    Route::prefix('admin')->middleware('ip.whitelist')->group(function () {
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

    Route::prefix('mis')->group(function () {
        Route::get('/', [\App\Http\Controllers\MisController::class, 'index'])->name('mis.index');

        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\MisController::class, 'users'])->name('mis.users');
            Route::post('/', [\App\Http\Controllers\MisController::class, 'createUser'])->name('mis.users.create');
            Route::prefix('{userId}')->group(function () {
                Route::get('/', [\App\Http\Controllers\MisController::class, 'user'])->name('mis.user');
                Route::get('/detail', [\App\Http\Controllers\MisController::class, 'userDetail'])->name('mis.users.user.detail');
                Route::post('/post', [\App\Http\Controllers\MisController::class, 'createPost'])->name('mis.users.post.create');
                Route::put('/update', [\App\Http\Controllers\MisController::class, 'updateUser'])->name('mis.users.user.update');
                Route::put('/update-access', [\App\Http\Controllers\MisController::class, 'updateOrCreateAccess'])->name('mis.users.user.access.update');
                Route::put('/update-post', [\App\Http\Controllers\MisController::class, 'updatePost'])->name('mis.users.user.post.update');
                Route::put('/update-roles', [\App\Http\Controllers\MisController::class, 'updateRoles'])->name('mis.users.user.roles.update');
                Route::post('/change-password', [\App\Http\Controllers\MisController::class, 'changePassword'])->name('mis.users.password.change');
            });
        });

        Route::prefix('templates')->group(function () {
            Route::get('/', [\App\Http\Controllers\MisController::class, 'roles'])->name('mis.templates.roles');
            Route::post('/', [\App\Http\Controllers\MisController::class, 'createTemplate'])->name('mis.templates.roles.create');
            Route::put('{template}', [\App\Http\Controllers\MisController::class, 'updateTemplate'])->name('mis.templates.roles.update');
        });

        Route::prefix('import')->group(function () {
            Route::post('/doctors', [\App\Http\Controllers\MisController::class, 'importDoctors'])->name('mis.import.doctors');
        });
//        Route::prefix('posts')->group(function () {
//            Route::get('/', [\App\Http\Controllers\MisController::class, 'posts'])->name('mis.posts');
//        });

    });
});
