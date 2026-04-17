<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Associate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function processPathAssignments(): HasMany
    {
        return $this->hasMany(ProcessPathAssignment::class);
    }

    public function shiftASchedules(): HasMany
    {
        return $this->hasMany(ScheduleDay::class, 'shift_a_associate_id');
    }

    public function shiftBSchedules(): HasMany
    {
        return $this->hasMany(ScheduleDay::class, 'shift_b_associate_id');
    }
}
