<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceTransfer extends Model
{
    protected $fillable = ['leave_request_id', 'payload', 'hash', 'status', 'attempts', 'last_error', 'sent_at', 'alerted_at'];

    protected $hidden = ['payload', 'hash'];

    protected $casts = [
        'payload' => 'encrypted',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'alerted_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
