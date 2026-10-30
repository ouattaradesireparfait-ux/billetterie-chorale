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

        <main class="{{ request()->routeIs('admin.scanner') ? 'scanner-page' : '' }}">
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

        <script>
            (() => {
                const endpoint = @json(route('session.csrf'));
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                let refreshRequest = null;

                function applyToken(token) {
                    csrfMeta.setAttribute('content', token);
                    document.querySelectorAll('form input[name="_token"]').forEach((input) => {
                        input.value = token;
                    });
                }

                function refreshToken() {
                    if (!refreshRequest) {
                        refreshRequest = fetch(endpoint, {
                            method: 'GET',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: { Accept: 'application/json' },
                        })
                            .then((response) => {
                                if (!response.ok) {
                                    throw new Error('Session refresh failed');
                                }

                                return response.json();
                            })
                            .then((data) => {
                                if (!data.token) {
                                    throw new Error('CSRF token missing');
                                }

                                applyToken(data.token);
                                return data.token;
                            })
                            .finally(() => {
                                refreshRequest = null;
                            });
                    }

                    return refreshRequest;
                }

                window.addEventListener('pageshow', () => {
                    refreshToken().catch(() => {});
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        refreshToken().catch(() => {});
                    }
                });

                document.addEventListener('submit', (event) => {
                    const form = event.target;

                    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') {
                        return;
                    }

                    if (form.dataset.csrfFresh === 'true') {
                        delete form.dataset.csrfFresh;
                        return;
                    }

                    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                        event.preventDefault();
                        return;
                    }

                    event.preventDefault();
                    const submitter = event.submitter;

                    refreshToken()
                        .then(() => {
                            form.dataset.csrfFresh = 'true';

                            if (typeof form.requestSubmit === 'function') {
                                if (submitter) {
                                    form.requestSubmit(submitter);
                                } else {
                                    form.requestSubmit();
                                }
                            } else {
                                form.submit();
                            }
                        })
                        .catch(() => {
                            window.alert('Impossible de renouveler la session. Rechargez la page avant de réessayer.');
                        });
                }, true);
            })();
        </script>

        @stack('scripts')
    </body>
</html>
