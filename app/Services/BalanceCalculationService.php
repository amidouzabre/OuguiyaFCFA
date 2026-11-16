<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BalanceCalculationService
{
    /**
     * Recalculate all cumulative balances and operation numbers for a given client.
     */
    public function recalculateForClient(int $clientId): void
    {
        DB::transaction(function () use ($clientId) {
            $client = Client::lockForUpdate()->find($clientId);
            if (! $client) {
                return;
            }

            $transactions = Transaction::where('client_id', $clientId)
                ->with('purchaseSource:id,ouguiya')
                ->orderBy('date_operation', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningOuguiya = 0.0;
            $runningFcfa = 0.0;
            $numero = 1;

            foreach ($transactions as $tx) {
                $ouguiya = (float) ($tx->ouguiya ?? 0);
                $payementOuguiya = (float) ($tx->payement_ouguiya ?? 0);
                $fcfa = (float) ($tx->fcfa ?? 0);
                $payementFcfa = (float) ($tx->payement_fcfa ?? 0);

                if ($tx->type_operation === 'vente' && $tx->purchaseSource) {
                    $payementOuguiya = (float) $tx->purchaseSource->ouguiya;
                    $tx->payement_ouguiya = $payementOuguiya;
                }

                // Rate is expressed as Ouguiya per FCFA.
                $rate = $tx->rate;
                if ((! $rate || $rate == 0) && $ouguiya > 0 && $fcfa > 0) {
                    $rate = round($ouguiya / $fcfa, 4);
                    $tx->rate = $rate;
                }

                // A sale removes the source purchase from stock; its resale amount is not a new balance due.
                if ($tx->type_operation === 'vente' && $tx->purchase_source_id) {
                    $runningOuguiya -= $payementOuguiya;
                } else {
                    $runningOuguiya += ($ouguiya - $payementOuguiya);
                }

                $runningFcfa += ($fcfa - $payementFcfa);

                $tx->numero_operation = $numero;
                $tx->solde_ouguiya_ligne = round($runningOuguiya, 2);
                $tx->solde_fcfa_ligne = round($runningFcfa, 2);

                $tx->saveQuietly();
                $numero++;
            }

            $client->solde_ouguiya = round($runningOuguiya, 2);
            $client->solde_fcfa = round($runningFcfa, 2);
            $client->saveQuietly();
        });
    }
}
