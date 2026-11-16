<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // Balances and financial metrics
        $totalCreancesFcfa = (float) Client::where('solde_fcfa', '>', 0)->sum('solde_fcfa');
        $totalAvoirsFcfa = (float) abs(Client::where('solde_fcfa', '<', 0)->sum('solde_fcfa'));
        $totalSoldeOuguiya = (float) Client::sum('solde_ouguiya');

        $totalGains = (float) Transaction::where('type_operation', 'vente')->sum('gain');
        $gainsThisMonth = (float) Transaction::where('type_operation', 'vente')
            ->where('date_operation', '>=', $startOfMonth)
            ->sum('gain');

        $totalVolumeFcfa = (float) Transaction::sum('fcfa') + (float) Transaction::sum('payement_fcfa');
        $totalVolumeOuguiya = (float) Transaction::sum('ouguiya') + (float) Transaction::sum('payement_ouguiya');

        $totalClients = Client::count();
        $clientsEnDette = Client::where('solde_fcfa', '>', 0)->orWhere('solde_ouguiya', '>', 0)->count();

        // Recent transactions
        $recentTransactions = Transaction::with('client:id,name,telephone')
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Top debtors (clients with outstanding amounts)
        $topDebiteurs = Client::where('solde_fcfa', '>', 0)
            ->orderBy('solde_fcfa', 'desc')
            ->limit(5)
            ->get();

        // Monthly stats for the last 6 months
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();

            $monthGains = (float) Transaction::where('type_operation', 'vente')
                ->whereBetween('date_operation', [$monthStart, $monthEnd])
                ->sum('gain');
            $monthVolumeFcfa = (float) Transaction::whereBetween('date_operation', [$monthStart, $monthEnd])->sum('fcfa');
            $monthVolumeMru = (float) Transaction::whereBetween('date_operation', [$monthStart, $monthEnd])->sum('ouguiya');

            $monthlyStats[] = [
                'month' => $monthDate->translatedFormat('M Y'),
                'label' => $monthDate->format('M'),
                'gain' => $monthGains,
                'volume_fcfa' => $monthVolumeFcfa,
                'volume_mru' => $monthVolumeMru,
            ];
        }

        // Quick clients list for transaction quick-add modal
        $allClients = Client::select('id', 'name', 'telephone', 'solde_ouguiya', 'solde_fcfa')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_creances_fcfa' => $totalCreancesFcfa,
                'total_avoirs_fcfa' => $totalAvoirsFcfa,
                'total_solde_ouguiya' => $totalSoldeOuguiya,
                'total_gains' => $totalGains,
                'gains_this_month' => $gainsThisMonth,
                'total_volume_fcfa' => $totalVolumeFcfa,
                'total_volume_ouguiya' => $totalVolumeOuguiya,
                'total_clients' => $totalClients,
                'clients_en_dette' => $clientsEnDette,
            ],
            'recent_transactions' => $recentTransactions,
            'top_debiteurs' => $topDebiteurs,
            'monthly_stats' => $monthlyStats,
            'all_clients' => $allClients,
        ]);
    }
}
