<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransitionStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_transition_id',
        'sort_order',
        'title',
        'description',
        'duration_months',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_months' => 'integer',
    ];

    public function careerTransition(): BelongsTo
    {
        return $this->belongsTo(CareerTransition::class);
    }
}
