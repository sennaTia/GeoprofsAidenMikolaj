<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function show(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $user = $request->user();

        return response()->json([
            'year' => $year,
            'remaining_days' => $user->remainingDays($year),
        ]);
    }
}
