<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    public function approve(LeaveRequest $leaveRequest)
    {
        Gate::authorize('approve-leave-request', $leaveRequest);

        $leaveRequest->update([
            'status' => 'goedgekeurd',
            'approved_by' => auth()->id(),
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
            if (!Gate::allows('approve-leave-request', $leaveRequest)) {
                $overgeslagen[] = ['id' => $leaveRequest->id, 'reden' => 'geen bevoegdheid of eigen aanvraag'];
                continue;
            }

            $jaar = (int) $leaveRequest->start_date->format('Y');
            $resterend = $leaveRequest->user->remainingDays($jaar);

            if ($resterend === null || $resterend < $leaveRequest->days_requested) {
                $overgeslagen[] = ['id' => $leaveRequest->id, 'reden' => 'onvoldoende saldo'];
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
}
