<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')->orderBy('id')->chunkById(200, function ($transactions) {
            foreach ($transactions as $transaction) {
                $ouguiya = (float) ($transaction->ouguiya ?? 0);
                $fcfa = (float) ($transaction->fcfa ?? 0);
                $rate = $ouguiya > 0 && $fcfa > 0 ? round($ouguiya / $fcfa, 4) : null;
                $purchaseRate = null;
                $gain = 0;

                if ($transaction->type_operation === 'vente' && (float) ($transaction->taux_achat ?? 0) > 0) {
                    $purchaseRate = round(1 / (float) $transaction->taux_achat, 4);
                    $gain = round(($fcfa * $purchaseRate) - $ouguiya, 2);
                }

                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'rate' => $rate,
                        'taux_achat' => $purchaseRate,
                        'gain' => $gain,
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('transactions')->orderBy('id')->chunkById(200, function ($transactions) {
            foreach ($transactions as $transaction) {
                $ouguiya = (float) ($transaction->ouguiya ?? 0);
                $fcfa = (float) ($transaction->fcfa ?? 0);
                $rate = $ouguiya > 0 && $fcfa > 0 ? round($fcfa / $ouguiya, 4) : null;
                $purchaseRate = null;
                $gain = 0;

                if ($transaction->type_operation === 'vente' && (float) ($transaction->taux_achat ?? 0) > 0) {
                    $purchaseRate = round(1 / (float) $transaction->taux_achat, 4);
                    $gain = $rate === null ? 0 : round(($rate - $purchaseRate) * $ouguiya, 2);
                }

                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'rate' => $rate,
                        'taux_achat' => $purchaseRate,
                        'gain' => $gain,
                    ]);
            }
        });
    }
};
