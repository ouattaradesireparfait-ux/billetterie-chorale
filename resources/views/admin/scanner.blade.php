@extends('layouts.app')

@section('content')
    <section class="card scanner-card">
        <div class="scanner-heading">
            <span class="scanner-eyebrow">Contrôle des billets</span>
            <h1>Scanner les billets</h1>
            <p>
                Scannez le QR code avec la caméra arrière, ou saisissez le numéro du billet.
                La vérification ne marque pas le billet comme utilisé.
            </p>
        </div>

        <div id="reader" aria-label="Lecteur de QR code utilisant la caméra arrière"></div>

        <form id="manual-ticket" class="scanner-manual" autocomplete="off">
            <label for="ticket-number">Saisie alternative</label>
            <div class="scanner-manual__controls">
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
                <button id="verify-ticket" class="button" type="submit">
                    Vérifier
                </button>
            </div>
            <small>Format accepté : CH-000001-01</small>
        </form>

        <p id="result" class="scan-result scan-result--checking" role="status" aria-live="polite">
            Démarrage de la caméra arrière…
        </p>

        <div class="scanner-actions">
            <button id="mark-used" class="button" type="button" disabled>
                Marquer comme déjà scanné
            </button>
            <button id="new-scan" class="button button-secondary" type="button" disabled>
                Nouveau scan
            </button>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('assets/js/scanner.js') }}"></script>
@endpush