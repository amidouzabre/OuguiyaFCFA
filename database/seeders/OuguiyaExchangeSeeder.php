<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OuguiyaExchangeSeeder extends Seeder
{
    public function run(): void
    {
        $balanceService = app(BalanceCalculationService::class);

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@ouguiyacfa.com'],
            [
                'name' => 'Amidou ZABRE',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'telephone' => '0707108448',
                'email_verified_at' => now(),
            ]
        );

        // 1. Client Zabre Amidou
        $client1 = Client::create([
            'name' => 'ZABRE AMIDOU',
            'telephone' => '0707108448',
            'address' => 'Abidjan / Nouakchott',
            'notes' => 'Client principal régulier - Opérations de change et transferts de fonds',
        ]);

        Transaction::create([
            'client_id' => $client1->id,
            'date_operation' => '2026-10-10',
            'type_operation' => 'vente',
            'description' => 'Vente Ouguiya (150 000 MRU à 16.50 FCFA) avec acompte',
            'ouguiya' => 150000,
            'payement_ouguiya' => 0,
            'fcfa' => 2475000,
            'payement_fcfa' => 1000000,
            'rate' => 16.50,
            'taux_achat' => 16.00,
            'gain' => 75000,
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'client_id' => $client1->id,
            'date_operation' => '2026-10-15',
            'type_operation' => 'remboursement_fcfa',
            'description' => 'Règlement par versement bancaire / Wave',
            'ouguiya' => 0,
            'payement_ouguiya' => 0,
            'fcfa' => 0,
            'payement_fcfa' => 1000000,
            'rate' => null,
            'taux_achat' => null,
            'gain' => 0,
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'client_id' => $client1->id,
            'date_operation' => '2026-10-22',
            'type_operation' => 'vente',
            'description' => 'Vente Ouguiya 80 000 MRU à 16.60 FCFA à terme',
            'ouguiya' => 80000,
            'payement_ouguiya' => 0,
            'fcfa' => 1328000,
            'payement_fcfa' => 0,
            'rate' => 16.60,
            'taux_achat' => 16.10,
            'gain' => 40000,
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'client_id' => $client1->id,
            'date_operation' => '2026-10-28',
            'type_operation' => 'remboursement_fcfa',
            'description' => 'Règlement espèces partiel',
            'ouguiya' => 0,
            'payement_ouguiya' => 0,
            'fcfa' => 0,
            'payement_fcfa' => 1500000,
            'rate' => null,
            'taux_achat' => null,
            'gain' => 0,
            'created_by' => $admin->id,
        ]);

        // 2. Client Salam Kabore
        $client2 = Client::create([
            'name' => 'Salam Kaboré',
            'telephone' => '0612345678',
            'address' => 'Ouagadougou / Nouakchott',
            'notes' => 'Grossiste bétail et marchandises diverses',
        ]);

        Transaction::create([
            'client_id' => $client2->id,
            'date_operation' => '2026-10-12',
            'type_operation' => 'vente',
            'description' => 'Vente Ouguiya 120 000 MRU à 16.70 FCFA - Payé comptant',
            'ouguiya' => 120000,
            'payement_ouguiya' => 0,
            'fcfa' => 2004000,
            'payement_fcfa' => 2004000,
            'rate' => 16.70,
            'taux_achat' => 16.20,
            'gain' => 60000,
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'client_id' => $client2->id,
            'date_operation' => '2026-10-25',
            'type_operation' => 'vente',
            'description' => 'Vente Ouguiya 50 000 MRU à 16.65 FCFA - Règlement partiel',
            'ouguiya' => 50000,
            'payement_ouguiya' => 0,
            'fcfa' => 832500,
            'payement_fcfa' => 500000,
            'rate' => 16.65,
            'taux_achat' => 16.15,
            'gain' => 25000,
            'created_by' => $admin->id,
        ]);

        // 3. Client Moussa Ould Cheikh
        $client3 = Client::create([
            'name' => 'Moussa Ould Cheikh',
            'telephone' => '0755443322',
            'address' => 'Nouadhibou',
            'notes' => 'Importateur véhicules & pièces détachées',
        ]);

        Transaction::create([
            'client_id' => $client3->id,
            'date_operation' => '2026-10-18',
            'type_operation' => 'vente',
            'description' => 'Vente Ouguiya 300 000 MRU à 16.55 FCFA',
            'ouguiya' => 300000,
            'payement_ouguiya' => 0,
            'fcfa' => 4965000,
            'payement_fcfa' => 3000000,
            'rate' => 16.55,
            'taux_achat' => 16.05,
            'gain' => 150000,
            'created_by' => $admin->id,
        ]);

        Transaction::create([
            'client_id' => $client3->id,
            'date_operation' => '2026-10-29',
            'type_operation' => 'remboursement_fcfa',
            'description' => 'Règlement par virement Orange Money',
            'ouguiya' => 0,
            'payement_ouguiya' => 0,
            'fcfa' => 0,
            'payement_fcfa' => 1000000,
            'rate' => null,
            'taux_achat' => null,
            'gain' => 0,
            'created_by' => $admin->id,
        ]);

        // 4. Client Fatou Traoré
        $client4 = Client::create([
            'name' => 'Fatou Traoré',
            'telephone' => '0544332211',
            'address' => 'Bamako / Abidjan',
            'notes' => 'Commerce tissus et bijoux',
        ]);

        Transaction::create([
            'client_id' => $client4->id,
            'date_operation' => '2026-10-20',
            'type_operation' => 'vente',
            'description' => 'Vente 40 000 MRU à 16.60 FCFA - Soldé le même jour',
            'ouguiya' => 40000,
            'payement_ouguiya' => 0,
            'fcfa' => 664000,
            'payement_fcfa' => 664000,
            'rate' => 16.60,
            'taux_achat' => 16.10,
            'gain' => 20000,
            'created_by' => $admin->id,
        ]);

        // Ensure calculation service synchronizes every client's balance
        $balanceService->recalculateForClient($client1->id);
        $balanceService->recalculateForClient($client2->id);
        $balanceService->recalculateForClient($client3->id);
        $balanceService->recalculateForClient($client4->id);
    }
}
