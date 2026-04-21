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
        'path_1',
        'path_2',
        'path_3',
    ];

    public function associate(): BelongsTo
    {
        return $this->belongsTo(Associate::class);
    }
}
