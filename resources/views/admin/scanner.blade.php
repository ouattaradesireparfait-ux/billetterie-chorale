@extends('layouts.app')

@section('content')
    <section class="card scanner-card">
        <h1 class="visually-hidden">Scanner les billets</h1>

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
                    maxlength="13"
                    pattern="(?:VIP|STD)-[A-Z0-9]{4}-(?:00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})"
                    placeholder="VIP-A0A1-001"
                    title="Format attendu : VIP-A0A1-001 ou STD-B2C3-002"
                    spellcheck="false"
                    required
                >
                <button id="verify-ticket" class="button" type="submit">
                    Vérifier
                </button>
            </div>
            <small>Format : VIP-A0A1-001 ou STD-B2C3-002.</small>
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