@extends('layouts.app')

@section('title', 'レシピ登録')

@section('content')
<hgroup>
    <h1>レシピ登録</h1>
    <p>許可されたレシピサイトのURLを貼り付けると、タイトル・画像・本文を自動取得します。</p>
</hgroup>

<form method="POST" action="{{ route('recipes.store') }}">
    @csrf
    <label>
        レシピURL
        <input type="url" name="url" value="{{ old('url') }}" required autofocus
               placeholder="https://cookpad.com/..."
               aria-invalid="@error('url') true @enderror">
        @error('url')
            <small style="color: var(--pico-del-color);">{{ $message }}</small>
        @enderror
    </label>

    <label>
        タグ（カンマ区切り・任意）
        <input type="text" name="tags" value="{{ old('tags') }}" placeholder="和食, 簡単, 鶏肉">
        @error('tags')
            <small style="color: var(--pico-del-color);">{{ $message }}</small>
        @enderror
    </label>

    <button type="submit">登録する</button>
    <a href="{{ route('recipes.index') }}" role="button" class="secondary outline">キャンセル</a>
</form>
@endsection
