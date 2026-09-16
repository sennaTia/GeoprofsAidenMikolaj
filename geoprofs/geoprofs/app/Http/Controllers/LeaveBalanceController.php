<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function show(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $user = $request->user();

        $remaining = $user->remainingDays($year);

        if ($remaining === null) {
            return response()->json([
                'year' => $year,
                'remaining_days' => null,
                'message' => 'Er is nog geen verlofsaldo ingesteld voor dit jaar.',
            ], 404);
        }

        return response()->json([
            'year' => $year,
            'remaining_days' => $remaining,
        ]);
    }
}
