<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/session/csrf-token', [SessionController::class, 'csrfToken'])
    ->middleware('throttle:60,1')
    ->name('session.csrf');

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/commandes', [PublicController::class, 'order'])
    ->middleware('throttle:10,1')
    ->name('orders.store');
Route::get('/paiement', [PublicController::class, 'paymentProofForm'])
    ->name('orders.payment');
Route::post('/paiement/preuve', [PublicController::class, 'submitPaymentProof'])
    ->middleware('throttle:5,1')
    ->name('orders.payment.submit');
Route::get('/commande/{reference}', [PublicController::class, 'confirmation'])
    ->name('orders.confirmation');
Route::post('/commande/{reference}/preuve-paiement', [PublicController::class, 'uploadPaymentProof'])
    ->middleware('throttle:5,1')
    ->name('orders.payment-proof');
Route::get('/b/{token}', [PublicController::class, 'ticket'])
    ->name('tickets.short');
Route::get('/billet/{token}', [PublicController::class, 'ticket'])
    ->name('tickets.show');

Route::get('/admin/connexion', [AdminController::class, 'login'])
    ->name('admin.login');
Route::post('/admin/connexion', [AdminController::class, 'authenticate'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('/deconnexion', [AdminController::class, 'logout'])
            ->name('logout');
        Route::get('/', [AdminController::class, 'dashboard'])
            ->name('dashboard');
        Route::post('/ventes', [AdminController::class, 'sale'])
            ->name('sales.store');
        Route::delete('/billets/{ticket}', [AdminController::class, 'destroyTicket'])
            ->name('tickets.destroy');
        Route::post('/commandes/{order}/confirmer', [AdminController::class, 'paid'])
            ->name('orders.paid');
        Route::post('/commandes/{order}/annuler', [AdminController::class, 'cancel'])
            ->name('orders.cancel');
        Route::get('/commandes/{order}/preuve-paiement', [AdminController::class, 'paymentProof'])
            ->name('orders.payment-proof');
        Route::get('/scanner', [AdminController::class, 'scanner'])
            ->name('scanner');
        Route::post('/scanner/valider', [AdminController::class, 'scan'])
            ->name('scanner.validate');
        Route::post('/scanner/marquer', [AdminController::class, 'markAsUsed'])
            ->name('scanner.mark-used');
    });