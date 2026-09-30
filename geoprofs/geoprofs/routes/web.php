<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::get('/mijn-aanvragen', function () {
        return Inertia::render('mijn-aanvragen');
    });
    Route::get('/kalender', function () {
        return Inertia::render('kalender');
    });
    Route::get('/hulp', function () {
        return Inertia::render('hulp');
    });
    Route::get('/verlof-aanvragen', function () {
        return Inertia::render('verlof-aanvragen');
    });
    Route::get('/verlofsaldo', [LeaveBalanceController::class, 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/verlofaanvragen/{leaveRequest}/goedkeuren', [LeaveRequestController::class, 'approve']);
    Route::post('/verlofaanvragen/{leaveRequest}/afwijzen', [LeaveRequestController::class, 'reject']);
    Route::post('/verlofaanvragen/bulk-goedkeuren', [LeaveRequestController::class, 'approvebulk']);
});
Route::post('/inloggen', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    return redirect('/login.html');
})->withoutMiddleware(ValidateCsrfToken::class);



Schedule::command('leave:create-yearly-balances')->yearlyOn(1, 1, '00:00');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
