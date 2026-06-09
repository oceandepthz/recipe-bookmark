<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * オーナー本人かどうか。view/update/delete はすべて所有者のみ許可。
     */
    public function view(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }
}
