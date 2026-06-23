<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'profession_id',
        'city_id',
        'title',
        'company',
        'salary_text',
        'description',
        'external_url',
        'source',
        'experience_level',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
