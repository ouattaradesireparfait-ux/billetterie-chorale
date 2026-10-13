@extends('layouts.app')

@section('content')
    <section class="card scanner-card">
        <h1>Contrôle d’entrée</h1>
        <p>
            Le scan vérifie le billet sans le modifier. Appuyez sur le bouton après vérification
            pour enregistrer l’entrée.
        </p>

        <div id="reader" aria-label="Scanner QR avec la caméra arrière"></div>

        <form id="manual-ticket" class="scanner-manual" autocomplete="off">
            <label for="ticket-number">
                Ou saisir le numéro du billet
                <input
                    id="ticket-number"
                    name="ticket_number"
                    type="text"
                    inputmode="text"
                    autocapitalize="characters"
                    maxlength="12"
                    minlength="12"
                    pattern="CH-[0-9]{6}-[0-9]{2}"
                    placeholder="CH-000001-01"
                    title="Format attendu : CH-000001-01"
                    spellcheck="false"
                    required
                >
                <button class="button" type="submit">Vérifier</button>
            </label>
        </form>

        <p id="result" class="scan-result" role="status" aria-live="polite">
            Démarrage de la caméra arrière…
        </p>

        <button id="mark-used" class="button" type="button" disabled>
            Marquer comme déjà scanné
        </button>
    </section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('assets/js/scanner.js') }}"></script>
@endpush