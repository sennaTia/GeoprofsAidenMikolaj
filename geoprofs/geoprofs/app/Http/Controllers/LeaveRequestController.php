<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->user_id !== $user->id) {
            abort(403);
        }

        return $leaveRequest;
    }
}