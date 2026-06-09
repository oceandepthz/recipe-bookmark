<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookingLog extends Model
{
    protected $fillable = [
        'body',
        'cooked_on',
    ];

    protected function casts(): array
    {
        return [
            'cooked_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
