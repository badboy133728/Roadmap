<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profession extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'skills',
        'outlook',
        'is_active',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class, 'category_id');
    }

    public function careerPathSteps(): HasMany
    {
        return $this->hasMany(CareerPathStep::class)->orderBy('sort_order');
    }

    public function educationPrograms(): HasMany
    {
        return $this->hasMany(EducationProgram::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(CareerTransition::class, 'from_profession_id');
    }

    public function transitionsTo(): HasMany
    {
        return $this->hasMany(CareerTransition::class, 'to_profession_id');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorite_professions');
    }

    public function savedPaths(): HasMany
    {
        return $this->hasMany(SavedPath::class);
    }

    public function jobVacancies(): HasMany
    {
        return $this->hasMany(JobVacancy::class)->orderBy('sort_order');
    }

    public function currentUsers(): HasMany
    {
        return $this->hasMany(User::class, 'current_profession_id');
    }
}
