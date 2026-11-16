<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GainController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'has_users' => User::count() > 0,
    ]);
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Clients
    Route::resource('clients', ClientController::class);
    Route::get('clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement');

    // Transactions
    Route::resource('transactions', TransactionController::class)->except(['create', 'show', 'edit']);
    Route::get('transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::get('clients/{client}/purchase-transactions', [TransactionController::class, 'availablePurchases'])
        ->name('clients.purchase-transactions');

    // Gains / Profit analytics module
    Route::get('gains', [GainController::class, 'index'])->name('gains.index');

    // Users management
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
});

require __DIR__.'/settings.php';
