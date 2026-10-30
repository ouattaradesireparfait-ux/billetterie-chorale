@extends('layouts.app')

@section('content')
    <section class="card login-card">
        <h1>Connexion organisateur</h1>

        <form method="post" action="{{ route('admin.login.submit') }}">
            @csrf

            <label>
                Mot de passe
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="button">Connexion</button>
        </form>
    </section>
@endsection