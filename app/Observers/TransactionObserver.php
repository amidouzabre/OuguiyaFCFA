<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\BalanceCalculationService;

class TransactionObserver
{
    public function __construct(
        protected BalanceCalculationService $balanceService
    ) {}

    public function created(Transaction $transaction): void
    {
        $this->balanceService->recalculateForClient($transaction->client_id);
    }

    public function updated(Transaction $transaction): void
    {
        if ($transaction->type_operation === 'achat' && $transaction->wasChanged('ouguiya')) {
            foreach ($transaction->sales as $sale) {
                $sale->payement_ouguiya = $transaction->ouguiya;
                $sale->gain = round((float) $sale->ouguiya - (float) $transaction->ouguiya, 2);
                $sale->saveQuietly();
            }
        }

        $this->balanceService->recalculateForClient($transaction->client_id);

        if ($transaction->wasChanged('client_id')) {
            $oldClientId = $transaction->getOriginal('client_id');
            if ($oldClientId) {
                $this->balanceService->recalculateForClient($oldClientId);
            }
        }
    }

    public function deleted(Transaction $transaction): void
    {
        $this->balanceService->recalculateForClient($transaction->client_id);
    }
}
