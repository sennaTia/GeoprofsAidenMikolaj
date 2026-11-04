<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\ApprovalProcedure;

class ApprovalProcedureController extends Controller
{
    public function show($departmentId)
    {
        Department::findOrFail($departmentId);

        $procedure = ApprovalProcedure::where('department_id', $departmentId)->first();

        if (!$procedure) {
            return response()->json([
                'approver_role' => 'manager',
                'required_approvals' => 1,
                'description' => 'Standaardprocedure: 1 goedkeuring van een manager van de afdeling.'
            ]);
        }

        return response()->json([
            'approver_role' => $procedure->role,
            'required_approvals' => $procedure->order,
            'description' => $procedure->description,
        ]);
    }

    public function update(Request $request, $departmentId)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Department::findOrFail($departmentId);

        $data = $request->validate([
            'approver_role' => 'required|in:manager,office-manager',
            'required_approvals' => 'required|integer|min:1|max:3',
            'description' => 'required|string|max:2000',
        ]);

        $procedure = ApprovalProcedure::updateOrCreate(
            ['department_id' => $departmentId],
            [
                'role' => $data['approver_role'],
                'order' => $data['required_approvals'],
                'description' => $data['description'],
                'updated_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Procedure saved.',
            'procedure' => $procedure
        ]);
    }
}