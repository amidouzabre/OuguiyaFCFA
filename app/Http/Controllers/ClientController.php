<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $query = Client::query()->withCount('transactions');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($filter === 'debiteurs_fcfa') {
            $query->where('solde_fcfa', '>', 0);
        } elseif ($filter === 'debiteurs_mru') {
            $query->where('solde_ouguiya', '>', 0);
        } elseif ($filter === 'soldes') {
            $query->where('solde_fcfa', 0)->where('solde_ouguiya', 0);
        } elseif ($filter === 'crediteurs') {
            $query->where('solde_fcfa', '<', 0)->orWhere('solde_ouguiya', '<', 0);
        }

        $clients = $query->orderBy('name')->get();

        $stats = [
            'total_clients' => Client::count(),
            'total_debiteurs_fcfa' => Client::where('solde_fcfa', '>', 0)->count(),
            'somme_creances_fcfa' => (float) Client::where('solde_fcfa', '>', 0)->sum('solde_fcfa'),
            'somme_creances_mru' => (float) Client::where('solde_ouguiya', '>', 0)->sum('solde_ouguiya'),
        ];

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'solde_initial_ouguiya' => ['nullable', 'numeric'],
            'solde_initial_fcfa' => ['nullable', 'numeric'],
        ]);

        $client = Client::create([
            'name' => $validated['name'],
            'telephone' => $validated['telephone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $initialMru = (float) ($validated['solde_initial_ouguiya'] ?? 0);
        $initialFcfa = (float) ($validated['solde_initial_fcfa'] ?? 0);

        if ($initialMru != 0 || $initialFcfa != 0) {
            Transaction::create([
                'client_id' => $client->id,
                'date_operation' => now()->toDateString(),
                'type_operation' => 'autre',
                'description' => 'Solde initial / Report',
                'ouguiya' => $initialMru > 0 ? $initialMru : 0,
                'payement_ouguiya' => $initialMru < 0 ? abs($initialMru) : 0,
                'fcfa' => $initialFcfa > 0 ? $initialFcfa : 0,
                'payement_fcfa' => $initialFcfa < 0 ? abs($initialFcfa) : 0,
                'created_by' => $request->user()?->id,
            ]);
        }

        return redirect()->route('clients.show', $client->id)
            ->with('success', "Le compte client '{$client->name}' a été créé avec succès.");
    }

    public function show(Client $client): Response
    {
        $client->load(['transactions' => function ($q) {
            $q->orderBy('date_operation', 'asc')->orderBy('id', 'asc');
        }]);

        // Totals for client history
        $totals = [
            'total_ouguiya' => (float) $client->transactions->sum('ouguiya'),
            'total_payement_ouguiya' => (float) $client->transactions->sum('payement_ouguiya'),
            'total_fcfa' => (float) $client->transactions->sum('fcfa'),
            'total_payement_fcfa' => (float) $client->transactions->sum('payement_fcfa'),
            'total_gain' => (float) $client->transactions->where('type_operation', 'vente')->sum('gain'),
            'count' => $client->transactions->count(),
        ];

        // All other clients for quick jump
        $otherClients = Client::select('id', 'name', 'solde_ouguiya', 'solde_fcfa')
            ->orderBy('name')
            ->get();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'totals' => $totals,
            'other_clients' => $otherClients,
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($validated);

        return back()->with('success', "Informations du client '{$client->name}' mises à jour.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        $name = $client->name;
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', "Le client '{$name}' et son historique ont été supprimés.");
    }

    public function statement(Client $client): Response
    {
        $client->load(['transactions' => function ($q) {
            $q->orderBy('date_operation', 'asc')->orderBy('id', 'asc');
        }]);

        $totals = [
            'total_ouguiya' => (float) $client->transactions->sum('ouguiya'),
            'total_payement_ouguiya' => (float) $client->transactions->sum('payement_ouguiya'),
            'total_fcfa' => (float) $client->transactions->sum('fcfa'),
            'total_payement_fcfa' => (float) $client->transactions->sum('payement_fcfa'),
            'count' => $client->transactions->count(),
        ];

        return Inertia::render('Clients/Statement', [
            'client' => $client,
            'totals' => $totals,
        ]);
    }
}
