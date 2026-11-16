<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class TransactionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_export_transactions(): void
    {
        $this->get(route('transactions.export'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_export_every_client_to_a_separate_worksheet(): void
    {
        $this->actingAs(User::factory()->create());

        $client = Client::create([
            'name' => 'Client/Archive',
            'telephone' => '0700000000',
        ]);
        Client::create(['name' => 'Sans opération']);

        Transaction::create([
            'client_id' => $client->id,
            'date_operation' => '2026-11-10',
            'numero_operation' => 7,
            'type_operation' => 'achat',
            'description' => 'Achat test',
            'ouguiya' => 1200,
            'fcfa' => 1500,
            'rate' => 0.8,
            'taux_achat' => 0.75,
            'gain' => 25,
        ]);

        $response = $this->get(route('transactions.export'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');

        $path = tempnam(sys_get_temp_dir(), 'transactions-export-');
        self::assertNotFalse($path);
        file_put_contents($path, $response->streamedContent());

        $reader = new Reader;

        try {
            $reader->open($path);
            $sheets = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                $rows = [];
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }

                $sheets[$sheet->getName()] = $rows;
            }
        } finally {
            $reader->close();
            unlink($path);
        }

        self::assertArrayHasKey('Client_Archive', $sheets);
        self::assertArrayHasKey('Sans opération', $sheets);
        self::assertSame('DATE', $sheets['Client_Archive'][1][0]);
        self::assertSame('Achat test', $sheets['Client_Archive'][2][3]);
        self::assertEquals(1200, $sheets['Client_Archive'][2][4]);
        self::assertEquals(1500, $sheets['Client_Archive'][2][6]);
        self::assertEquals(0.75, $sheets['Client_Archive'][2][9]);
        self::assertSame([], $sheets['Sans opération'][2] ?? []);
    }
}
