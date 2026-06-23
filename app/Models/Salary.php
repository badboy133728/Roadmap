<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'profession_id',
        'city_id',
        'level',
        'salary_min',
        'salary_median',
        'salary_max',
        'source',
        'updated_at_source',
    ];

    protected $casts = [
        'salary_min' => 'integer',
        'salary_median' => 'integer',
        'salary_max' => 'integer',
        'updated_at_source' => 'date',
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
