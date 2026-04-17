<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HourlyNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_date',
        'hour_slot',
        'note',
        'status',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];
}
