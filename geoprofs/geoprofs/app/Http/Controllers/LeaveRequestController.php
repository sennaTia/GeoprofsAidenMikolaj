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
}
