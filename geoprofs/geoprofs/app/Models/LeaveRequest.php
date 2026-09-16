<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['user_id', 'start_date', 'end_date', 'days_requested', 'reason', 'status', 'approved_by'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
