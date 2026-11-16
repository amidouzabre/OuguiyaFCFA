<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->input('client_id');
        $type = $request->input('type');
        $period = $request->input('period', 'all');
        $search = $request->input('search');

        $query = Transaction::with('client:id,name,telephone');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($type && $type !== 'all') {
            $query->where('type_operation', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($period === 'today') {
            $query->whereDate('date_operation', Carbon::today());
        } elseif ($period === 'this_week') {
            $query->whereBetween('date_operation', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereBetween('date_operation', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }

        $transactions = $query->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(30)
            ->withQueryString();

        $allClients = Client::select('id', 'name', 'solde_ouguiya', 'solde_fcfa')
            ->orderBy('name')
            ->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'clients' => $allClients,
            'filters' => [
                'client_id' => $clientId,
                'type' => $type,
                'period' => $period,
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedTransactionData($request, true);

        DB::transaction(function () use ($validated, $request) {
            if ($validated['type_operation'] === 'vente') {
                $purchase = $this->assertPurchaseAvailable($validated['purchase_source_id'], $validated['client_id']);
                $validated['payement_ouguiya'] = (float) $purchase->ouguiya;
                $validated['gain'] = round((float) $validated['ouguiya'] - (float) $purchase->ouguiya, 2);
            }

            Transaction::create([
                ...$validated,
                'created_by' => $request->user()?->id,
            ]);
        });

        return back()->with('success', 'Opération enregistrée avec succès.');
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $this->validatedTransactionData($request, false, $transaction);
        unset($validated['client_id']);

        DB::transaction(function () use ($validated, $transaction) {
            if ($transaction->type_operation === 'achat'
                && $transaction->sales()->exists()
                && $validated['type_operation'] !== 'achat') {
                throw ValidationException::withMessages([
                    'type_operation' => 'Un achat déjà vendu ne peut pas devenir un autre type d’opération.',
                ]);
            }

            if ($validated['type_operation'] === 'vente') {
                $purchase = $this->assertPurchaseAvailable(
                    $validated['purchase_source_id'],
                    $transaction->client_id,
                    $transaction->id
                );
                $validated['payement_ouguiya'] = (float) $purchase->ouguiya;
                $validated['gain'] = round((float) $validated['ouguiya'] - (float) $purchase->ouguiya, 2);
            }

            $transaction->update($validated);
        });

        return back()->with('success', 'Opération mise à jour avec succès.');
    }

    /**
     * Validate the operation-specific input and normalize it to the ledger fields.
     *
     * @return array<string, mixed>
     */
    private function validatedTransactionData(
        Request $request,
        bool $includeClientId = false,
        ?Transaction $transaction = null
    ): array {
        $clientId = $request->input('client_id', $transaction?->client_id);
        $validated = $request->validate([
            'client_id' => [$includeClientId ? 'required' : 'sometimes', 'exists:clients,id'],
            'date_operation' => ['required', 'date'],
            'type_operation' => ['required', 'in:achat,vente,remboursement_fcfa,remboursement_ouguiya,depot,autre'],
            'description' => ['nullable', 'string', 'max:500'],
            'ouguiya' => ['required_if:type_operation,achat,vente', 'nullable', 'numeric', 'min:0.01'],
            'fcfa' => ['required_if:type_operation,achat,remboursement_fcfa', 'nullable', 'numeric', 'min:0.01'],
            'payement_ouguiya' => ['nullable', 'numeric', 'min:0'],
            'payement_fcfa' => ['nullable', 'numeric', 'min:0'],
            'purchase_source_id' => [
                'required_if:type_operation,vente',
                'nullable',
                Rule::exists('transactions', 'id')
                    ->where('client_id', $clientId)
                    ->where('type_operation', 'achat'),
            ],
        ]);

        $type = $validated['type_operation'];
        $ouguiya = (float) ($validated['ouguiya'] ?? 0);
        $fcfa = (float) ($validated['fcfa'] ?? 0);
        $rate = $ouguiya > 0 && $fcfa > 0 ? round($ouguiya / $fcfa, 4) : null;

        $data = [
            'date_operation' => $validated['date_operation'],
            'type_operation' => $type,
            'description' => $validated['description'] ?? null,
            'ouguiya' => $ouguiya,
            'payement_ouguiya' => 0,
            'fcfa' => $fcfa,
            'payement_fcfa' => 0,
            'rate' => $rate,
            'taux_achat' => null,
            'gain' => 0,
            'purchase_source_id' => null,
        ];

        if ($includeClientId) {
            $data['client_id'] = $validated['client_id'];
        }

        if ($type === 'remboursement_fcfa') {
            $data['fcfa'] = 0;
            $data['payement_fcfa'] = $fcfa;
        } elseif ($type === 'vente') {
            $purchase = Transaction::query()->whereKey($validated['purchase_source_id'])->firstOrFail();
            $data['fcfa'] = 0;
            $data['rate'] = null;
            $data['payement_ouguiya'] = (float) $purchase->ouguiya;
            $data['purchase_source_id'] = $purchase->id;
            $data['gain'] = round($ouguiya - (float) $purchase->ouguiya, 2);
        } elseif (in_array($type, ['autre', 'depot', 'remboursement_ouguiya'], true)) {
            $data['payement_ouguiya'] = (float) ($validated['payement_ouguiya'] ?? 0);
            $data['payement_fcfa'] = (float) ($validated['payement_fcfa'] ?? 0);
        }

        return $data;
    }

    public function availablePurchases(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'exclude_transaction_id' => [
                'nullable',
                Rule::exists('transactions', 'id')
                    ->where('client_id', $client->id)
                    ->where('type_operation', 'vente'),
            ],
        ]);
        $excludedSaleId = $validated['exclude_transaction_id'] ?? null;

        $purchases = Transaction::query()
            ->where('client_id', $client->id)
            ->where('type_operation', 'achat')
            ->whereDoesntHave('sales', function (Builder $query) use ($excludedSaleId) {
                if ($excludedSaleId) {
                    $query->where('id', '!=', $excludedSaleId);
                }
            })
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get(['id', 'date_operation', 'numero_operation', 'ouguiya']);

        return response()->json($purchases);
    }

    private function assertPurchaseAvailable(int $purchaseId, int $clientId, ?int $exceptSaleId = null): Transaction
    {
        $purchase = Transaction::query()->lockForUpdate()->findOrFail($purchaseId);
        $isValidPurchase = $purchase->client_id === $clientId && $purchase->type_operation === 'achat';
        $alreadySold = Transaction::query()
            ->where('purchase_source_id', $purchaseId)
            ->when($exceptSaleId, fn (Builder $query) => $query->where('id', '!=', $exceptSaleId))
            ->exists();

        if (! $isValidPurchase || $alreadySold) {
            throw ValidationException::withMessages([
                'purchase_source_id' => 'Cet achat n’est plus disponible pour une vente.',
            ]);
        }

        return $purchase;
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        if ($transaction->type_operation === 'achat' && $transaction->sales()->exists()) {
            throw ValidationException::withMessages([
                'transaction' => 'Cet achat ne peut pas être supprimé car il est lié à une vente.',
            ]);
        }

        $transaction->delete();

        return back()->with('success', 'Opération supprimée avec succès.');
    }

    public function receipt(Transaction $transaction): Response
    {
        $transaction->load(['client', 'creator', 'purchaseSource']);

        return Inertia::render('Transactions/Receipt', [
            'transaction' => $transaction,
        ]);
    }
}
