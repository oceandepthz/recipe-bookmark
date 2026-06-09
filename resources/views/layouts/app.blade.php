<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
        }
        .recipe-card { margin: 0; }
        .recipe-card img {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border-radius: var(--pico-border-radius);
        }
        .recipe-card .thumb-placeholder {
            width: 100%;
            aspect-ratio: 16 / 9;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--pico-card-sectioning-background-color);
            border-radius: var(--pico-border-radius);
            font-size: 2rem;
        }
        .tag-chip {
            display: inline-block;
            padding: 0.1rem 0.55rem;
            margin: 0.1rem 0.15rem 0.1rem 0;
            font-size: 0.8rem;
            border-radius: 1rem;
            background: var(--pico-secondary-background);
            color: var(--pico-secondary-inverse);
            text-decoration: none;
        }
        .tag-chip.active { background: var(--pico-primary-background); color: var(--pico-primary-inverse); }
        .recipe-content :is(img, iframe) { max-width: 100%; height: auto; }
        .nav-form { display: inline; margin: 0; }
        .nav-form button { margin: 0; padding: 0.25rem 0.75rem; }
        .meta { color: var(--pico-muted-color); font-size: 0.85rem; }
    </style>
</head>
<body>
    <nav class="container">
        <ul>
            <li><strong><a href="{{ route('recipes.index') }}" style="text-decoration:none;">🍳 {{ config('app.name') }}</a></strong></li>
        </ul>
        @auth
        <ul>
            <li><a href="{{ route('recipes.create') }}" role="button" class="outline">＋ レシピ登録</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}" class="nav-form">
                    @csrf
                    <button type="submit" class="secondary outline">ログアウト</button>
                </form>
            </li>
        </ul>
        @endauth
    </nav>

    <main class="container">
        @if (session('status'))
            <article style="background: var(--pico-ins-color); color:#fff;">
                {{ session('status') }}
            </article>
        @endif

        @yield('content')
    </main>
</body>
</html>
