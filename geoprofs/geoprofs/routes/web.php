<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LeaveBalanceController;
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

Schedule::command('leave:create-yearly-balances')->yearlyOn(1, 1, '00:00');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
