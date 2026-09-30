<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $jaar = now()->year;

        $saldo = $user->leaveBalances()->where('year', $jaar)->first();
        $aanvragenDitJaar = $user->leaveRequests()->whereYear('start_date', $jaar);

        $opgenomen = (float) (clone $aanvragenDitJaar)->where('status', 'goedgekeurd')->sum('days_requested');
        $inBehandeling = (clone $aanvragenDitJaar)->where('status', 'in_behandeling');

        return Inertia::render('dashboard', [
            'jaar' => $jaar,
            'totaal' => $saldo ? (float) $saldo->total_days : null,
            'opgenomen' => $opgenomen,
            'resterend' => $saldo ? (float) $saldo->total_days - $opgenomen : null,
            'inBehandeling' => (float) (clone $inBehandeling)->sum('days_requested'),
            'aantalOpenstaand' => (clone $inBehandeling)->count(),
            'recenteAanvragen' => $user->leaveRequests()
                ->latest('start_date')
                ->take(5)
                ->get()
                ->map(fn (LeaveRequest $aanvraag) => [
                    'id' => $aanvraag->id,
                    'start_date' => $aanvraag->start_date->toDateString(),
                    'end_date' => $aanvraag->end_date->toDateString(),
                    'reason' => $aanvraag->reason,
                    'status' => $aanvraag->status,
                    'days_requested' => (float) $aanvraag->days_requested,
                ]),
        ]);
    }
}
