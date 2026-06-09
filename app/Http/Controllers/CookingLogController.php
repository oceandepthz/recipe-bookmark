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
        // メモを追加できるのはレシピのオーナーのみ。
        $this->authorize('update', $recipe);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'cooked_on' => ['nullable', 'date'],
        ], [], [
            'body' => 'コメント',
            'cooked_on' => '作った日',
        ]);

        $recipe->cookingLogs()->create($data + ['user_id' => $recipe->user_id]);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('status', '「作ってみた」コメントを追加しました。');
    }

    public function destroy(Request $request, CookingLog $cookingLog): RedirectResponse
    {
        // 自分のメモのみ削除可。
        abort_unless($cookingLog->user_id === $request->user()->id, 403);

        $recipeId = $cookingLog->recipe_id;
        $cookingLog->delete();

        return redirect()
            ->route('recipes.show', $recipeId)
            ->with('status', 'コメントを削除しました。');
    }
}
