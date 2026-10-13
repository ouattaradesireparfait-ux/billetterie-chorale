@extends('layouts.app')

@section('content')
    <section class="hero">
        <div>
            <small>BILLETTERIE OFFICIELLE</small>
            <h1>{{ config('ticketing.event_name') }}</h1>
            <p>{{ config('ticketing.event_subtitle') }}</p>
            <p>
                {{ config('ticketing.event_date') }} · {{ config('ticketing.event_time') }}<br>
                {{ config('ticketing.event_venue') }}
            </p>

            <a class="button gold" href="#tickets">Réserver →</a>
        </div>

        <div class="art">
            <img
                src="{{ asset('assets/images/photo-chorale.jpg') }}"
                alt="Les membres de la chorale Divine Mélodie"
            >
        </div>
    </section>

    <section id="tickets">
        <h2>Billets</h2>

        <div class="prices">
            <article>Standard <b>2 000 FCFA</b></article>
            <article>VIP <b>5 000 FCFA</b></article>
        </div>

        <div class="card">
            <h2>Réserver</h2>
            <p>{{ config('ticketing.payment_instructions') }}</p>

            <form method="post" action="{{ route('orders.store') }}">
                @csrf

                <div class="grid">
                    <label>
                        Nom
                        <input name="buyer_name" value="{{ old('buyer_name') }}" required>
                    </label>

                    <label>
                        E-mail
                        <input name="buyer_email" type="email" value="{{ old('buyer_email') }}" required>
                    </label>

                    <label>
                        Téléphone
                        <input name="buyer_phone" value="{{ old('buyer_phone') }}" required>
                    </label>

                    <label>
                        Billet
                        <select name="ticket_type" required>
                            <option value="standard" @selected(old('ticket_type') === 'standard')>
                                Standard — 2 000 FCFA
                            </option>
                            <option value="vip" @selected(old('ticket_type') === 'vip')>
                                VIP — 5 000 FCFA
                            </option>
                        </select>
                    </label>

                    <label>
                        Quantité
                        <select name="quantity" required>
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" @selected(old('quantity', 1) == $i)>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </label>

                    <label>
                        Paiement
                        <select name="payment_method" required>
                            <option value="orange_money" @selected(old('payment_method', 'orange_money') === 'orange_money')>
                                Orange Money
                            </option>
                            <option value="wave" @selected(old('payment_method') === 'wave')>
                                Wave
                            </option>
                            <option value="momo" @selected(old('payment_method') === 'momo')>
                                Momo
                            </option>
                            <option value="moov_money" @selected(old('payment_method') === 'moov_money')>
                                Moov Money
                            </option>
                        </select>
                    </label>
                </div>

                <button class="button">Continuer</button>
            </form>
        </div>
    </section>
@endsection