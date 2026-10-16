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
                    maxlength="32"
                    pattern="(?:VIP|STD)-[0-9]{4,}"
                    placeholder="VIP-0001 ou STD-0001"
                    title="Format attendu : VIP-0001 ou STD-0001"
                    spellcheck="false"
                    required
                >
                <button id="verify-ticket" class="button" type="submit">
                    Vérifier
                </button>
            </div>
            <small>Format accepté : VIP-0001 ou STD-0001.</small>
        </form>
    </section>

    <dialog id="scan-result-dialog" class="scan-dialog" aria-labelledby="scan-dialog-title">
        <div class="scan-dialog__content">
            <span class="scanner-eyebrow">Contrôle des billets</span>
            <h2 id="scan-dialog-title">Statut du billet</h2>
            <p id="result" class="scan-result scan-result--checking" role="status" aria-live="polite"></p>

            <div id="scanner-actions" class="scanner-actions scanner-actions--single">
                <button id="mark-used" class="button" type="button" hidden disabled>
                    Marquer comme déjà scanné
                </button>
                <button id="new-scan" class="button button-secondary" type="button">
                    Nouveau scan
                </button>
            </div>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('assets/js/scanner.js') }}?v={{ filemtime(public_path('assets/js/scanner.js')) }}"></script>
@endpush