<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Recipe;
use App\Models\Tag;
use App\Services\RecipeFetcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $tag = $request->string('tag')->trim()->toString();
        $q = $request->string('q')->trim()->toString();

        $recipes = Recipe::query()
            ->with('tags')
            ->withCount('cookingLogs')
            ->when($tag !== '', fn ($query) => $query->whereHas('tags', fn ($t) => $t->where('name', $tag)))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%")
                    ->orWhere('site_name', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $tags = Tag::query()->orderBy('name')->get();

        return view('recipes.index', [
            'recipes' => $recipes,
            'tags' => $tags,
            'activeTag' => $tag,
            'searchQuery' => $q,
        ]);
    }

    public function create(): View
    {
        return view('recipes.create');
    }

    public function store(StoreRecipeRequest $request, RecipeFetcher $fetcher): RedirectResponse
    {
        $url = (string) $request->validated('url');
        $fetched = $fetcher->fetch($url);

        $recipe = Recipe::create([
            'url' => $url,
            'domain' => strtolower((string) parse_url($url, PHP_URL_HOST)),
            'title' => $fetched['title'] ?: $url,
            'site_name' => $fetched['site_name'],
            'image_url' => $fetched['image_url'],
            'excerpt' => $fetched['excerpt'],
            'content_html' => $fetched['content_html'],
        ]);

        $this->syncTags($recipe, $request->input('tags'));

        $message = $fetched['content_html']
            ? 'レシピを登録しました。'
            : 'レシピを登録しました（本文の自動取得に失敗したため、編集画面で補完できます）。';

        return redirect()->route('recipes.show', $recipe)->with('status', $message);
    }

    public function show(Recipe $recipe): View
    {
        $recipe->load('tags', 'cookingLogs');

        return view('recipes.show', ['recipe' => $recipe]);
    }

    public function edit(Recipe $recipe): View
    {
        $recipe->load('tags');

        return view('recipes.edit', [
            'recipe' => $recipe,
            'tagsValue' => $recipe->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe, RecipeFetcher $fetcher): RedirectResponse
    {
        $recipe->fill($request->only(['title', 'excerpt', 'image_url', 'content_html']));

        if ($request->boolean('refetch')) {
            $fetched = $fetcher->fetch($recipe->url);
            foreach (['site_name', 'image_url', 'excerpt', 'content_html'] as $key) {
                if (! empty($fetched[$key])) {
                    $recipe->{$key} = $fetched[$key];
                }
            }
        }

        $recipe->save();
        $this->syncTags($recipe, $request->input('tags'));

        return redirect()->route('recipes.show', $recipe)->with('status', 'レシピを更新しました。');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()->route('recipes.index')->with('status', 'レシピを削除しました。');
    }

    /**
     * カンマ区切りのタグ文字列をタグへ同期する。
     */
    private function syncTags(Recipe $recipe, ?string $tagsInput): void
    {
        $ids = collect(explode(',', (string) $tagsInput))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name): int => Tag::firstOrCreate(['name' => $name])->id)
            ->all();

        $recipe->tags()->sync($ids);
    }
}
