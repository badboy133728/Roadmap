<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profession_id',
        'city_id',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
