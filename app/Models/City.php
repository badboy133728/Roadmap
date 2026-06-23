<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'region',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function savedPaths(): HasMany
    {
        return $this->hasMany(SavedPath::class);
    }
}
