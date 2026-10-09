<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\LeaveRequest;
use App\Models\LeaveApproval;
use App\Models\ApprovalProcedure;

class LeaveRequestController extends Controller
{
    public function approve(LeaveRequest $leaveRequest)
    {
        Gate::authorize('approve-leave-request', $leaveRequest);

        $approver = auth()->user();
        $employee = $leaveRequest->user;

        if (in_array($leaveRequest->status, ['goedgekeurd', 'afgekeurd'])) {
            return response()->json(['message' => 'Already handled.'], 422);
        }

        $procedure = ApprovalProcedure::where('department_id', $employee->department_id)->first();
        $approverRole = $procedure ? $procedure->approver_role : 'manager';
        $requiredApprovals = $procedure ? $procedure->required_approvals : 1;

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

        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'user_id' => $approver->id,
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
