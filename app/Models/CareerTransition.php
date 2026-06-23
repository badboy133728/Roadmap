<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerTransition extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_profession_id',
        'to_profession_id',
        'difficulty',
        'duration_months',
        'description',
    ];

    protected $casts = [
        'duration_months' => 'integer',
    ];

    public function fromProfession(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'from_profession_id');
    }

    public function toProfession(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'to_profession_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TransitionStep::class)->orderBy('sort_order');
    }
}
