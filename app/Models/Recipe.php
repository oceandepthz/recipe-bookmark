<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'url',
        'domain',
        'title',
        'site_name',
        'image_url',
        'excerpt',
        'content_html',
    ];

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<CookingLog, $this>
     */
    public function cookingLogs(): HasMany
    {
        return $this->hasMany(CookingLog::class)->latest('cooked_on')->latest('id');
    }
}
