@extends('layouts.app')

@section('title', 'レシピ編集')

@section('content')
<hgroup>
    <h1>レシピ編集</h1>
    <p><a href="{{ $recipe->url }}" target="_blank" rel="noopener">{{ $recipe->url }}</a></p>
</hgroup>

<form method="POST" action="{{ route('recipes.update', $recipe) }}">
    @csrf
    @method('PUT')

    <label>
        タイトル
        <input type="text" name="title" value="{{ old('title', $recipe->title) }}" required
               aria-invalid="@error('title') true @enderror">
        @error('title')<small style="color: var(--pico-del-color);">{{ $message }}</small>@enderror
    </label>

    <label>
        画像URL
        <input type="url" name="image_url" value="{{ old('image_url', $recipe->image_url) }}">
        @error('image_url')<small style="color: var(--pico-del-color);">{{ $message }}</small>@enderror
    </label>

    <label>
        概要
        <textarea name="excerpt" rows="3">{{ old('excerpt', $recipe->excerpt) }}</textarea>
        @error('excerpt')<small style="color: var(--pico-del-color);">{{ $message }}</small>@enderror
    </label>

    <label>
        タグ（カンマ区切り）
        <input type="text" name="tags" value="{{ old('tags', $tagsValue) }}" placeholder="和食, 簡単">
    </label>

    <label>
        本文（HTML）
        <textarea name="content_html" rows="12">{{ old('content_html', $recipe->content_html) }}</textarea>
    </label>

    <label>
        <input type="checkbox" name="refetch" value="1">
        保存時にURLから本文・画像・概要を再取得する（上のフォーム値より優先）
    </label>

    <button type="submit">保存する</button>
    <a href="{{ route('recipes.show', $recipe) }}" role="button" class="secondary outline">キャンセル</a>
</form>

<hr>

<details>
    <summary style="color: var(--pico-del-color);">このレシピを削除する</summary>
    <form method="POST" action="{{ route('recipes.destroy', $recipe) }}"
          onsubmit="return confirm('このレシピを削除しますか？');" style="margin-top:1rem;">
        @csrf
        @method('DELETE')
        <button type="submit" class="contrast">削除する</button>
    </form>
</details>
@endsection
