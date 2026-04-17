<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProcessPathAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'associate_id',
        'year',
        'quarter',
        'start_path',
        'end_path',
    ];

    public function associate(): BelongsTo
    {
        return $this->belongsTo(Associate::class);
    }
}
