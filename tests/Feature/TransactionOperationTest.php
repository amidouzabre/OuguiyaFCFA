<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionOperationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ougouiya_purchase_increases_both_balances_and_calculates_oug_over_fcfa_rate(): void
    {
        $client = Client::create(['name' => 'Test client']);
        $this->actingAs(User::factory()->create());

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-07',
            'type_operation' => 'achat',
            'ouguiya' => 700000,
            'fcfa' => 900000,
        ])->assertRedirect();

        $transaction = Transaction::firstOrFail();
        $client->refresh();

        $this->assertSame('0.7778', $transaction->rate);
        $this->assertSame('0.00', $transaction->gain);
        $this->assertSame('700000.00', $client->solde_ouguiya);
        $this->assertSame('900000.00', $client->solde_fcfa);
    }

    public function test_fcfa_repayment_decreases_the_fcfa_balance(): void
    {
        $client = Client::create(['name' => 'Test client']);
        $this->actingAs(User::factory()->create());

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-06',
            'type_operation' => 'achat',
            'ouguiya' => 700000,
            'fcfa' => 900000,
        ])->assertRedirect();

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-07',
            'type_operation' => 'remboursement_fcfa',
            'fcfa' => 250000,
        ])->assertRedirect();

        $repayment = Transaction::where('type_operation', 'remboursement_fcfa')->firstOrFail();
        $client->refresh();

        $this->assertSame('0.00', $repayment->fcfa);
        $this->assertSame('250000.00', $repayment->payement_fcfa);
        $this->assertSame('650000.00', $client->solde_fcfa);
        $this->assertSame('700000.00', $client->solde_ouguiya);
    }

    public function test_ougouiya_sale_records_a_signed_profit_in_ougouiya(): void
    {
        $client = Client::create(['name' => 'Test client']);
        $this->actingAs(User::factory()->create());

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-06',
            'type_operation' => 'achat',
            'ouguiya' => 5000,
            'fcfa' => 7000,
        ])->assertRedirect();

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-06',
            'type_operation' => 'achat',
            'ouguiya' => 6000,
            'fcfa' => 8000,
        ])->assertRedirect();

        $purchases = Transaction::where('type_operation', 'achat')->orderBy('id')->get();
        $firstPurchase = $purchases[0];
        $secondPurchase = $purchases[1];

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-07',
            'type_operation' => 'vente',
            'purchase_source_id' => $firstPurchase->id,
            'ouguiya' => 5100,
        ])->assertRedirect();

        $this->getJson("/clients/{$client->id}/purchase-transactions")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $secondPurchase->id);

        $this->from('/transactions')->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-08',
            'type_operation' => 'vente',
            'purchase_source_id' => $firstPurchase->id,
            'ouguiya' => 5200,
        ])->assertRedirect('/transactions')->assertSessionHasErrors('purchase_source_id');

        $this->post('/transactions', [
            'client_id' => $client->id,
            'date_operation' => '2026-11-08',
            'type_operation' => 'vente',
            'purchase_source_id' => $secondPurchase->id,
            'ouguiya' => 5900,
        ])->assertRedirect();

        $sales = Transaction::where('type_operation', 'vente')->orderBy('id')->get();
        $profit = $sales[0];
        $loss = $sales[1];
        $client->refresh();

        $this->assertSame($firstPurchase->id, $profit->purchase_source_id);
        $this->assertSame('0.00', $profit->fcfa);
        $this->assertSame('5000.00', $profit->payement_ouguiya);
        $this->assertNull($profit->rate);
        $this->assertSame('100.00', $profit->gain);
        $this->assertSame('-100.00', $loss->gain);
        $this->assertSame('6000.00', $loss->payement_ouguiya);
        $this->assertSame('0.00', $client->solde_ouguiya);
        $this->assertSame('15000.00', $client->solde_fcfa);
    }
}
