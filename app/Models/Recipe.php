<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
     * 取得した本文HTMLは外部サイト由来の信頼できない入力のため、
     * 保存時に HTMLPurifier で許可リスト方式のサニタイズを行う（XSS対策）。
     */
    protected function contentHtml(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null || trim($value) === ''
                ? null
                : clean($value),
        );
    }

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
