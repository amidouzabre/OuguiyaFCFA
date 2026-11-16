<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpDown,
    ArrowUpRight,
    Calendar,
    Download,
    Filter,
    Pencil,
    Plus,
    Receipt,
    RotateCcw,
    Search,
    Trash2,
} from '@lucide/vue';
import TransactionModal from '@/components/TransactionModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatFcfa, formatOuguiya, formatRate, formatShortDate } from '@/lib/formatters';
import type { Client, Transaction } from '@/types';

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    clients: Array<Pick<Client, 'id' | 'name' | 'solde_ouguiya' | 'solde_fcfa'>>;
    filters: {
        client_id?: string | number | null;
        type?: string | null;
        period?: string | null;
        search?: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: '/dashboard',
            },
            {
                title: 'Opérations',
                href: '/transactions',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedClient = ref(props.filters.client_id || '');
const selectedType = ref(props.filters.type || '');
const selectedPeriod = ref(props.filters.period || 'all');

// Modals
const isTransactionModalOpen = ref(false);
const editingTransaction = ref<Transaction | null>(null);

function openCreateTransaction() {
    editingTransaction.value = null;
    isTransactionModalOpen.value = true;
}

function openEditTransaction(tx: Transaction) {
    editingTransaction.value = tx;
    isTransactionModalOpen.value = true;
}

function deleteTransaction(tx: Transaction) {
    if (confirm(`Supprimer l'opération n°${tx.numero_operation} du ${formatShortDate(tx.date_operation)} ? Tous les soldes cumulés suivants seront recalculés automatiquement.`)) {
        router.delete(`/transactions/${tx.id}`);
    }
}

function applyFilters() {
    router.get(
        '/transactions',
        {
            search: searchQuery.value || undefined,
            client_id: selectedClient.value || undefined,
            type: selectedType.value || undefined,
            period: selectedPeriod.value !== 'all' ? selectedPeriod.value : undefined,
        },
        { preserveState: true, replace: true }
    );
}

let timeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        applyFilters();
    }, 300);
});
</script>

<template>
    <Head title="Journal des Opérations - OuguiyaCFA" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
        <!-- Top header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-5">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-foreground flex items-center gap-2">
                    Journal des Opérations
                    <span class="rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-0.5 text-xs font-semibold">
                        {{ transactions.total }} opération(s)
                    </span>
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Historique chronologique global de toutes les transactions de change, achats, ventes et règlements.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 self-start sm:self-auto">
                <a href="/transactions/export">
                    <Button variant="outline" class="gap-1.5 text-xs font-semibold">
                        <Download class="size-4" />
                        Exporter Excel
                    </Button>
                </a>
                <Button
                    @click="openCreateTransaction"
                    class="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm text-xs font-semibold"
                >
                    <Plus class="size-4" />
                    Nouvelle Opération
                </Button>
            </div>
        </div>

        <!-- Filters Section -->
        <Card class="border-border/70 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search text -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Recherche libellé ou client..."
                        class="pl-9 text-xs"
                    />
                </div>

                <!-- Client filter -->
                <div>
                    <select
                        v-model="selectedClient"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">Tous les clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                </div>

                <!-- Type filter -->
                <div>
                    <select
                        v-model="selectedType"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">Tous les types</option>
                        <option value="vente">Vente Ouguiya</option>
                        <option value="achat">Achat Ouguiya</option>
                        <option value="remboursement_fcfa">Règlement FCFA</option>
                        <option value="autre">Autre opération</option>
                    </select>
                </div>

                <!-- Period filter -->
                <div>
                    <select
                        v-model="selectedPeriod"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="all">Toutes les dates</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="this_week">Cette semaine</option>
                        <option value="this_month">Ce mois-ci</option>
                    </select>
                </div>
            </div>
        </Card>

        <!-- Transactions Table -->
        <Card class="border-border/70 shadow-sm overflow-hidden">
            <CardContent class="p-0">
                <div v-if="transactions.data.length === 0" class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground">
                    <Receipt class="size-12 text-muted-foreground/50 mb-3" />
                    <p class="font-bold text-foreground text-base">Aucune opération trouvée</p>
                    <p class="text-xs text-muted-foreground mt-1 max-w-sm">
                        Modifiez vos filtres ou enregistrez votre première opération de change.
                    </p>
                    <Button variant="outline" size="sm" class="mt-4 gap-1.5" @click="openCreateTransaction">
                        <Plus class="size-4" />
                        Nouvelle Opération
                    </Button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-muted/50 text-muted-foreground font-semibold border-b uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="p-3 pl-4">Date</th>
                                <th class="p-3">Client</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Libellé</th>
                                <th class="p-3 text-right">Ouguiya (MRU)</th>
                                <th class="p-3 text-right">Taux MRU/XOF</th>
                                <th class="p-3 text-right">FCFA (XOF)</th>
                                <th class="p-3 text-right">Paiement FCFA</th>
                                <th class="p-3 text-right font-black">Gain</th>
                                <th class="p-3 text-center pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="t in transactions.data"
                                :key="t.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <td class="p-3 pl-4 font-medium text-muted-foreground whitespace-nowrap">
                                    {{ formatShortDate(t.date_operation) }}
                                </td>
                                <td class="p-3 font-bold text-foreground whitespace-nowrap">
                                    <Link v-if="t.client" :href="`/clients/${t.client.id}`" class="hover:text-emerald-600 transition-colors">
                                        {{ t.client.name }}
                                    </Link>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span
                                        v-if="t.type_operation === 'vente'"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        <ArrowUpRight class="size-3" /> Vente
                                    </span>
                                    <span
                                        v-else-if="t.type_operation === 'achat'"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                    >
                                        <ArrowDownLeft class="size-3" /> Achat
                                    </span>
                                    <span
                                        v-else-if="t.type_operation === 'remboursement_fcfa'"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        <RotateCcw class="size-3" /> Règl. FCFA
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-muted text-muted-foreground"
                                    >
                                        Autre
                                    </span>
                                </td>
                                <td class="p-3 text-muted-foreground max-w-[220px] truncate">
                                    {{ t.description || '-' }}
                                </td>
                                <td class="p-3 text-right font-medium whitespace-nowrap">
                                    {{ Number(t.ouguiya) > 0 ? formatOuguiya(t.ouguiya) : '-' }}
                                </td>
                                <td class="p-3 text-right font-medium whitespace-nowrap">
                                    {{ formatRate(t.rate) }}
                                </td>
                                <td class="p-3 text-right font-semibold whitespace-nowrap">
                                    {{ Number(t.fcfa) > 0 ? formatFcfa(t.fcfa) : '-' }}
                                </td>
                                <td class="p-3 text-right font-bold text-emerald-700 dark:text-emerald-400 whitespace-nowrap">
                                    {{ Number(t.payement_fcfa) > 0 ? formatFcfa(t.payement_fcfa) : '-' }}
                                </td>
                                <td class="p-3 text-right font-black text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    {{ Number(t.gain) !== 0 ? `${Number(t.gain) > 0 ? '+' : ''}${formatOuguiya(t.gain)}` : '-' }}
                                </td>
                                <td class="p-3 text-center pr-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <Link :href="`/transactions/${t.id}/receipt`" title="Voir le reçu">
                                            <Button variant="ghost" size="sm" class="h-7 px-2 text-[11px]">
                                                Reçu
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-muted-foreground hover:text-emerald-600"
                                            title="Modifier"
                                            @click="openEditTransaction(t)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                            title="Supprimer"
                                            @click="deleteTransaction(t)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.last_page > 1" class="flex items-center justify-between p-4 border-t text-xs text-muted-foreground">
                    <div>
                        Page {{ transactions.current_page }} sur {{ transactions.last_page }} ({{ transactions.total }} résultats)
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-2.5 py-1 rounded text-xs transition-colors',
                                link.active ? 'bg-emerald-600 text-white font-bold' : 'hover:bg-muted text-muted-foreground',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Modals -->
    <TransactionModal
        v-model:open="isTransactionModalOpen"
        :clients="clients"
        :transaction="editingTransaction"
    />
</template>
