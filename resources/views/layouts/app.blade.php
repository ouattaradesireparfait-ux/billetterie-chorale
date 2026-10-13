<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('ticketing.event_name'))</title>
        <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}">
    </head>
    <body>
        <header class="site-header">
            <a class="site-header__brand" href="{{ route('home') }}">
                {{ config('ticketing.event_name') }}
            </a>

            <nav class="site-header__nav" aria-label="Navigation principale">
                <a href="{{ route('home') }}">Accueil</a>
                <a href="{{ route('admin.login') }}">Organisateurs</a>
            </nav>
        </header>

        <main>
            @if (session('success'))
                <div class="notice" role="status">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="error" role="alert">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>

        <footer>
            Concert du Jubilé d'Etain de la chorale Divine Mélodie · Billetterie officielle
        </footer>

        @stack('scripts')
    </body>
</html>