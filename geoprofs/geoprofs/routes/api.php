<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaveRequestController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
Route::post('/leave-requests', [LeaveRequestController::class, 'store']);