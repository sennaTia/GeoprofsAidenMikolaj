<?php

use App\Http\Controllers\LeaveRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show'])
    ->middleware('auth:sanctum');