<?php

namespace App\Http\Controllers;

use App\Models\ApprovalProcedure;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\StaffingPlan;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    public function myRequests(Request $request)
    {
        $user = $request->user();

        $leaveRequests = LeaveRequest::where('user_id', $user->id)->get();

        return Inertia::render('mijn-aanvragen', [
            'leaveRequests' => $leaveRequests,
        ]);
    }

    public function teamRequests(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'manager' || $user->department_id === null) {
            abort(403, 'Geen toegang tot het teamoverzicht.');
        }

        $leaveRequests = LeaveRequest::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department_id', $user->department_id);
            })
            ->where('user_id', '!=', $user->id)
            ->get();

        return Inertia::render('team-aanvragen', [
            'leaveRequests' => $leaveRequests,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        Gate::authorize('approve-leave-request', $leaveRequest);

        $approver = auth()->user();
        $employee = $leaveRequest->user;

        if (in_array($leaveRequest->status, ['goedgekeurd', 'afgewezen'])) {
            return response()->json(['message' => 'Already handled.'], 422);
        }

        $procedure = ApprovalProcedure::where('department_id', $employee->department_id)->first();
        $approverRole = $procedure ? $procedure->role : 'manager';
        $requiredApprovals = $procedure ? $procedure->order : 1;

        if ($approver->role !== $approverRole) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($approverRole === 'manager' && $approver->department_id != $employee->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($approver->id === $employee->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($leaveRequest->approvals()->where('user_id', $approver->id)->exists()) {
            return response()->json(['message' => 'You already approved this request.'], 422);
        }

        $request->validate([
            'override_reason' => ['nullable', 'string', 'min:10', 'max:500'],
        ]);

        $bezetting = $this->berekenBezetting($leaveRequest);

        if ($bezetting['onder_minimum'] && ! $request->filled('override_reason')) {
            return response()->json([
                'message' => 'Waarschuwing: door deze goedkeuring zakt de bezetting onder het minimum. Geef een reden op om toch goed te keuren.',
                'bezetting' => $bezetting,
            ], 409);
        }

        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'user_id' => $approver->id,
            'lowest_staffing' => $bezetting['krapste_dag']['verwacht'] ?? null,
            'min_staffing' => $bezetting['krapste_dag']['minimum'] ?? null,
            'override_reason' => $bezetting['onder_minimum'] ? $request->input('override_reason') : null,
        ]);

        if ($leaveRequest->approvals()->count() < $requiredApprovals) {
            return response()->json([
                'id' => $leaveRequest->id,
                'status' => $leaveRequest->status,
                'approvals' => $leaveRequest->approvals()->count(),
                'required' => $requiredApprovals,
            ]);
        }

        $leaveRequest->update([
            'status' => 'goedgekeurd',
            'approved_by' => $approver->id,
        ]);

        return response()->json([
            'id' => $leaveRequest->id,
            'status' => $leaveRequest->status,
        ]);
    }

    public function reject(LeaveRequest $leaveRequest)
    {
        Gate::authorize('approve-leave-request', $leaveRequest);

        $leaveRequest->update([
            'status' => 'afgewezen',
            'approved_by' => auth()->id(),
        ]);

        return response()->json([
            'id' => $leaveRequest->id,
            'status' => $leaveRequest->status,
        ]);
    }

    public function approvebulk(Request $request)
    {
        $ids = $request->input('leave_request_ids', []);

        $goedgekeurd = [];
        $overgeslagen = [];

        foreach (LeaveRequest::whereIn('id', $ids)->get() as $leaveRequest) {
            if (! Gate::allows('approve-leave-request', $leaveRequest)) {
                $overgeslagen[] = [
                    'id' => $leaveRequest->id,
                    'reden' => 'geen bevoegdheid of eigen aanvraag',
                ];

                continue;
            }

            $jaar = (int) $leaveRequest->start_date->format('Y');

            $resterend = $leaveRequest->user->remainingDays($jaar);

            if ($resterend === null || $resterend < $leaveRequest->days_requested) {
                $overgeslagen[] = [
                    'id' => $leaveRequest->id,
                    'reden' => 'onvoldoende saldo',
                ];

                continue;
            }

            if ($this->berekenBezetting($leaveRequest)['onder_minimum']) {
                $overgeslagen[] = [
                    'id' => $leaveRequest->id,
                    'reden' => 'bezetting onder minimum, los beoordelen',
                ];

                continue;
            }

            $leaveRequest->update([
                'status' => 'goedgekeurd',
                'approved_by' => auth()->id(),
            ]);

            $goedgekeurd[] = $leaveRequest->id;
        }

        return response()->json([
            'goedgekeurd' => $goedgekeurd,
            'overgeslagen' => $overgeslagen,
        ]);
    }

    public function absenceOverview(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'manager' || $user->department_id === null) {
            abort(403, 'Geen toegang tot het afwezigheidsoverzicht.');
        }

        $today = now()->toDateString();

        $totalEmployees = User::where('department_id', $user->department_id)
            ->where('role', 'werknemer')
            ->count();

        $absentEmployees = LeaveRequest::where('status', 'goedgekeurd')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department_id', $user->department_id)
                    ->where('role', 'werknemer');
            })
            ->distinct()
            ->count('user_id');

        $departmentName = DB::table('departments')
            ->where('id', $user->department_id)
            ->value('name');

        return Inertia::render('afwezigheid-inzien', [
            'departmentName' => $departmentName,
            'totalEmployees' => $totalEmployees,
            'absentEmployees' => $absentEmployees,
            'presentEmployees' => $totalEmployees - $absentEmployees,
        ]);
    }

    public function staffing(LeaveRequest $leaveRequest)
    {
        Gate::authorize('approve-leave-request', $leaveRequest);

        return response()->json($this->berekenBezetting($leaveRequest));
    }

    private function berekenBezetting(LeaveRequest $leaveRequest): array
    {
        $aanvrager = $leaveRequest->user;
        $afdeling = $aanvrager->department;
        abort_if(! $afdeling, 422, 'Aanvrager heeft geen afdeling.');

        $teamIds = User::where('department_id', $afdeling->id)
            ->whereIn('role', ['werknemer', 'manager'])
            ->pluck('id');

        $overlappend = LeaveRequest::whereIn('user_id', $teamIds)
            ->where('id', '!=', $leaveRequest->id)
            ->whereIn('status', ['goedgekeurd', 'in_behandeling'])
            ->whereDate('start_date', '<=', $leaveRequest->end_date)
            ->whereDate('end_date', '>=', $leaveRequest->start_date)
            ->get();

        $planning = StaffingPlan::where('department_id', $afdeling->id)
            ->whereBetween('date', [$leaveRequest->start_date->toDateString(), $leaveRequest->end_date->toDateString()])
            ->get()
            ->keyBy('date');

        $dagen = [];
        foreach (CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date) as $dag) {
            if ($dag->isWeekend()) {
                continue;
            }

            $opDezeDag = $overlappend->filter(fn ($r) => $r->start_date->lte($dag) && $r->end_date->gte($dag));

            $afwezigIds = $opDezeDag->where('status', 'goedgekeurd')
                ->pluck('user_id')
                ->push($aanvrager->id)
                ->unique()
                ->intersect($teamIds);

            $verwacht = $teamIds->count() - $afwezigIds->count();
            $minimum = $planning->get($dag->toDateString())?->min_staffing ?? $afdeling->min_staffing;

            $dagen[] = [
                'datum' => $dag->toDateString(),
                'afwezig' => $afwezigIds->count(),
                'openstaand' => $opDezeDag->where('status', 'in_behandeling')->count(),
                'verwacht' => $verwacht,
                'minimum' => $minimum,
                'onder_minimum' => $verwacht < $minimum,
            ];
        }

        $krapste = collect($dagen)->sortBy(fn ($d) => $d['verwacht'] - $d['minimum'])->first();

        return [
            'afdeling' => $afdeling->name,
            'teamgrootte' => $teamIds->count(),
            'onder_minimum' => collect($dagen)->contains('onder_minimum', true),
            'krapste_dag' => $krapste,
            'dagen' => $dagen,
        ];
    }
}