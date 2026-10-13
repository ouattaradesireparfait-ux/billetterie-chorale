@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Contrôle d’entrée</h1>
        <p>
            Le scan dans cette application vérifie le billet et enregistre l’entrée.
            L’ouverture du lien avec l’appareil photo du téléphone affiche seulement
            le statut en ligne et ne marque pas le billet comme scanné.
        </p>

        <div id="reader"></div>

        <form id="manual">
            <label>
                Ou saisir le jeton ou coller le lien du billet
                <input id="token" autocomplete="off" required>
            </label>

            <button class="button">Vérifier</button>
        </form>

                <p id="result" role="status" aria-live="polite"></p>
        <button id="scan-next" class="button scan-next" type="button" hidden>
            Scanner le billet suivant
        </button>
    </section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('assets/js/scanner.js') }}"></script>
@endpush