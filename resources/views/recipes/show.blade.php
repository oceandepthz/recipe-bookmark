@extends('layouts.app')

@section('title', $recipe->title)

@section('content')
<p class="meta">
    <a href="{{ route('recipes.index') }}">← 一覧へ戻る</a>
</p>

<article>
    <header>
        <h1 style="margin-bottom:.3rem;">{{ $recipe->title }}</h1>
        <p class="meta">
            {{ $recipe->site_name ?: $recipe->domain }} ・
            <a href="{{ $recipe->url }}" target="_blank" rel="noopener">元のページを開く ↗</a>
        </p>
        @if ($recipe->tags->isNotEmpty())
            <p>
                @foreach ($recipe->tags as $tag)
                    <a href="{{ route('recipes.index', ['tag' => $tag->name]) }}" class="tag-chip">{{ $tag->name }}</a>
                @endforeach
            </p>
        @endif
        <p>
            <a href="{{ route('recipes.edit', $recipe) }}" role="button" class="outline">編集</a>
        </p>
    </header>

    @if ($recipe->image_url)
        <img src="{{ $recipe->image_url }}" alt="" style="max-width:100%; border-radius: var(--pico-border-radius);">
    @endif

    @if ($recipe->excerpt)
        <blockquote>{{ $recipe->excerpt }}</blockquote>
    @endif

    @if ($recipe->content_html)
        <div class="recipe-content">
            {{-- 保存時にもサニタイズ済みだが、表示時にも HTMLPurifier を通して多層防御する --}}
            {!! clean($recipe->content_html) !!}
        </div>
    @else
        <p class="meta">本文が取得できませんでした。<a href="{{ route('recipes.edit', $recipe) }}">編集画面</a>で本文を貼り付けるか、再取得できます。</p>
    @endif
</article>

<section>
    <h2>🍳 作ってみたメモ</h2>

    @forelse ($recipe->cookingLogs as $log)
        <article>
            <header class="meta">
                @if ($log->cooked_on)
                    作った日: {{ $log->cooked_on->format('Y/m/d') }}
                @else
                    {{ $log->created_at->format('Y/m/d') }}
                @endif
                <form method="POST" action="{{ route('cooking-logs.destroy', $log) }}"
                      class="nav-form" style="float:right;"
                      onsubmit="return confirm('このコメントを削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="secondary outline">削除</button>
                </form>
            </header>
            <p style="white-space:pre-wrap; margin:0;">{{ $log->body }}</p>
        </article>
    @empty
        <p class="meta">まだメモがありません。作ってみた感想を記録しましょう。</p>
    @endforelse

    <form method="POST" action="{{ route('recipes.logs.store', $recipe) }}">
        @csrf
        <label>
            コメント
            <textarea name="body" rows="3" required placeholder="味付けは少し濃いめが好み。次回は砂糖控えめで。"
                      aria-invalid="@error('body') true @enderror"></textarea>
            @error('body')<small style="color: var(--pico-del-color);">{{ $message }}</small>@enderror
        </label>
        <label>
            作った日（任意）
            <input type="date" name="cooked_on" value="{{ old('cooked_on') }}">
        </label>
        <button type="submit">メモを追加</button>
    </form>
</section>
@endsection
