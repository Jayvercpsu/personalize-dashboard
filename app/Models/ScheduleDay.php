<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_date',
        'shift_a_associate_id',
        'shift_b_associate_id',
        'part_time_associate_id',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    public function shiftA(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'shift_a_associate_id');
    }

    public function shiftB(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'shift_b_associate_id');
    }

    public function partTime(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'part_time_associate_id');
    }
}
