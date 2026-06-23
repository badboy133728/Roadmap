<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'search_url_template',
        'icon',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function searchUrl(string $query): string
    {
        return str_replace('{query}', urlencode($query), $this->search_url_template);
    }
}
