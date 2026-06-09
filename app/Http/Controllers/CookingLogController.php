<?php

namespace App\Http\Controllers;

use App\Models\CookingLog;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CookingLogController extends Controller
{
    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'cooked_on' => ['nullable', 'date'],
        ], [], [
            'body' => 'コメント',
            'cooked_on' => '作った日',
        ]);

        $recipe->cookingLogs()->create($data);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('status', '「作ってみた」コメントを追加しました。');
    }

    public function destroy(CookingLog $cookingLog): RedirectResponse
    {
        $recipeId = $cookingLog->recipe_id;
        $cookingLog->delete();

        return redirect()
            ->route('recipes.show', $recipeId)
            ->with('status', 'コメントを削除しました。');
    }
}
