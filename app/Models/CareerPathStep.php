<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerPathStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'profession_id',
        'sort_order',
        'step_type',
        'title',
        'description',
        'duration_months',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_months' => 'integer',
    ];

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }
}
