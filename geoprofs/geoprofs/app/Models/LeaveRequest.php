<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
    'type',
    'start_date',
    'end_date',
    'comments',
    'user_id',
    'status',
];
}
