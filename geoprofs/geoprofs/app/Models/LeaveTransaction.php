<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveTransaction extends Model
{
        public const TYPE_GRANT = 'grant';
    public const TYPE_DEDUCTION = 'deduction';
    public const TYPE_REVERSAL = 'reversal';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'user_id', 'leave_request_id', 'type', 'days', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return ['days' => 'decimal:1'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
