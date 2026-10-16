<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\models\Department;
use app\models\ApprovalProcedure;

class ApprovalProcedureController extends Controller
{
    public function show($departmentId)
    {
        Department::findOrFail($departmentId);

        $procedure = ApprovalProcedure::where('department_id', $departmentId)->first();
        
        if (!$procedure) {
            return response()->json(['approver_role' => 'manager', 'required_approvals' => 1, 'description'=> 'Standaardprocedure: 1 goedkeuring van een manager van de afdeling.']);
        }

        return response()->json($procedure);
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

        $data['updated_by'] = $request->user()->id;

        $procedure = ApprovalProcedure::updateOrCreate(
            ['department_id' => $departmentId],
            $data
        );

        return response()->json(['message' => 'Procedure saved.', 'procedure' => $procedure]);
    }
}
