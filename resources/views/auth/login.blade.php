@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
<article style="max-width: 420px; margin: 2rem auto;">
    <hgroup>
        <h1>ログイン</h1>
        <p>{{ config('app.name') }}</p>
    </hgroup>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>
            メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   aria-invalid="@error('email') true @enderror">
        </label>
        <label>
            パスワード
            <input type="password" name="password" required>
        </label>
        <label>
            <input type="checkbox" name="remember"> ログイン状態を保持する
        </label>

        @error('email')
            <small style="color: var(--pico-del-color);">{{ $message }}</small>
        @enderror

        <button type="submit">ログイン</button>
    </form>
</article>
@endsection
