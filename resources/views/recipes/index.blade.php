@extends('layouts.app')

@section('title', '登録済レシピ一覧')

@section('content')
<hgroup>
    <h1>登録済レシピ</h1>
    <p>全 {{ $recipes->total() }} 件</p>
</hgroup>

<form method="GET" action="{{ route('recipes.index') }}" role="search">
    <fieldset role="group">
        <input type="search" name="q" value="{{ $searchQuery }}" placeholder="タイトル・概要・サイト名で検索">
        @if ($activeTag !== '')
            <input type="hidden" name="tag" value="{{ $activeTag }}">
        @endif
        <button type="submit">検索</button>
    </fieldset>
</form>

@if ($tags->isNotEmpty())
    <p>
        <a href="{{ route('recipes.index', ['q' => $searchQuery]) }}"
           class="tag-chip @if($activeTag === '') active @endif">すべて</a>
        @foreach ($tags as $tag)
            <a href="{{ route('recipes.index', ['tag' => $tag->name, 'q' => $searchQuery]) }}"
               class="tag-chip @if($activeTag === $tag->name) active @endif">{{ $tag->name }}</a>
        @endforeach
    </p>
@endif

@if ($recipes->isEmpty())
    <article>まだレシピがありません。<a href="{{ route('recipes.create') }}">最初のレシピを登録</a>しましょう。</article>
@else
    <div class="recipe-grid">
        @foreach ($recipes as $recipe)
            <article class="recipe-card">
                <a href="{{ route('recipes.show', $recipe) }}" style="text-decoration:none; color:inherit;">
                    @if ($recipe->image_url)
                        <img src="{{ $recipe->image_url }}" alt="" loading="lazy">
                    @else
                        <div class="thumb-placeholder">🍽️</div>
                    @endif
                    <h3 style="margin:.6rem 0 .2rem; font-size:1.05rem;">{{ $recipe->title }}</h3>
                </a>
                <p class="meta">
                    {{ $recipe->site_name ?: $recipe->domain }}
                    @if ($recipe->cooking_logs_count > 0)
                        ・🍳 {{ $recipe->cooking_logs_count }}
                    @endif
                </p>
                @if ($recipe->tags->isNotEmpty())
                    <p>
                        @foreach ($recipe->tags as $tag)
                            <span class="tag-chip">{{ $tag->name }}</span>
                        @endforeach
                    </p>
                @endif
            </article>
        @endforeach
    </div>

    @if ($recipes->hasPages())
        <nav style="margin-top:1.5rem;">
            <ul style="display:flex; gap:.5rem; list-style:none; padding:0; justify-content:center; align-items:center;">
                <li>
                    @if ($recipes->onFirstPage())
                        <button class="secondary" disabled>前へ</button>
                    @else
                        <a role="button" class="secondary" href="{{ $recipes->previousPageUrl() }}">前へ</a>
                    @endif
                </li>
                <li>{{ $recipes->currentPage() }} / {{ $recipes->lastPage() }}</li>
                <li>
                    @if ($recipes->hasMorePages())
                        <a role="button" class="secondary" href="{{ $recipes->nextPageUrl() }}">次へ</a>
                    @else
                        <button class="secondary" disabled>次へ</button>
                    @endif
                </li>
            </ul>
        </nav>
    @endif
@endif
@endsection
