<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowUpDown,
    CheckCircle,
    Eye,
    MessageCircle,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserPlus,
    Users,
    Wallet,
} from '@lucide/vue';
import ClientModal from '@/components/ClientModal.vue';
import TransactionModal from '@/components/TransactionModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatFcfa, formatOuguiya } from '@/lib/formatters';
import { getWhatsAppUrl as createWhatsAppUrl } from '@/lib/whatsapp';
import type { Client } from '@/types';

const props = defineProps<{
    clients: Client[];
    filters: {
        search?: string | null;
        filter?: string;
    };
    stats: {
        total_clients: number;
        total_debiteurs_fcfa: number;
        somme_creances_fcfa: number;
        somme_creances_mru: number;
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
                title: 'Comptes Clients',
                href: '/clients',
            },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const activeFilter = ref(props.filters.filter || 'all');

// Modals
const isClientModalOpen = ref(false);
const editingClient = ref<Client | null>(null);

const isTransactionModalOpen = ref(false);
const selectedClientIdForTx = ref<number | null>(null);

function openCreateClient() {
    editingClient.value = null;
    isClientModalOpen.value = true;
}

function openEditClient(client: Client) {
    editingClient.value = client;
    isClientModalOpen.value = true;
}

function openAddTransaction(clientId: number) {
    selectedClientIdForTx.value = clientId;
    isTransactionModalOpen.value = true;
}

function deleteClient(client: Client) {
    if (
        confirm(
            `Êtes-vous sûr de vouloir supprimer le client "${client.name}" et toutes ses opérations ? Cette action est irréversible.`,
        )
    ) {
        router.delete(`/clients/${client.id}`);
    }
}

function applyFilters() {
    router.get(
        '/clients',
        {
            search: searchQuery.value || undefined,
            filter:
                activeFilter.value !== 'all' ? activeFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

// Watch search with slight debounce
let timeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

function setFilter(filter: string) {
    activeFilter.value = filter;
    applyFilters();
}

function getWhatsAppUrl(phone?: string | null, clientName = '', amount = '') {
    if (!phone) return '#';
    const message = `Bonjour ${clientName}, voici le récapitulatif de votre compte. Votre solde actuel est de ${amount}. Cordialement.`;
    return createWhatsAppUrl(phone, message);
}
</script>

<template>
    <Head title="Comptes Clients - OuguiyaCFA" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <!-- Top header -->
        <div
            class="flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    Comptes Clients
                    <span
                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        {{ clients.length }} affichés
                    </span>
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Gérez vos clients, consultez leurs relevés de compte et
                    suivez leurs créances en temps réel.
                </p>
            </div>

            <Button
                @click="openCreateClient"
                class="gap-1.5 self-start bg-emerald-600 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 sm:self-auto"
            >
                <UserPlus class="size-4" />
                Nouveau Client
            </Button>
        </div>

        <!-- 4 Quick Stats Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Total Clients
                </div>
                <div class="mt-1 text-2xl font-black text-foreground">
                    {{ stats.total_clients }}
                </div>
            </Card>

            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-semibold tracking-wider text-amber-600 uppercase dark:text-amber-400"
                >
                    Débiteurs FCFA
                </div>
                <div
                    class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400"
                >
                    {{ stats.total_debiteurs_fcfa }}
                </div>
            </Card>

            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-semibold tracking-wider text-amber-600 uppercase dark:text-amber-400"
                >
                    Créances Totales FCFA
                </div>
                <div
                    class="mt-1 text-xl font-black text-amber-600 sm:text-2xl dark:text-amber-400"
                >
                    {{ formatFcfa(stats.somme_creances_fcfa) }}
                </div>
            </Card>

            <Card class="border-border/70 p-4 shadow-sm">
                <div
                    class="text-xs font-semibold tracking-wider text-sky-600 uppercase dark:text-sky-400"
                >
                    Créances Totales MRU
                </div>
                <div
                    class="mt-1 text-xl font-black text-sky-600 sm:text-2xl dark:text-sky-400"
                >
                    {{ formatOuguiya(stats.somme_creances_mru) }}
                </div>
            </Card>
        </div>

        <!-- Search & Filter Bar -->
        <div
            class="flex flex-col items-stretch justify-between gap-3 md:flex-row md:items-center"
        >
            <!-- Search -->
            <div class="relative max-w-md flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Rechercher par nom, téléphone, ville..."
                    class="pl-9 text-xs"
                />
            </div>

            <!-- Filter tabs -->
            <div
                class="flex flex-wrap items-center gap-1.5 rounded-xl border border-border/50 bg-muted/60 p-1 text-xs font-medium"
            >
                <button
                    @click="setFilter('all')"
                    :class="[
                        'rounded-lg px-3 py-1.5 transition-all',
                        activeFilter === 'all'
                            ? 'bg-background font-bold text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    Tous ({{ stats.total_clients }})
                </button>
                <button
                    @click="setFilter('debiteurs_fcfa')"
                    :class="[
                        'flex items-center gap-1 rounded-lg px-3 py-1.5 transition-all',
                        activeFilter === 'debiteurs_fcfa'
                            ? 'bg-amber-100 font-bold text-amber-900 shadow-sm dark:bg-amber-950 dark:text-amber-300'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    <span class="size-2 rounded-full bg-amber-500"></span>
                    Débiteurs FCFA
                </button>
                <button
                    @click="setFilter('debiteurs_mru')"
                    :class="[
                        'flex items-center gap-1 rounded-lg px-3 py-1.5 transition-all',
                        activeFilter === 'debiteurs_mru'
                            ? 'bg-sky-100 font-bold text-sky-900 shadow-sm dark:bg-sky-950 dark:text-sky-300'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    <span class="size-2 rounded-full bg-sky-500"></span>
                    Débiteurs MRU
                </button>
                <button
                    @click="setFilter('soldes')"
                    :class="[
                        'flex items-center gap-1 rounded-lg px-3 py-1.5 transition-all',
                        activeFilter === 'soldes'
                            ? 'bg-emerald-100 font-bold text-emerald-900 shadow-sm dark:bg-emerald-950 dark:text-emerald-300'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    Comptes Soldés
                </button>
            </div>
        </div>

        <!-- Clients Table -->
        <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardContent class="p-0">
                <div
                    v-if="clients.length === 0"
                    class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground"
                >
                    <Users class="mb-3 size-12 text-muted-foreground/50" />
                    <p class="text-base font-bold text-foreground">
                        Aucun client trouvé
                    </p>
                    <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                        Aucun compte ne correspond à votre recherche ou filtre.
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        class="mt-4 gap-1.5"
                        @click="openCreateClient"
                    >
                        <UserPlus class="size-4" />
                        Créer un client
                    </Button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="border-b bg-muted/50 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="p-3 pl-4">Client</th>
                                <th class="p-3">Téléphone</th>
                                <th class="p-3">Localisation</th>
                                <th class="p-3 text-center">Opérations</th>
                                <th class="p-3 text-right">Solde FCFA (XOF)</th>
                                <th class="p-3 text-right">
                                    Solde Ouguiya (MRU)
                                </th>
                                <th class="p-3 pr-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="c in clients"
                                :key="c.id"
                                class="group transition-colors hover:bg-muted/40"
                            >
                                <td
                                    class="p-3 pl-4 font-bold whitespace-nowrap text-foreground"
                                >
                                    <Link
                                        :href="`/clients/${c.id}`"
                                        class="flex items-center gap-2 transition-colors hover:text-emerald-600"
                                    >
                                        <div
                                            class="flex size-7 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground uppercase transition-colors group-hover:bg-emerald-100 group-hover:text-emerald-700"
                                        >
                                            {{ c.name.substring(0, 2) }}
                                        </div>
                                        <span>{{ c.name }}</span>
                                    </Link>
                                </td>
                                <td
                                    class="p-3 whitespace-nowrap text-muted-foreground"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ c.telephone || '-' }}</span>
                                        <a
                                            v-if="c.telephone"
                                            :href="
                                                getWhatsAppUrl(
                                                    c.telephone,
                                                    c.name,
                                                    formatFcfa(c.solde_fcfa),
                                                )
                                            "
                                            target="_blank"
                                            class="inline-flex size-6 items-center justify-center rounded bg-emerald-100 text-emerald-700 transition-colors hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300"
                                            title="Contacter sur WhatsApp"
                                        >
                                            <MessageCircle class="size-3.5" />
                                        </a>
                                    </div>
                                </td>
                                <td
                                    class="p-3 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ c.address || '-' }}
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <span
                                        class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-semibold text-muted-foreground"
                                    >
                                        {{ c.transactions_count || 0 }}
                                    </span>
                                </td>
                                <td
                                    class="p-3 text-right font-bold whitespace-nowrap"
                                >
                                    <span
                                        v-if="Number(c.solde_fcfa) > 0"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-black text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        {{ formatFcfa(c.solde_fcfa) }}
                                        <span
                                            class="text-[9px] font-normal uppercase"
                                            >(Doit)</span
                                        >
                                    </span>
                                    <span
                                        v-else-if="Number(c.solde_fcfa) < 0"
                                        class="inline-flex items-center gap-1 rounded-md bg-sky-100 px-2 py-0.5 text-xs font-black text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                    >
                                        {{
                                            formatFcfa(
                                                Math.abs(Number(c.solde_fcfa)),
                                            )
                                        }}
                                        <span
                                            class="text-[9px] font-normal uppercase"
                                            >(Avoir)</span
                                        >
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        Soldé
                                    </span>
                                </td>
                                <td
                                    class="p-3 text-right font-bold whitespace-nowrap"
                                >
                                    <span
                                        v-if="Number(c.solde_ouguiya) > 0"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold text-sky-700 dark:text-sky-300"
                                    >
                                        {{ formatOuguiya(c.solde_ouguiya) }}
                                    </span>
                                    <span
                                        v-else-if="Number(c.solde_ouguiya) < 0"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold text-muted-foreground"
                                    >
                                        -{{
                                            formatOuguiya(
                                                Math.abs(
                                                    Number(c.solde_ouguiya),
                                                ),
                                            )
                                        }}
                                    </span>
                                    <span v-else class="text-muted-foreground">
                                        0 MRU
                                    </span>
                                </td>
                                <td
                                    class="p-3 pr-4 text-center whitespace-nowrap"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <Link :href="`/clients/${c.id}`">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-7 gap-1 border-emerald-600/30 px-2.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                                            >
                                                <Eye class="size-3.5" />
                                                Historique
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-[11px] text-muted-foreground hover:text-emerald-600"
                                            title="Ajouter une opération"
                                            @click="openAddTransaction(c.id)"
                                        >
                                            <Plus class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-[11px] text-muted-foreground hover:text-foreground"
                                            title="Modifier la fiche"
                                            @click="openEditClient(c)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 px-2 text-[11px] text-destructive hover:bg-destructive/10"
                                            title="Supprimer le client"
                                            @click="deleteClient(c)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Modals -->
    <ClientModal v-model:open="isClientModalOpen" :client="editingClient" />

    <TransactionModal
        v-model:open="isTransactionModalOpen"
        :clients="clients"
        :default-client-id="selectedClientIdForTx"
    />
</template>
