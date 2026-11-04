<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use\app\models\Department;
use\app\models\StaffingPlan;
use\carbon\Carbon;

class StaffingPlanController extends Controller
{
     public function index(Request $request)
    {
        $afdeling = $this->afdelingVoor($request);

        return response()->json([
            'afdeling' => $afdeling->name,
            'standaard_minimum' => $afdeling->min_staffing,
            'planning' => $afdeling->staffingPlans()
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->get(['date', 'min_staffing']),
        ]);
    }

    public function store(Request $request)
    {
        $afdeling = $this->afdelingVoor($request);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'min_staffing' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $plan = StaffingPlan::updateOrCreate(
            ['department_id' => $afdeling->id, 'date' => Carbon::parse($data['date'])->toDateString()],
            ['min_staffing' => $data['min_staffing'], 'created_by' => $request->user()->id]
        );

        return response()->json($plan, 201);
    }

    public function updateDefault(Request $request)
    {
        $afdeling = $this->afdelingVoor($request);

        $data = $request->validate([
            'min_staffing' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $afdeling->update($data);

        return response()->json($afdeling);
    }

    private function afdelingVoor(Request $request): Department
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['manager', 'office_management', 'admin']), 403);

        if ($user->role === 'admin') {
            return Department::findOrFail($request->input('department_id'));
        }

        abort_if(! $user->department_id, 403);

        return $user->department;
    }
}
