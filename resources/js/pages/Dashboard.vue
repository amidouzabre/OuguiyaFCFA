<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowDownLeft,
    ArrowRight,
    ArrowUpRight,
    Calculator,
    CheckCircle2,
    Clock,
    DollarSign,
    MessageCircle,
    Plus,
    RotateCcw,
    TrendingUp,
    UserCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import ClientModal from '@/components/ClientModal.vue';
import TransactionModal from '@/components/TransactionModal.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    formatFcfa,
    formatOuguiya,
    formatRate,
    formatShortDate,
} from '@/lib/formatters';
import { getWhatsAppUrl as createWhatsAppUrl } from '@/lib/whatsapp';
import type {
    Client,
    DashboardMetrics,
    MonthlyStat,
    Transaction,
} from '@/types';

defineProps<{
    metrics: DashboardMetrics;
    recent_transactions: Transaction[];
    top_debiteurs: Client[];
    monthly_stats: MonthlyStat[];
    all_clients: Array<
        Pick<
            Client,
            'id' | 'name' | 'telephone' | 'solde_ouguiya' | 'solde_fcfa'
        >
    >;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: '/dashboard',
            },
        ],
    },
});

// Modals state
const isTransactionModalOpen = ref(false);
const isClientModalOpen = ref(false);

// Currency quick converter calculator state
const calcOuguiya = ref<number | string>(100000);
const calcRate = ref<number | string>(0.78);
const calcBuyRate = ref<number | string>(0.75);

const calcFcfa = () => {
    const oug = Number(calcOuguiya.value) || 0;
    const r = Number(calcRate.value) || 0;
    return r > 0 ? Math.round(oug / r) : 0;
};

const calcGain = () => {
    const oug = Number(calcOuguiya.value) || 0;
    const r = Number(calcRate.value) || 0;
    const br = Number(calcBuyRate.value) || 0;
    return br > 0 && r > 0 ? Math.round(calcFcfa() * br - oug) : 0;
};

function getWhatsAppUrl(phone?: string | null, clientName = '', amount = '') {
    if (!phone) return '#';
    const message = `Bonjour ${clientName}, nous vous informons que votre solde actuel est de ${amount}. Merci de bien vouloir faire le nécessaire pour la régularisation. Cordialement.`;
    return createWhatsAppUrl(phone, message);
}
</script>

<template>
    <Head title="Tableau de bord - OuguiyaCFA" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <!-- Top Banner & Quick Actions -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    Tableau de Bord
                    <span
                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        MRU ⇄ FCFA
                    </span>
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Suivi des soldes clients, des devises en circulation et
                    calcul des gains de change en temps réel.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <Button
                    variant="outline"
                    @click="isClientModalOpen = true"
                    class="gap-1.5 text-xs font-semibold shadow-sm"
                >
                    <Users class="size-4" />
                    Nouveau Client
                </Button>
                <Button
                    @click="isTransactionModalOpen = true"
                    class="gap-1.5 bg-emerald-600 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                >
                    <Plus class="size-4" />
                    Nouvelle Opération
                </Button>
            </div>
        </div>

        <!-- 4 Primary KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Total Creances FCFA -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-amber-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Créances FCFA à Recouvrer
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    >
                        <Wallet class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-black text-amber-600 dark:text-amber-400"
                    >
                        {{ formatFcfa(metrics.total_creances_fcfa) }}
                    </div>
                    <p
                        class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <span class="font-semibold text-foreground">{{
                            metrics.clients_en_dette
                        }}</span>
                        clients ont un solde débiteur
                    </p>
                </CardContent>
            </Card>

            <!-- 2. Solde Ouguiya Total -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-sky-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Ouguiya en Circulation
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
                        {{ formatOuguiya(metrics.total_solde_ouguiya) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Solde cumulé net sur tous les comptes
                    </p>
                </CardContent>
            </Card>

            <!-- 3. Total Gains Realises -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-emerald-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Bénéfices Réalisés
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
                        {{ formatOuguiya(metrics.total_gains) }}
                    </div>
                    <p
                        class="mt-1 flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                    >
                        {{ metrics.gains_this_month >= 0 ? '+' : ''
                        }}{{ formatOuguiya(metrics.gains_this_month) }} ce mois
                    </p>
                </CardContent>
            </Card>

            <!-- 4. Total Clients -->
            <Card
                class="relative overflow-hidden border-border/70 bg-gradient-to-br from-card to-purple-500/5 shadow-sm"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Comptes Clients Actifs
                    </CardTitle>
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                    >
                        <UserCheck class="size-4.5" />
                    </span>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-black text-foreground">
                        {{ metrics.total_clients }}
                    </div>
                    <div
                        class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        Volume FCFA:
                        <span class="font-semibold text-foreground">{{
                            formatFcfa(metrics.total_volume_fcfa)
                        }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Middle Row: Debtors alerts & Currency Converter -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Debtors alert widget (2 cols) -->
            <Card
                class="flex flex-col justify-between border-border/70 shadow-sm lg:col-span-2"
            >
                <CardHeader class="border-b bg-muted/20 pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle
                                class="flex items-center gap-2 text-base font-bold"
                            >
                                <AlertCircle class="size-4.5 text-amber-500" />
                                Alertes Créances & Débiteurs Prioritaires
                            </CardTitle>
                            <CardDescription>
                                Clients avec les créances FCFA les plus
                                importantes à recouvrer.
                            </CardDescription>
                        </div>
                        <Link href="/clients?filter=debiteurs_fcfa">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="gap-1 text-xs text-muted-foreground hover:text-foreground"
                            >
                                Voir tous <ArrowRight class="size-3.5" />
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div
                        v-if="top_debiteurs.length === 0"
                        class="flex flex-col items-center justify-center p-8 text-center text-muted-foreground"
                    >
                        <CheckCircle2 class="mb-2 size-10 text-emerald-500" />
                        <p class="font-medium text-foreground">
                            Tous les comptes sont à jour !
                        </p>
                        <p class="text-xs">
                            Aucun client n'a de dette FCFA en attente.
                        </p>
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div
                            v-for="client in top_debiteurs"
                            :key="client.id"
                            class="flex flex-col justify-between gap-3 p-4 transition-colors hover:bg-muted/40 sm:flex-row sm:items-center"
                        >
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/clients/${client.id}`"
                                        class="font-bold text-foreground transition-colors hover:text-emerald-600"
                                    >
                                        {{ client.name }}
                                    </Link>
                                    <span
                                        v-if="client.telephone"
                                        class="text-xs text-muted-foreground"
                                    >
                                        • {{ client.telephone }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span
                                        >Solde Ouguiya:
                                        <strong>{{
                                            formatOuguiya(client.solde_ouguiya)
                                        }}</strong></span
                                    >
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div
                                        class="text-sm font-black text-amber-600 dark:text-amber-400"
                                    >
                                        {{ formatFcfa(client.solde_fcfa) }}
                                    </div>
                                    <span
                                        class="text-[10px] font-semibold text-amber-700/80 uppercase dark:text-amber-300"
                                        >À recouvrer</span
                                    >
                                </div>

                                <div class="flex items-center gap-1.5">
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
                                        class="inline-flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 transition-colors hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300"
                                        title="Envoyer un rappel par WhatsApp"
                                    >
                                        <MessageCircle class="size-4" />
                                    </a>
                                    <Link :href="`/clients/${client.id}`">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            Fiche
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Currency Converter Widget (1 col) -->
            <Card
                class="flex flex-col justify-between border-border/70 bg-gradient-to-b from-card to-muted/20 shadow-sm"
            >
                <CardHeader class="border-b bg-muted/20 pb-3">
                    <CardTitle
                        class="flex items-center gap-2 text-base font-bold"
                    >
                        <Calculator
                            class="size-4.5 text-emerald-600 dark:text-emerald-400"
                        />
                        Calculatrice de Change
                    </CardTitle>
                    <CardDescription>
                        Simulez instantanément une transaction et votre marge.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3.5 p-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-foreground"
                            >Montant Ouguiya (MRU)</label
                        >
                        <Input
                            type="number"
                            v-model="calcOuguiya"
                            placeholder="100 000"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Taux de vente (Ouguiya / FCFA)</label
                            >
                            <Input
                                type="number"
                                step="0.01"
                                v-model="calcRate"
                                placeholder="0.78"
                            />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="text-xs font-medium text-muted-foreground"
                                >Taux d'achat (Ouguiya / FCFA)</label
                            >
                            <Input
                                type="number"
                                step="0.01"
                                v-model="calcBuyRate"
                                placeholder="0.75"
                            />
                        </div>
                    </div>

                    <div
                        class="space-y-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-center dark:border-emerald-800/60 dark:bg-emerald-950/40"
                    >
                        <div
                            class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Contre-valeur FCFA à payer
                        </div>
                        <div
                            class="text-2xl font-black text-emerald-700 dark:text-emerald-400"
                        >
                            {{ formatFcfa(calcFcfa()) }}
                        </div>
                        <div
                            class="flex items-center justify-center gap-1 border-t border-emerald-200/60 pt-2 text-xs font-semibold text-emerald-800 dark:border-emerald-800/60 dark:text-emerald-300"
                        >
                            <span>Bénéfice estimé :</span>
                            <span class="font-bold"
                                >{{ calcGain() > 0 ? '+' : ''
                                }}{{ formatOuguiya(calcGain()) }}</span
                            >
                        </div>
                    </div>

                    <Button
                        class="w-full gap-1.5 bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700"
                        @click="isTransactionModalOpen = true"
                    >
                        <Plus class="size-4" />
                        Enregistrer cette opération
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Monthly Trends & Profits Evolution -->
        <Card class="border-border/70 shadow-sm">
            <CardHeader class="border-b bg-muted/20 pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle
                            class="flex items-center gap-2 text-base font-bold"
                        >
                            <TrendingUp
                                class="size-4.5 text-emerald-600 dark:text-emerald-400"
                            />
                            Évolution des Gains & Volumes (6 Derniers Mois)
                        </CardTitle>
                        <CardDescription>
                            Bénéfices nets réalisés sur les opérations de change
                            Ouguiya / FCFA.
                        </CardDescription>
                    </div>
                    <Link href="/gains">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="gap-1 text-xs text-muted-foreground hover:text-foreground"
                        >
                            Module Gains Complet <ArrowRight class="size-3.5" />
                        </Button>
                    </Link>
                </div>
            </CardHeader>
            <CardContent class="p-6">
                <div
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6"
                >
                    <div
                        v-for="m in monthly_stats"
                        :key="m.month"
                        class="flex flex-col items-center justify-between rounded-xl border border-border/60 bg-muted/20 p-3.5 text-center transition-all hover:bg-muted/50"
                    >
                        <span
                            class="text-xs font-semibold text-muted-foreground"
                            >{{ m.month }}</span
                        >
                        <div
                            class="my-3 text-lg font-black text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatOuguiya(m.gain) }}
                        </div>
                        <div class="text-[11px] text-muted-foreground">
                            Vol: {{ formatFcfa(m.volume_fcfa) }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Recent Transactions Journal -->
        <Card class="border-border/70 shadow-sm">
            <CardHeader class="border-b bg-muted/20 pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle
                            class="flex items-center gap-2 text-base font-bold"
                        >
                            <Clock class="size-4.5 text-primary" />
                            Dernières Opérations Enregistrées
                        </CardTitle>
                        <CardDescription>
                            Flux en temps réel de votre journal de change.
                        </CardDescription>
                    </div>
                    <Link href="/transactions">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="gap-1 text-xs text-muted-foreground hover:text-foreground"
                        >
                            Journal Complet <ArrowRight class="size-3.5" />
                        </Button>
                    </Link>
                </div>
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
                                <th class="p-3">Type</th>
                                <th class="p-3">Libellé</th>
                                <th class="p-3 text-right">Ouguiya (MRU)</th>
                                <th class="p-3 text-right">Taux MRU/XOF</th>
                                <th class="p-3 text-right">FCFA (XOF)</th>
                                <th class="p-3 text-right">Paiement FCFA</th>
                                <th class="p-3 text-right">Gain</th>
                                <th class="p-3 pr-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="t in recent_transactions"
                                :key="t.id"
                                class="transition-colors hover:bg-muted/40"
                            >
                                <td
                                    class="p-3 pl-4 font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatShortDate(t.date_operation) }}
                                </td>
                                <td
                                    class="p-3 font-semibold whitespace-nowrap text-foreground"
                                >
                                    <Link
                                        v-if="t.client"
                                        :href="`/clients/${t.client.id}`"
                                        class="transition-colors hover:text-emerald-600"
                                    >
                                        {{ t.client.name }}
                                    </Link>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span
                                        v-if="t.type_operation === 'vente'"
                                        class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        <ArrowUpRight class="size-3" /> Vente
                                    </span>
                                    <span
                                        v-else-if="t.type_operation === 'achat'"
                                        class="inline-flex items-center gap-1 rounded-md bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                    >
                                        <ArrowDownLeft class="size-3" /> Achat
                                    </span>
                                    <span
                                        v-else-if="
                                            t.type_operation ===
                                            'remboursement_fcfa'
                                        "
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        <RotateCcw class="size-3" /> Règl. FCFA
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-[10px] font-bold text-muted-foreground"
                                    >
                                        Autre
                                    </span>
                                </td>
                                <td
                                    class="max-w-[200px] truncate p-3 text-muted-foreground"
                                >
                                    {{ t.description || '-' }}
                                </td>
                                <td
                                    class="p-3 text-right font-medium whitespace-nowrap"
                                >
                                    {{
                                        Number(t.ouguiya) > 0
                                            ? formatOuguiya(t.ouguiya)
                                            : '-'
                                    }}
                                </td>
                                <td
                                    class="p-3 text-right font-medium whitespace-nowrap"
                                >
                                    {{ formatRate(t.rate) }}
                                </td>
                                <td
                                    class="p-3 text-right font-semibold whitespace-nowrap"
                                >
                                    {{
                                        Number(t.fcfa) > 0
                                            ? formatFcfa(t.fcfa)
                                            : '-'
                                    }}
                                </td>
                                <td
                                    class="p-3 text-right font-semibold whitespace-nowrap text-emerald-600 dark:text-emerald-400"
                                >
                                    {{
                                        Number(t.payement_fcfa) > 0
                                            ? formatFcfa(t.payement_fcfa)
                                            : '-'
                                    }}
                                </td>
                                <td
                                    class="p-3 text-right font-bold whitespace-nowrap text-emerald-700 dark:text-emerald-400"
                                >
                                    {{
                                        Number(t.gain) !== 0
                                            ? `${Number(t.gain) > 0 ? '+' : ''}${formatOuguiya(t.gain)}`
                                            : '-'
                                    }}
                                </td>
                                <td
                                    class="p-3 pr-4 text-center whitespace-nowrap"
                                >
                                    <Link
                                        :href="`/transactions/${t.id}/receipt`"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-[11px]"
                                        >
                                            Reçu
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Modals -->
    <TransactionModal
        v-model:open="isTransactionModalOpen"
        :clients="all_clients"
    />

    <ClientModal v-model:open="isClientModalOpen" />
</template>
