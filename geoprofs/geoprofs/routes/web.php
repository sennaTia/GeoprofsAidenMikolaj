<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Schedule;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('/verlofsaldo', [LeaveBalanceController::class, 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/verlofaanvragen/{leaveRequest}/goedkeuren', [LeaveRequestController::class, 'approve']);
    Route::post('/verlofaanvragen/{leaveRequest}/afwijzen', [LeaveRequestController::class, 'reject']);
    Route::post('/verlofaanvragen/bulk-goedkeuren', [LeaveRequestController::class, 'approvebulk']);
});

Schedule::command('leave:create-yearly-balances')->yearlyOn(1, 1, '00:00');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
