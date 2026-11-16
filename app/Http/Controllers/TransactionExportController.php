<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $usedSheetNames = [];

            try {
                $clients = Client::query()->orderBy('name')->orderBy('id')->get();

                if ($clients->isEmpty()) {
                    $this->addClientSheet($writer, null, $usedSheetNames);
                } else {
                    foreach ($clients as $index => $client) {
                        $sheet = $index === 0
                            ? $writer->getCurrentSheet()
                            : $writer->addNewSheetAndMakeItCurrent();

                        $sheet->setName($this->sheetName($client, $usedSheetNames));
                        $this->addClientSheetRows($writer, $client);
                    }
                }
            } finally {
                $writer->close();
            }
        }, 'transactions_clients_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, true>  $usedSheetNames
     */
    private function addClientSheet(Writer $writer, ?Client $client, array &$usedSheetNames): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($client ? $this->sheetName($client, $usedSheetNames) : 'Aucun client');
        $this->addClientSheetRows($writer, $client);
    }

    private function addClientSheetRows(Writer $writer, ?Client $client): void
    {
        $clientName = $client->name ?? 'Aucun client';
        $title = 'COMPTE '.$clientName.($client?->telephone ? ' - '.$client->telephone : '');

        $writer->addRow(Row::fromValues([
            $title,
            ...array_fill(0, 14, null),
            'SOLDE OUGUIYA',
            'SOLDE FCFA',
        ]));
        $writer->addRow(Row::fromValues([
            'DATE',
            'OPERATION',
            'TYPE',
            'LIBELLE',
            'OUGUIYA (MRU)',
            'PAYEMENT OUGUIYA (MRU)',
            'FCFA (XOF)',
            'PAYEMENT FCFA (XOF)',
            'TAUX MRU/XOF',
            'TAUX ACHAT MRU/XOF',
            'ACHAT LIÉ',
            'SOLDE OUGUIYA (MRU)',
            'SOLDE FCFA (XOF)',
            'GAIN (MRU)',
            null,
            $client ? (float) $client->solde_ouguiya : null,
            $client ? (float) $client->solde_fcfa : null,
        ]));

        if (! $client) {
            return;
        }

        Transaction::query()
            ->with('purchaseSource:id,numero_operation')
            ->where('client_id', $client->id)
            ->orderBy('date_operation')
            ->orderBy('id')
            ->chunk(500, function ($transactions) use ($writer): void {
                foreach ($transactions as $transaction) {
                    $writer->addRow(Row::fromValues($this->transactionValues($transaction)));
                }
            });
    }

    /**
     * @return list<int|float|string|null>
     */
    private function transactionValues(Transaction $transaction): array
    {
        return [
            Carbon::parse($transaction->date_operation)->format('d/m/Y'),
            $transaction->numero_operation,
            match ($transaction->type_operation) {
                'achat' => 'Achat',
                'vente' => 'Vente',
                'remboursement_fcfa' => 'Règlement FCFA',
                'remboursement_ouguiya' => 'Règlement Ouguiya',
                'depot' => 'Dépôt',
                default => 'Autre',
            },
            $transaction->description,
            $this->numberOrNull($transaction->ouguiya),
            $this->numberOrNull($transaction->payement_ouguiya),
            $this->numberOrNull($transaction->fcfa),
            $this->numberOrNull($transaction->payement_fcfa),
            $this->numberOrNull($transaction->rate),
            $this->numberOrNull($transaction->taux_achat),
            $transaction->purchaseSource?->numero_operation,
            (float) $transaction->solde_ouguiya_ligne,
            (float) $transaction->solde_fcfa_ligne,
            $this->numberOrNull($transaction->gain),
        ];
    }

    private function numberOrNull(float|string|null $value): ?float
    {
        if ($value === null || (float) $value === 0.0) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, true>  $usedSheetNames
     */
    private function sheetName(Client $client, array &$usedSheetNames): string
    {
        $baseName = trim(str_replace(['\\', '/', '?', '*', ':', '[', ']'], '_', $client->name), " '");
        $baseName = $baseName !== '' ? $baseName : 'Client '.$client->id;
        $candidate = mb_substr($baseName, 0, 31);

        if (strcasecmp($candidate, 'History') !== 0 && ! isset($usedSheetNames[mb_strtolower($candidate)])) {
            $usedSheetNames[mb_strtolower($candidate)] = true;

            return $candidate;
        }

        $suffix = '-'.$client->id;
        $candidate = mb_substr($baseName, 0, 31 - mb_strlen($suffix)).$suffix;
        $counter = 1;

        while (strcasecmp($candidate, 'History') === 0 || isset($usedSheetNames[mb_strtolower($candidate)])) {
            $suffix = '-'.$client->id.'-'.$counter++;
            $candidate = mb_substr($baseName, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        $usedSheetNames[mb_strtolower($candidate)] = true;

        return $candidate;
    }
}
