<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LeaveBalanceController;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('/verlofsaldo', [LeaveBalanceController::class, 'show']);
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
