<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeft,
    ArrowUpRight,
    Calendar,
    ChevronDown,
    DollarSign,
    FileText,
    MessageCircle,
    Pencil,
    Plus,
    Printer,
    Receipt,
    RotateCcw,
    Search,
    Trash2,
    TrendingUp,
    Users,
    Wallet,
} from '@lucide/vue';
import ClientModal from '@/components/ClientModal.vue';
import TransactionModal from '@/components/TransactionModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    formatFcfa,
    formatOuguiya,
    formatRate,
    formatShortDate,
} from '@/lib/formatters';
import { getWhatsAppUrl as createWhatsAppUrl } from '@/lib/whatsapp';
import type { Client, Transaction } from '@/types';

const props = defineProps<{
    client: Client & { transactions: Transaction[] };
    totals: {
        total_ouguiya: number;
        total_payement_ouguiya: number;
        total_fcfa: number;
        total_payement_fcfa: number;
        total_gain: number;
        count: number;
    };
    other_clients: Array<
        Pick<Client, 'id' | 'name' | 'solde_ouguiya' | 'solde_fcfa'>
    >;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: '/dashboard',
            },
            {
                title: 'Comptes Clients',
                href: '/clients',
            },
            {
                title: 'Relevé de Compte',
                href: '#',
            },
        ],
    },
});

// Modals
const isTransactionModalOpen = ref(false);
const editingTransaction = ref<Transaction | null>(null);

const isClientModalOpen = ref(false);

function openAddTransaction() {
    editingTransaction.value = null;
    isTransactionModalOpen.value = true;
}

function openEditTransaction(tx: Transaction) {
    editingTransaction.value = tx;
    isTransactionModalOpen.value = true;
}

function deleteTransaction(tx: Transaction) {
    if (
        confirm(
            `Supprimer l'opération n°${tx.numero_operation} du ${formatShortDate(tx.date_operation)} ? Tous les soldes cumulés suivants seront recalculés automatiquement.`,
        )
    ) {
        router.delete(`/transactions/${tx.id}`);
    }
}

// Filters on history table
const searchQuery = ref('');
const typeFilter = ref('all');

const filteredTransactions = computed(() => {
    let list = props.client.transactions || [];

    if (typeFilter.value !== 'all') {
        list = list.filter((t) => t.type_operation === typeFilter.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter((t) => {
            return (
                (t.description && t.description.toLowerCase().includes(q)) ||
                (t.date_operation && t.date_operation.includes(q)) ||
                String(t.numero_operation).includes(q)
            );
        });
    }

    return list;
});

function getWhatsAppUrl(
    phone?: string | null,
    clientName = '',
    soldeFcfa = '',
) {
    if (!phone) return '#';
    const message = `Bonjour ${clientName}, voici l'état de votre compte Ouguiya/FCFA. Votre solde FCFA actuel est de ${soldeFcfa}. Merci de nous contacter pour convenir du règlement. Bien à vous.`;
    return createWhatsAppUrl(phone, message);
}

function switchClient(e: Event) {
    const target = e.target as HTMLSelectElement;
    if (target.value) {
        router.visit(`/clients/${target.value}`);
    }
}
</script>

<template>
    <Head :title="`Relevé de Compte - ${client.name} - OuguiyaCFA`" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <!-- Top Navigation & Client Switcher -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3">
                <Link href="/clients">
                    <Button variant="outline" size="sm" class="h-9 gap-1 px-3">
                        <ArrowLeft class="size-4" />
                        <span class="hidden sm:inline">Tous les clients</span>
                    </Button>
                </Link>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="text-2xl font-black tracking-tight text-foreground"
                        >
                            {{ client.name }}
                        </h1>
                        <span
                            class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground"
                        >
                            Compte N° {{ client.id }}
                        </span>
                    </div>
                    <div
                        class="mt-0.5 flex items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span
                            v-if="client.telephone"
                            class="flex items-center gap-1"
                        >
                            📞 {{ client.telephone }}
                        </span>
                        <span
                            v-if="client.address"
                            class="flex items-center gap-1"
                        >
                            📍 {{ client.address }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Client Quick Switcher & Action buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Quick switch client dropdown -->
                <div class="relative">
                    <select
                        @change="switchClient"
                        class="h-9 rounded-lg border border-input bg-background pr-8 pl-2.5 text-xs font-medium focus:ring-2 focus:ring-ring"
                    >
                        <option :value="client.id" selected>
                            {{ client.name }} (Actuel)
                        </option>
                        <option
                            v-for="other in other_clients.filter(
                                (c) => c.id !== client.id,
                            )"
                            :key="other.id"
                            :value="other.id"
                        >
                            {{ other.name }}
                        </option>
                    </select>
                </div>

                <a
                    v-if="client.telephone"
                    :href="
                        getWhatsAppUrl(
                            client.telephone,
                            client.name,
                            formatFcfa(client.solde_fcfa),
                        )
                    "
                    target="_blank"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-100 px-3 text-xs font-bold text-emerald-800 shadow-sm transition-colors hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    <MessageCircle class="size-4" />
                    <span class="hidden sm:inline">WhatsApp</span>
                </a>

                <Link :href="`/clients/${client.id}/statement`">
                    <Button
                        variant="outline"
                        class="h-9 gap-1.5 text-xs font-semibold shadow-sm"
                    >
                        <Printer class="size-4" />
                        <span class="hidden sm:inline">Imprimer Relevé A4</span>
                    </Button>
                </Link>

                <Button
                    variant="outline"
                    class="h-9 px-2.5 text-xs"
                    title="Modifier la fiche client"
                    @click="isClientModalOpen = true"
                >
                    <Pencil class="size-4" />
                </Button>

                <Button
                    @click="openAddTransaction"
                    class="h-9 gap-1.5 bg-emerald-600 text-xs font-bold text-white shadow-sm hover:bg-emerald-700"
                >
                    <Plus class="size-4" />
                    Nouvelle Opération
                </Button>
            </div>
        </div>

        <!-- Notes if any -->
        <div
            v-if="client.notes"
            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300"
        >
            <span class="text-[10px] font-bold tracking-wider uppercase"
                >Note :</span
            >
            <span>{{ client.notes }}</span>
        </div>

        <!-- 4 Financial Summary Cards for this Client -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Solde FCFA -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-amber-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Solde Actuel FCFA (XOF)
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    >
                        <Wallet class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-black"
                        :class="
                            Number(client.solde_fcfa) > 0
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-emerald-600 dark:text-emerald-400'
                        "
                    >
                        {{ formatFcfa(client.solde_fcfa) }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-muted-foreground">
                        <span
                            v-if="Number(client.solde_fcfa) > 0"
                            class="font-bold text-amber-700 dark:text-amber-400"
                        >
                            ⚠️ Le client vous doit cette somme
                        </span>
                        <span
                            v-else-if="Number(client.solde_fcfa) < 0"
                            class="font-bold text-sky-700 dark:text-sky-400"
                        >
                            ℹ️ Avoir en faveur du client
                        </span>
                        <span
                            v-else
                            class="font-bold text-emerald-700 dark:text-emerald-400"
                        >
                            ✅ Compte FCFA entièrement soldé
                        </span>
                    </p>
                </CardContent>
            </Card>

            <!-- Solde Ouguiya -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-sky-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Solde Actuel Ouguiya (MRU)
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                    >
                        <DollarSign class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-black text-sky-600 dark:text-sky-400"
                    >
                        {{ formatOuguiya(client.solde_ouguiya) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Solde cumulé en devise Ouguiya
                    </p>
                </CardContent>
            </Card>

            <!-- Total Volume FCFA -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-purple-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Volume Total Opérations
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                    >
                        <Users class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-black text-foreground">
                        {{ formatFcfa(totals.total_fcfa) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Sur {{ totals.count }} opération(s) enregistrée(s)
                    </p>
                </CardContent>
            </Card>

            <!-- Bénéfices / Gains avec ce client -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-emerald-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Marge & Bénéfices Réalisés
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <TrendingUp class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        {{ formatOuguiya(totals.total_gain) }}
                    </div>
                    <p
                        class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                    >
                        Gains générés sur les reventes
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Filter bar for transactions -->
        <div
            class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Filtrer les opérations (libellé, date...)"
                    class="pl-9 text-xs"
                />
            </div>

            <div class="flex items-center gap-2">
                <select
                    v-model="typeFilter"
                    class="h-9 rounded-lg border border-input bg-background px-3 text-xs font-medium focus:ring-2 focus:ring-ring"
                >
                    <option value="all">Tous les types d'opérations</option>
                    <option value="vente">Vente Ouguiya</option>
                    <option value="achat">Achat Ouguiya</option>
                    <option value="remboursement_fcfa">Règlement FCFA</option>
                    <option value="autre">Autre / Solde initial</option>
                </select>
            </div>
        </div>

        <!-- Interactive Excel-style Ledger Table -->
        <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader
                class="flex flex-row items-center justify-between border-b bg-muted/20 pb-3"
            >
                <div>
                    <CardTitle
                        class="flex items-center gap-2 text-base font-bold"
                    >
                        <FileText
                            class="size-4.5 text-emerald-600 dark:text-emerald-400"
                        />
                        Relevé Détaillé des Opérations (Compte Client)
                    </CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Historique complet chronologique avec soldes cumulés
                        ligne par ligne.
                    </p>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <!-- Grouped table header -->
                        <thead
                            class="border-b bg-muted/80 text-[11px] font-bold tracking-wider text-foreground uppercase"
                        >
                            <tr>
                                <th
                                    class="w-12 border-r border-border/50 p-2.5 pl-3 text-center"
                                >
                                    N°
                                </th>
                                <th
                                    class="border-r border-border/50 p-2.5 whitespace-nowrap"
                                >
                                    Date
                                </th>
                                <th class="border-r border-border/50 p-2.5">
                                    Type / Libellé
                                </th>
                                <th
                                    class="border-r border-border/50 bg-sky-500/10 p-2.5 text-right text-sky-900 dark:text-sky-300"
                                >
                                    Ouguiya (MRU)
                                </th>
                                <th
                                    class="border-r border-border/50 bg-sky-500/10 p-2.5 text-right text-sky-900 dark:text-sky-300"
                                >
                                    Payement MRU
                                </th>
                                <th
                                    class="border-r border-border/50 bg-sky-500/20 p-2.5 text-right font-extrabold text-sky-950 dark:text-sky-200"
                                >
                                    Solde MRU
                                </th>
                                <th
                                    class="border-r border-border/50 p-2.5 text-right"
                                >
                                    Taux MRU/XOF
                                </th>
                                <th
                                    class="border-r border-border/50 bg-amber-500/10 p-2.5 text-right text-amber-900 dark:text-amber-300"
                                >
                                    FCFA (XOF)
                                </th>
                                <th
                                    class="border-r border-border/50 bg-amber-500/10 p-2.5 text-right text-amber-900 dark:text-amber-300"
                                >
                                    Payement FCFA
                                </th>
                                <th
                                    class="border-r border-border/50 bg-amber-500/20 p-2.5 text-right font-extrabold text-amber-950 dark:text-amber-200"
                                >
                                    Solde FCFA
                                </th>
                                <th
                                    class="border-r border-border/50 p-2.5 text-right text-emerald-700 dark:text-emerald-400"
                                >
                                    Gain
                                </th>
                                <th class="w-28 p-2.5 pr-3 text-center">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border/60">
                            <tr v-if="filteredTransactions.length === 0">
                                <td
                                    colspan="12"
                                    class="p-8 text-center text-muted-foreground"
                                >
                                    Aucune opération enregistrée pour ce client.
                                </td>
                            </tr>

                            <tr
                                v-for="t in filteredTransactions"
                                :key="t.id"
                                class="transition-colors hover:bg-muted/40"
                            >
                                <td
                                    class="border-r border-border/50 p-2.5 pl-3 text-center font-bold text-muted-foreground"
                                >
                                    {{ t.numero_operation }}
                                </td>

                                <td
                                    class="border-r border-border/50 p-2.5 font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatShortDate(t.date_operation) }}
                                </td>

                                <td
                                    class="max-w-[220px] border-r border-border/50 p-2.5"
                                >
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <span
                                            v-if="t.type_operation === 'vente'"
                                            class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                        >
                                            <ArrowUpRight class="size-2.5" />
                                            Vente
                                        </span>
                                        <span
                                            v-else-if="
                                                t.type_operation === 'achat'
                                            "
                                            class="inline-flex items-center gap-1 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                        >
                                            <ArrowDownLeft class="size-2.5" />
                                            Achat
                                        </span>
                                        <span
                                            v-else-if="
                                                t.type_operation ===
                                                'remboursement_fcfa'
                                            "
                                            class="inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                        >
                                            <RotateCcw class="size-2.5" /> Règl.
                                            FCFA
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded bg-muted px-1.5 py-0.5 text-[10px] font-bold text-muted-foreground"
                                        >
                                            Autre
                                        </span>
                                        <span
                                            class="truncate font-medium text-foreground"
                                            >{{ t.description || '-' }}</span
                                        >
                                    </div>
                                </td>

                                <!-- Ouguiya -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/5 p-2.5 text-right font-semibold whitespace-nowrap text-foreground"
                                >
                                    {{
                                        Number(t.ouguiya) > 0
                                            ? formatOuguiya(t.ouguiya)
                                            : '-'
                                    }}
                                </td>

                                <!-- Payement Ouguiya -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/5 p-2.5 text-right font-semibold whitespace-nowrap text-sky-700 dark:text-sky-400"
                                >
                                    {{
                                        Number(t.payement_ouguiya) > 0
                                            ? formatOuguiya(t.payement_ouguiya)
                                            : '-'
                                    }}
                                </td>

                                <!-- Solde Ouguiya Ligne -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/10 p-2.5 text-right font-black whitespace-nowrap text-sky-900 dark:text-sky-300"
                                >
                                    {{ formatOuguiya(t.solde_ouguiya_ligne) }}
                                </td>

                                <!-- Taux -->
                                <td
                                    class="border-r border-border/50 p-2.5 text-right font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatRate(t.rate) }}
                                </td>

                                <!-- FCFA -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/5 p-2.5 text-right font-semibold whitespace-nowrap text-foreground"
                                >
                                    {{
                                        Number(t.fcfa) > 0
                                            ? formatFcfa(t.fcfa)
                                            : '-'
                                    }}
                                </td>

                                <!-- Payement FCFA -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/5 p-2.5 text-right font-bold whitespace-nowrap text-emerald-700 dark:text-emerald-400"
                                >
                                    {{
                                        Number(t.payement_fcfa) > 0
                                            ? formatFcfa(t.payement_fcfa)
                                            : '-'
                                    }}
                                </td>

                                <!-- Solde FCFA Ligne -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/10 p-2.5 text-right font-black whitespace-nowrap text-amber-900 dark:text-amber-300"
                                >
                                    {{ formatFcfa(t.solde_fcfa_ligne) }}
                                </td>

                                <!-- Gain -->
                                <td
                                    class="border-r border-border/50 p-2.5 text-right font-bold whitespace-nowrap text-emerald-600 dark:text-emerald-400"
                                >
                                    {{
                                        Number(t.gain) !== 0
                                            ? `${Number(t.gain) > 0 ? '+' : ''}${formatOuguiya(t.gain)}`
                                            : '-'
                                    }}
                                </td>

                                <!-- Actions -->
                                <td
                                    class="p-2.5 pr-3 text-center whitespace-nowrap"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <Link
                                            :href="`/transactions/${t.id}/receipt`"
                                            title="Imprimer le reçu"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-6 w-6 p-0 text-muted-foreground hover:text-foreground"
                                            >
                                                <Receipt class="size-3.5" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0 text-muted-foreground hover:text-emerald-600"
                                            title="Modifier cette opération"
                                            @click="openEditTransaction(t)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0 text-destructive hover:bg-destructive/10"
                                            title="Supprimer cette opération"
                                            @click="deleteTransaction(t)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Bottom Summary Row (Excel Sums) -->
                        <tfoot
                            class="border-t-2 border-border bg-muted text-xs font-bold text-foreground"
                        >
                            <tr>
                                <td
                                    colspan="3"
                                    class="border-r border-border/50 p-3 pl-3 text-right text-[11px] font-black tracking-wider uppercase"
                                >
                                    Totaux Généraux :
                                </td>
                                <!-- Total Ouguiya -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/10 p-3 text-right font-black whitespace-nowrap text-sky-950 dark:text-sky-200"
                                >
                                    {{ formatOuguiya(totals.total_ouguiya) }}
                                </td>
                                <!-- Total Payements Ouguiya -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/10 p-3 text-right font-black whitespace-nowrap text-sky-700 dark:text-sky-300"
                                >
                                    {{
                                        formatOuguiya(
                                            totals.total_payement_ouguiya,
                                        )
                                    }}
                                </td>
                                <!-- Solde Final Ouguiya -->
                                <td
                                    class="border-r border-border/50 bg-sky-500/20 p-3 text-right font-black whitespace-nowrap text-sky-950 dark:text-sky-200"
                                >
                                    {{ formatOuguiya(client.solde_ouguiya) }}
                                </td>
                                <td
                                    class="border-r border-border/50 p-3 text-center text-muted-foreground"
                                >
                                    -
                                </td>
                                <!-- Total FCFA -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/10 p-3 text-right font-black whitespace-nowrap text-amber-950 dark:text-amber-200"
                                >
                                    {{ formatFcfa(totals.total_fcfa) }}
                                </td>
                                <!-- Total Payements FCFA -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/10 p-3 text-right font-black whitespace-nowrap text-emerald-800 dark:text-emerald-300"
                                >
                                    {{ formatFcfa(totals.total_payement_fcfa) }}
                                </td>
                                <!-- Solde Final FCFA -->
                                <td
                                    class="border-r border-border/50 bg-amber-500/20 p-3 text-right font-black whitespace-nowrap text-amber-950 dark:text-amber-200"
                                >
                                    {{ formatFcfa(client.solde_fcfa) }}
                                </td>
                                <!-- Total Gains -->
                                <td
                                    class="border-r border-border/50 p-3 text-right font-black whitespace-nowrap text-emerald-700 dark:text-emerald-400"
                                >
                                    {{ formatOuguiya(totals.total_gain) }}
                                </td>
                                <td class="p-3 text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Modals -->
    <TransactionModal
        v-model:open="isTransactionModalOpen"
        :clients="[client, ...other_clients]"
        :default-client-id="client.id"
        :transaction="editingTransaction"
    />

    <ClientModal v-model:open="isClientModalOpen" :client="client" />
</template>
