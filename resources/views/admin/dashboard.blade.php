@extends('layouts.app')

@section('content')
    <h1>Administration</h1>

    <div class="admin-summary" aria-label="Résumé des ventes">
        <div class="admin-summary__item">
            <span>Ventes</span>
            <strong>{{ $paidCount }}</strong>
        </div>
        <div class="admin-summary__item">
            <span>Recettes</span>
            <strong>{{ number_format($paidTotal, 0, ',', ' ') }} FCFA</strong>
        </div>
        <div class="admin-summary__item">
            <span>Entrées</span>
            <strong>{{ $usedCount }}</strong>
        </div>
        <div class="admin-summary__item">
            <span>En attente</span>
            <strong>{{ $pendingCount }}</strong>
        </div>
    </div>

    <div class="admin-actions">
        <a class="button gold" href="{{ route('admin.scanner') }}">Scanner</a>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button>Déconnexion</button>
        </form>
    </div>

    <section class="card admin-sale">
        <h2>Vente sur place</h2>
        <form method="post" action="{{ route('admin.sales.store') }}">
            @csrf

            <div class="grid">
                <label>Nom<input name="buyer_name" required></label>
                <label>E-mail<input type="email" name="buyer_email"></label>
                <label>Téléphone<input name="buyer_phone" required></label>
                <label>Billet
                    <select name="ticket_type">
                        <option value="standard">Standard</option>
                        <option value="vip">VIP</option>
                    </select>
                </label>
                <label>Quantité<input type="number" name="quantity" value="1" min="1" max="10"></label>
                <label>Paiement
                    <select name="payment_method">
                        <option value="orange_money">Orange Money</option>
                        <option value="wave">Wave</option>
                        <option value="momo">Momo</option>
                        <option value="moov_money">Moov Money</option>
                    </select>
                </label>
            </div>

            <button class="button">Émettre les billets</button>
        </form>
    </section>

    <h2>Commandes</h2>
    <div class="table">
        <table>
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Billets</th>
                    <th>Total</th>
                    <th>Paiement</th>
                    <th>Preuve</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td data-label="Commande">
                            {{ $order->reference }}<br>
                            {{ $order->buyer_name }}
                        </td>
                        <td data-label="Billets">
                            {{ $order->ticket_type }} × {{ $order->quantity }}
                        </td>
                        <td data-label="Total">{{ $order->formattedTotal() }}</td>
                        <td data-label="Paiement">{{ $order->paymentMethodLabel() }}</td>
                        <td data-label="Preuve">
                            @if ($order->payment_proof_path)
                                <a
                                    href="{{ route('admin.orders.payment-proof', $order) }}"
                                    target="_blank"
                                    rel="noopener"
                                >Voir la capture</a>
                                <br>
                                <small>Reçue le {{ $order->payment_proof_uploaded_at?->format('d/m/Y à H:i') }}</small>
                            @else
                                En attente
                            @endif
                        </td>
                        <td data-label="Statut">{{ $order->status }}</td>
                        <td data-label="Action">
                            @if ($order->status === 'pending')
                                <form method="post" action="{{ route('admin.orders.paid', $order) }}">
                                    @csrf
                                    <button>Confirmer le paiement</button>
                                </form>
                                <form method="post" action="{{ route('admin.orders.cancel', $order) }}">
                                    @csrf
                                    <button>Annuler</button>
                                </form>
                            @elseif ($order->status === 'paid')
                                @foreach ($order->tickets as $ticket)
                                    <a target="_blank" rel="noopener" href="{{ route('tickets.show', $ticket->token) }}">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
@endsection