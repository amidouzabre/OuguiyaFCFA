<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Award,
    BadgePercent,
    Calendar,
    CircleDollarSign,
    DollarSign,
    Filter,
    Receipt,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatFcfa, formatOuguiya, formatShortDate } from '@/lib/formatters';
import type { Client, Transaction } from '@/types';

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    clients: Array<Pick<Client, 'id' | 'name'>>;
    metrics: {
        total_gains: number;
        gains_this_month: number;
        gains_this_week: number;
        gains_today: number;
    };
    gains_by_client: Array<{
        client_id: number;
        total_gain: number;
        total_ouguiya: number;
        operations_count: number;
        client?: Client;
    }>;
    monthly_gains: Array<{
        month: string;
        label: string;
        gain: number;
    }>;
    filters: {
        client_id?: string | number | null;
        period?: string | null;
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
                title: 'Marges & Gains',
                href: '/gains',
            },
        ],
    },
});

const selectedClient = ref(props.filters.client_id || '');
const selectedPeriod = ref(props.filters.period || 'all');

function applyFilters() {
    router.get(
        '/gains',
        {
            client_id: selectedClient.value || undefined,
            period:
                selectedPeriod.value !== 'all'
                    ? selectedPeriod.value
                    : undefined,
        },
        { preserveState: true, replace: true },
    );
}

// Find max monthly profit for proportional bar visualization
const maxMonthlyGain = Math.max(
    ...props.monthly_gains.map((m) => m.gain),
    1000,
);
</script>

<template>
    <Head title="Marges & Bénéfices - OuguiyaCFA" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <!-- Top header -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    Marges & Bénéfices de Change
                    <span
                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        Module Rentabilité
                    </span>
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Analyse des profits générés par l'écart entre taux d'achat
                    et taux de vente d'Ouguiya.
                </p>
            </div>
        </div>

        <!-- 4 Profit KPIs -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-emerald-500/10 shadow-sm"
            >
                <CardHeader class="space-y-0 px-4 pt-4 pb-1">
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Bénéfices Totaux
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div
                        class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        {{ formatOuguiya(metrics.total_gains) }}
                    </div>
                    <p class="mt-1 text-[11px] text-muted-foreground">
                        Cumul historique réalisé
                    </p>
                </CardContent>
            </Card>

            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-emerald-500/5 shadow-sm"
            >
                <CardHeader class="space-y-0 px-4 pt-4 pb-1">
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Ce Mois-ci
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-4 pt-0">
                    <div
                        class="text-2xl font-black text-emerald-700 dark:text-emerald-300"
                    >
                        {{ formatOuguiya(metrics.gains_this_month) }}
                    </div>
                    <p
                        class="mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        Gains du mois en cours
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                >
                    Cette Semaine
                </div>
                <div class="mt-1 text-2xl font-black text-foreground">
                    {{ formatOuguiya(metrics.gains_this_week) }}
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    7 derniers jours
                </p>
            </Card>

            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                >
                    Aujourd'hui
                </div>
                <div class="mt-1 text-2xl font-black text-foreground">
                    {{ formatOuguiya(metrics.gains_today) }}
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Journée en cours
                </p>
            </Card>
        </div>

        <!-- Middle section: Monthly Profit Chart & Top Profitable Clients -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- 12-Month Profit Evolution Chart (2 cols) -->
            <Card class="border-border/70 shadow-sm lg:col-span-2">
                <CardHeader class="border-b bg-muted/20 pb-3">
                    <CardTitle
                        class="flex items-center gap-2 text-base font-bold"
                    >
                        <TrendingUp
                            class="size-4.5 text-emerald-600 dark:text-emerald-400"
                        />
                        Évolution des Bénéfices Mensuels (12 Derniers Mois)
                    </CardTitle>
                    <CardDescription>
                        Visualisation des bénéfices réalisés mois après mois.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6">
                    <div
                        class="grid h-44 grid-cols-4 items-end gap-2 pt-4 pb-2 sm:grid-cols-6 lg:grid-cols-12"
                    >
                        <div
                            v-for="m in monthly_gains"
                            :key="m.month"
                            class="group flex h-full flex-col items-center justify-end gap-1.5"
                        >
                            <span
                                class="text-[10px] font-bold whitespace-nowrap text-emerald-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-emerald-400"
                            >
                                {{ formatOuguiya(m.gain) }}
                            </span>
                            <div
                                class="relative w-full max-w-[28px] rounded-t-md bg-emerald-500/80 transition-all duration-300 group-hover:scale-105 hover:bg-emerald-600"
                                :style="{
                                    height: `${Math.max(8, (m.gain / maxMonthlyGain) * 100)}%`,
                                }"
                            ></div>
                            <span
                                class="w-full truncate text-center text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ m.label }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Clients by Profit Generated (1 col) -->
            <Card class="border-border/70 shadow-sm">
                <CardHeader class="border-b bg-muted/20 pb-3">
                    <CardTitle
                        class="flex items-center gap-2 text-base font-bold"
                    >
                        <Award class="size-4.5 text-amber-500" />
                        Clients les Plus Rentables
                    </CardTitle>
                    <CardDescription>
                        Clients générant le plus de marge.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div
                        v-if="gains_by_client.length === 0"
                        class="p-6 text-center text-xs text-muted-foreground"
                    >
                        Aucune donnée disponible.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div
                            v-for="(item, idx) in gains_by_client"
                            :key="item.client_id"
                            class="flex items-center justify-between p-3.5 transition-colors hover:bg-muted/40"
                        >
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="flex size-6 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground"
                                >
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <Link
                                        v-if="item.client"
                                        :href="`/clients/${item.client_id}`"
                                        class="text-xs font-bold text-foreground transition-colors hover:text-emerald-600"
                                    >
                                        {{ item.client.name }}
                                    </Link>
                                    <div
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        {{ item.operations_count }} opération(s)
                                        •
                                        {{ formatOuguiya(item.total_ouguiya) }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div
                                    class="text-xs font-black"
                                    :class="
                                        item.total_gain >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ item.total_gain > 0 ? '+' : ''
                                    }}{{ formatOuguiya(item.total_gain) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter bar for profitable operations -->
        <Card class="border-border/70 p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <select
                        v-model="selectedClient"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs"
                    >
                        <option value="">Tous les clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="selectedPeriod"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs"
                    >
                        <option value="all">Toutes les dates</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="this_week">Cette semaine</option>
                        <option value="this_month">Ce mois-ci</option>
                    </select>
                </div>
            </div>
        </Card>

        <!-- Profitable Operations Table -->
        <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader class="border-b bg-muted/20 pb-3">
                <CardTitle class="flex items-center gap-2 text-base font-bold">
                    <BadgePercent
                        class="size-4.5 text-emerald-600 dark:text-emerald-400"
                    />
                    Détail des Opérations Ayant Généré des Gains
                </CardTitle>
                <CardDescription>
                    Liste des transactions de change avec taux d'achat, taux de
                    vente et marge réalisée.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="border-b bg-muted/50 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="p-3 pl-4">Date</th>
                                <th class="p-3">Client</th>
                                <th class="p-3">Libellé</th>
                                <th class="p-3 text-right">Achat source</th>
                                <th class="p-3 text-right">Montant vendu</th>
                                <th class="p-3 text-right">Gain / Perte</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-if="transactions.data.length === 0">
                                <td
                                    colspan="6"
                                    class="p-8 text-center text-muted-foreground"
                                >
                                    Aucune opération lucrative trouvée avec ces
                                    critères.
                                </td>
                            </tr>
                            <tr
                                v-for="t in transactions.data"
                                :key="t.id"
                                class="transition-colors hover:bg-muted/40"
                            >
                                <td
                                    class="p-3 pl-4 font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatShortDate(t.date_operation) }}
                                </td>
                                <td
                                    class="p-3 font-bold whitespace-nowrap text-foreground"
                                >
                                    <Link
                                        v-if="t.client"
                                        :href="`/clients/${t.client.id}`"
                                        class="transition-colors hover:text-emerald-600"
                                    >
                                        {{ t.client.name }}
                                    </Link>
                                </td>
                                <td
                                    class="max-w-[200px] truncate p-3 text-muted-foreground"
                                >
                                    {{ t.description || '-' }}
                                </td>
                                <td
                                    class="p-3 text-right font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    <span v-if="t.purchase_source">
                                        N°{{
                                            t.purchase_source.numero_operation
                                        }}
                                        ·
                                        {{
                                            formatOuguiya(
                                                t.purchase_source.ouguiya,
                                            )
                                        }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td
                                    class="p-3 text-right font-medium whitespace-nowrap text-foreground"
                                >
                                    {{ formatOuguiya(t.ouguiya) }}
                                </td>
                                <td
                                    class="p-3 text-right font-bold whitespace-nowrap"
                                    :class="
                                        Number(t.gain) >= 0
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ Number(t.gain) > 0 ? '+' : ''
                                    }}{{ formatOuguiya(t.gain) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="transactions.last_page > 1"
                    class="flex items-center justify-between border-t p-4 text-xs text-muted-foreground"
                >
                    <div>
                        Page {{ transactions.current_page }} sur
                        {{ transactions.last_page }} ({{ transactions.total }}
                        résultats)
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'rounded px-2.5 py-1 text-xs transition-colors',
                                link.active
                                    ? 'bg-emerald-600 font-bold text-white'
                                    : 'text-muted-foreground hover:bg-muted',
                                !link.url
                                    ? 'cursor-not-allowed opacity-40'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
