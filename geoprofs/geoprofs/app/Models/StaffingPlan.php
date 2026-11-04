<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffingPlan extends Model
{
    protected $fillable = ['department_id', 'date', 'min_staffing', 'created_by'];

    protected $casts = [
        'min_staffing' => 'integer',
        'date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}