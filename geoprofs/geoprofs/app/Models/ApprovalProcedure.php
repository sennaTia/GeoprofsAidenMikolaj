<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalProcedure extends Model
{
    protected $fillable = ['department_id', 'role', 'order', 'description', 'updated_by'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
