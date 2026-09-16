<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    public function index()
{
    return LeaveRequest::all();
}

public function store(Request $request)
{
    $validated = $request->validate([
        'type' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'comments' => 'nullable|string',
        'user_id' => 'required|integer|exists:users,id',
    ]);

    $leaveRequest = LeaveRequest::create($validated);

    return $leaveRequest;
}

}
