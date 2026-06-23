<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'profession_id',
        'name',
        'duration_years',
        'study_form',
    ];

    protected $casts = [
        'duration_years' => 'decimal:1',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }
}
