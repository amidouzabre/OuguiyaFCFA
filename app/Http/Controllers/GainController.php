<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GainController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        $clientId = $request->input('client_id');
        $period = $request->input('period', 'all');

        $query = Transaction::with([
            'client:id,name,telephone',
            'purchaseSource:id,date_operation,numero_operation,ouguiya',
        ])
            ->where('type_operation', 'vente');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($period === 'today') {
            $query->whereDate('date_operation', Carbon::today());
        } elseif ($period === 'this_week') {
            $query->whereBetween('date_operation', [$startOfWeek, $now->copy()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereBetween('date_operation', [$startOfMonth, $now->copy()->endOfMonth()]);
        }

        $transactions = $query->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        // Key Metrics
        $totalGainsOverall = (float) Transaction::where('type_operation', 'vente')->sum('gain');
        $gainsThisMonth = (float) Transaction::where('type_operation', 'vente')
            ->where('date_operation', '>=', $startOfMonth)
            ->sum('gain');
        $gainsThisWeek = (float) Transaction::where('type_operation', 'vente')
            ->where('date_operation', '>=', $startOfWeek)
            ->sum('gain');
        $gainsToday = (float) Transaction::where('type_operation', 'vente')
            ->whereDate('date_operation', Carbon::today())
            ->sum('gain');

        // Top clients by profit generated
        $gainsByClient = Transaction::selectRaw('client_id, SUM(gain) as total_gain, SUM(ouguiya) as total_ouguiya, COUNT(*) as operations_count')
            ->where('type_operation', 'vente')
            ->groupBy('client_id')
            ->orderByDesc('total_gain')
            ->with('client:id,name,telephone')
            ->limit(10)
            ->get();

        // Monthly evolution of earnings (last 12 months)
        $monthlyGains = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            $monthProfit = (float) Transaction::where('type_operation', 'vente')
                ->whereBetween('date_operation', [$monthStart, $monthEnd])
                ->sum('gain');
            $monthlyGains[] = [
                'month' => $monthDate->translatedFormat('M Y'),
                'label' => $monthDate->format('M'),
                'gain' => $monthProfit,
            ];
        }

        $allClients = Client::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Gains/Index', [
            'transactions' => $transactions,
            'clients' => $allClients,
            'metrics' => [
                'total_gains' => $totalGainsOverall,
                'gains_this_month' => $gainsThisMonth,
                'gains_this_week' => $gainsThisWeek,
                'gains_today' => $gainsToday,
            ],
            'gains_by_client' => $gainsByClient,
            'monthly_gains' => $monthlyGains,
            'filters' => [
                'client_id' => $clientId,
                'period' => $period,
            ],
        ]);
    }
}
