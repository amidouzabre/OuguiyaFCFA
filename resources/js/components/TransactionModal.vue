<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Calculator,
    Check,
    RotateCcw,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatFcfa,
    formatOuguiya,
    formatRate,
    formatShortDate,
} from '@/lib/formatters';
import type { Client, Transaction, TransactionType } from '@/types';

const props = defineProps<{
    open: boolean;
    clients: Array<
        Pick<Client, 'id' | 'name' | 'solde_ouguiya' | 'solde_fcfa'>
    >;
    defaultClientId?: number | null;
    transaction?: Transaction | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const isEditing = computed(() => !!props.transaction);
const isLegacyOperation = computed(() =>
    ['autre', 'depot', 'remboursement_ouguiya'].includes(
        form.value.type_operation,
    ),
);

type PurchaseOption = {
    id: number;
    date_operation: string;
    numero_operation: number;
    ouguiya: number | string;
};

const form = ref({
    client_id: '' as string | number,
    date_operation: new Date().toISOString().split('T')[0],
    type_operation: 'achat' as TransactionType,
    purchase_source_id: '' as number | string,
    description: '',
    ouguiya: '' as number | string,
    payement_ouguiya: '' as number | string,
    fcfa: '' as number | string,
    payement_fcfa: '' as number | string,
    rate: '' as number | string,
    taux_achat: '' as number | string,
    gain: '' as number | string,
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});
const purchaseOptions = ref<PurchaseOption[]>([]);
const isLoadingPurchases = ref(false);
const purchaseLoadError = ref('');

// Current selected client
const selectedClient = computed(() => {
    return props.clients.find(
        (c) => String(c.id) === String(form.value.client_id),
    );
});

const calculatedRate = computed(() => {
    const ouguiya = Number(form.value.ouguiya);
    const fcfa = Number(form.value.fcfa);

    return ouguiya > 0 && fcfa > 0 ? ouguiya / fcfa : null;
});

const calculatedGain = computed(() => {
    if (form.value.type_operation !== 'vente' || !form.value.purchase_source_id)
        return null;
    const purchase = purchaseOptions.value.find(
        (option) => option.id === Number(form.value.purchase_source_id),
    );
    if (!purchase) return null;

    return Number(form.value.ouguiya) - Number(purchase.ouguiya);
});

// Watch open/transaction props to initialize form
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        errors.value = {};
        if (props.transaction) {
            form.value = {
                client_id: props.transaction.client_id,
                date_operation: props.transaction.date_operation
                    ? props.transaction.date_operation.substring(0, 10)
                    : new Date().toISOString().split('T')[0],
                type_operation: props.transaction.type_operation,
                purchase_source_id: props.transaction.purchase_source_id || '',
                description: props.transaction.description || '',
                ouguiya: Number(props.transaction.ouguiya) || '',
                payement_ouguiya:
                    Number(props.transaction.payement_ouguiya) || '',
                fcfa:
                    props.transaction.type_operation === 'remboursement_fcfa'
                        ? Number(props.transaction.payement_fcfa) || ''
                        : Number(props.transaction.fcfa) || '',
                payement_fcfa: Number(props.transaction.payement_fcfa) || '',
                rate: Number(props.transaction.rate) || '',
                taux_achat: Number(props.transaction.taux_achat) || '',
                gain: Number(props.transaction.gain) || '',
            };
            recalculateAuto();
        } else {
            form.value = {
                client_id:
                    props.defaultClientId ||
                    (props.clients.length > 0 ? props.clients[0].id : ''),
                date_operation: new Date().toISOString().split('T')[0],
                type_operation: 'achat',
                purchase_source_id: '',
                description: 'Achat Ouguiya',
                ouguiya: '',
                payement_ouguiya: '',
                fcfa: '',
                payement_fcfa: '',
                rate: '',
                taux_achat: '',
                gain: '',
            };
        }
    },
    { immediate: true },
);

watch(
    () =>
        [
            props.open,
            form.value.client_id,
            form.value.type_operation,
            props.transaction?.id,
        ] as const,
    async (
        [isOpen, clientId, typeOperation, transactionId],
        _previous,
        onCleanup,
    ) => {
        purchaseOptions.value = [];
        purchaseLoadError.value = '';

        if (!isOpen || typeOperation !== 'vente' || !clientId) {
            isLoadingPurchases.value = false;
            return;
        }

        const controller = new AbortController();
        onCleanup(() => controller.abort());
        isLoadingPurchases.value = true;

        try {
            const query = transactionId
                ? `?exclude_transaction_id=${transactionId}`
                : '';
            const response = await fetch(
                `/clients/${clientId}/purchase-transactions${query}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                throw new Error(
                    'Impossible de charger les achats disponibles.',
                );
            }

            purchaseOptions.value = (await response.json()) as PurchaseOption[];
            recalculateAuto();
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError')
                return;
            purchaseLoadError.value =
                error instanceof Error
                    ? error.message
                    : 'Erreur lors du chargement des achats.';
        } finally {
            if (!controller.signal.aborted) {
                isLoadingPurchases.value = false;
            }
        }
    },
    { immediate: true },
);

// Auto-fill defaults and descriptions on operation type change
function onTypeChange(type: TransactionType) {
    if (form.value.type_operation === type) return;

    form.value.type_operation = type;
    form.value.ouguiya = '';
    form.value.purchase_source_id = '';
    form.value.payement_ouguiya = '';
    form.value.fcfa = '';
    form.value.payement_fcfa = '';
    form.value.rate = '';
    form.value.taux_achat = '';
    form.value.gain = '';

    form.value.description =
        {
            achat: 'Achat Ouguiya',
            vente: 'Vente Ouguiya',
            remboursement_fcfa: 'Remboursement FCFA',
            remboursement_ouguiya: 'Remboursement Ouguiya',
            depot: 'Dépôt',
            autre: 'Autre opération',
        }[type] || '';
}

function recalculateAuto() {
    form.value.rate = calculatedRate.value
        ? Number(calculatedRate.value.toFixed(4))
        : '';
    form.value.gain =
        calculatedGain.value === null
            ? ''
            : Number(calculatedGain.value.toFixed(2));
    if (form.value.type_operation === 'vente') {
        const purchase = purchaseOptions.value.find(
            (option) => option.id === Number(form.value.purchase_source_id),
        );
        form.value.payement_ouguiya = purchase ? Number(purchase.ouguiya) : '';
    }
}

// Simulation of balance after operation
const simulatedBalances = computed(() => {
    if (!selectedClient.value) return null;

    const curOug = parseFloat(String(selectedClient.value.solde_ouguiya)) || 0;
    const curFcfa = parseFloat(String(selectedClient.value.solde_fcfa)) || 0;

    const opOug = parseFloat(String(form.value.ouguiya)) || 0;
    const payOug = parseFloat(String(form.value.payement_ouguiya)) || 0;
    const opFcfa = parseFloat(String(form.value.fcfa)) || 0;
    const payFcfa = parseFloat(String(form.value.payement_fcfa)) || 0;

    // In edit mode, client's current balance already has this operation, but this gives a solid indicator
    const newOug =
        form.value.type_operation === 'vente'
            ? curOug - payOug
            : curOug + (opOug - payOug);
    const newFcfa =
        curFcfa +
        (form.value.type_operation === 'remboursement_fcfa'
            ? -opFcfa
            : opFcfa - payFcfa);

    return {
        curOug,
        curFcfa,
        newOug,
        newFcfa,
    };
});

function handleSubmit() {
    errors.value = {};
    if (!form.value.client_id) {
        errors.value.client_id = 'Veuillez sélectionner un client.';
        return;
    }
    if (!form.value.date_operation) {
        errors.value.date_operation = 'La date est requise.';
        return;
    }

    isSubmitting.value = true;

    if (isEditing.value && props.transaction) {
        router.put(`/transactions/${props.transaction.id}`, form.value, {
            onSuccess: () => {
                isSubmitting.value = false;
                emit('update:open', false);
                emit('saved');
            },
            onError: (err) => {
                isSubmitting.value = false;
                errors.value = err;
            },
        });
    } else {
        router.post('/transactions', form.value, {
            onSuccess: () => {
                isSubmitting.value = false;
                emit('update:open', false);
                emit('saved');
            },
            onError: (err) => {
                isSubmitting.value = false;
                errors.value = err;
            },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-xl font-bold">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <Calculator class="size-4.5" />
                    </span>
                    {{
                        isEditing
                            ? "Modifier l'opération"
                            : 'Nouvelle opération de change'
                    }}
                </DialogTitle>
                <DialogDescription>
                    Enregistrez une transaction, un achat, une vente ou un
                    règlement avec calcul automatique des soldes et gains.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-5 py-2">
                <!-- Client selector & Current Balance Display -->
                <div class="grid gap-2">
                    <Label for="client_id" class="text-sm font-semibold"
                        >Client concerné *</Label
                    >
                    <select
                        id="client_id"
                        v-model="form.client_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="isEditing"
                        required
                    >
                        <option value="" disabled>
                            Sélectionner un client...
                        </option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }} (Solde:
                            {{ formatFcfa(c.solde_fcfa) }} |
                            {{ formatOuguiya(c.solde_ouguiya) }})
                        </option>
                    </select>
                    <p v-if="errors.client_id" class="text-xs text-destructive">
                        {{ errors.client_id }}
                    </p>

                    <!-- Client Balances preview bar -->
                    <div
                        v-if="selectedClient"
                        class="flex flex-wrap items-center gap-2 rounded-lg bg-muted/60 p-2.5 text-xs"
                    >
                        <span class="font-medium text-muted-foreground"
                            >Soldes actuels :</span
                        >
                        <span
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-semibold"
                            :class="
                                Number(selectedClient.solde_fcfa) > 0
                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                    : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                            "
                        >
                            FCFA: {{ formatFcfa(selectedClient.solde_fcfa) }}
                            <span
                                v-if="Number(selectedClient.solde_fcfa) > 0"
                                class="text-[10px] font-normal uppercase"
                                >(Doit)</span
                            >
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-semibold"
                            :class="
                                Number(selectedClient.solde_ouguiya) > 0
                                    ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300'
                                    : 'bg-muted text-foreground'
                            "
                        >
                            MRU:
                            {{ formatOuguiya(selectedClient.solde_ouguiya) }}
                        </span>
                    </div>
                </div>

                <!-- Operation Type Selection Pills -->
                <div class="space-y-2">
                    <Label class="text-sm font-semibold"
                        >Type d'opération</Label
                    >
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button
                            type="button"
                            @click="onTypeChange('achat')"
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-lg border p-2 text-xs font-semibold transition-all',
                                form.type_operation === 'achat'
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm dark:border-blue-500 dark:bg-blue-950/60 dark:text-blue-300'
                                    : 'border-border text-muted-foreground hover:bg-muted',
                            ]"
                        >
                            <ArrowDownLeft class="size-3.5" />
                            Achat d'Ouguiya
                        </button>
                        <button
                            type="button"
                            @click="onTypeChange('vente')"
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-lg border p-2 text-xs font-semibold transition-all',
                                form.type_operation === 'vente'
                                    ? 'border-emerald-600 bg-emerald-50 text-emerald-700 shadow-sm dark:border-emerald-500 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'border-border text-muted-foreground hover:bg-muted',
                            ]"
                        >
                            <ArrowUpRight class="size-3.5" />
                            Vente d'Ouguiya
                        </button>
                        <button
                            type="button"
                            @click="onTypeChange('remboursement_fcfa')"
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-lg border p-2 text-xs font-semibold transition-all',
                                form.type_operation === 'remboursement_fcfa'
                                    ? 'border-amber-600 bg-amber-50 text-amber-700 shadow-sm dark:border-amber-500 dark:bg-amber-950/60 dark:text-amber-300'
                                    : 'border-border text-muted-foreground hover:bg-muted',
                            ]"
                        >
                            <RotateCcw class="size-3.5" />
                            Remboursement FCFA
                        </button>
                        <button
                            v-if="isEditing && isLegacyOperation"
                            type="button"
                            @click="onTypeChange('autre')"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-purple-600 bg-purple-50 p-2 text-xs font-semibold text-purple-700 shadow-sm dark:border-purple-500 dark:bg-purple-950/60 dark:text-purple-300"
                        >
                            Saisie libre (ancien)
                        </button>
                    </div>
                </div>

                <!-- Date & Description -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="space-y-1">
                        <Label for="date_op" class="text-xs font-medium"
                            >Date d'opération *</Label
                        >
                        <Input
                            id="date_op"
                            type="date"
                            v-model="form.date_operation"
                            required
                        />
                        <p
                            v-if="errors.date_operation"
                            class="text-xs text-destructive"
                        >
                            {{ errors.date_operation }}
                        </p>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <Label for="description" class="text-xs font-medium"
                            >Libellé / Référence</Label
                        >
                        <Input
                            id="description"
                            type="text"
                            v-model="form.description"
                            placeholder="ex: Vente 100 000 MRU espèces, Acompte Wave..."
                        />
                    </div>
                </div>

                <!-- Operation-specific amount fields -->
                <div
                    class="space-y-4 rounded-xl border border-border/80 bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex items-center justify-between text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        <span>Montants</span>
                        <span
                            v-if="
                                form.type_operation === 'achat' ||
                                isLegacyOperation
                            "
                            class="text-[11px] font-normal text-emerald-600 dark:text-emerald-400"
                        >
                            Taux Ouguiya / FCFA :
                            {{
                                calculatedRate
                                    ? formatRate(calculatedRate)
                                    : '-'
                            }}
                        </span>
                    </div>

                    <div
                        v-if="form.type_operation === 'vente'"
                        class="space-y-3"
                    >
                        <div class="space-y-1">
                            <Label
                                for="purchase_source_id"
                                class="text-xs font-medium text-foreground"
                                >Achat d’Ouguiya à revendre</Label
                            >
                            <select
                                id="purchase_source_id"
                                v-model="form.purchase_source_id"
                                @change="recalculateAuto"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="" disabled>
                                    {{
                                        isLoadingPurchases
                                            ? 'Chargement des achats...'
                                            : 'Sélectionner un achat disponible...'
                                    }}
                                </option>
                                <option
                                    v-for="purchase in purchaseOptions"
                                    :key="purchase.id"
                                    :value="purchase.id"
                                >
                                    Opération n°{{ purchase.numero_operation }}
                                    ·
                                    {{
                                        formatShortDate(purchase.date_operation)
                                    }}
                                    · {{ formatOuguiya(purchase.ouguiya) }}
                                </option>
                            </select>
                            <p
                                v-if="errors.purchase_source_id"
                                class="text-xs text-destructive"
                            >
                                {{ errors.purchase_source_id }}
                            </p>
                            <p
                                v-if="purchaseLoadError"
                                class="text-xs text-destructive"
                            >
                                {{ purchaseLoadError }}
                            </p>
                            <p
                                v-else-if="
                                    !isLoadingPurchases &&
                                    purchaseOptions.length === 0
                                "
                                class="text-xs text-muted-foreground"
                            >
                                Aucun achat d’Ouguiya disponible pour ce client.
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Chaque achat sélectionné ne peut être vendu
                                qu’une seule fois.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label
                                    for="ouguiya"
                                    class="text-xs font-medium text-foreground"
                                    >Montant Ouguiya de revente (MRU)</Label
                                >
                                <Input
                                    id="ouguiya"
                                    type="number"
                                    min="0.01"
                                    step="any"
                                    v-model="form.ouguiya"
                                    @input="recalculateAuto"
                                    placeholder="0"
                                    required
                                />
                                <p
                                    v-if="errors.ouguiya"
                                    class="text-xs text-destructive"
                                >
                                    {{ errors.ouguiya }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <Label
                                    class="text-xs font-medium text-emerald-600 dark:text-emerald-400"
                                    >Bénéfice / Perte estimé (MRU)</Label
                                >
                                <p
                                    class="flex h-10 items-center rounded-md border border-input bg-muted/40 px-3 text-sm font-bold"
                                    :class="
                                        (calculatedGain || 0) >= 0
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : 'text-destructive'
                                    "
                                >
                                    {{
                                        calculatedGain === null
                                            ? '-'
                                            : formatOuguiya(calculatedGain)
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="form.type_operation === 'achat'"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                    >
                        <div class="space-y-1">
                            <Label
                                for="ouguiya"
                                class="text-xs font-medium text-foreground"
                            >
                                Montant Ouguiya (MRU)
                            </Label>
                            <Input
                                id="ouguiya"
                                type="number"
                                step="any"
                                v-model="form.ouguiya"
                                @input="recalculateAuto"
                                placeholder="0"
                                required
                            />
                            <p
                                v-if="errors.ouguiya"
                                class="text-xs text-destructive"
                            >
                                {{ errors.ouguiya }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <Label
                                for="fcfa"
                                class="text-xs font-medium text-foreground"
                            >
                                Montant FCFA (XOF)
                            </Label>
                            <Input
                                id="fcfa"
                                type="number"
                                step="any"
                                v-model="form.fcfa"
                                @input="recalculateAuto"
                                placeholder="0"
                                required
                            />
                            <p
                                v-if="errors.fcfa"
                                class="text-xs text-destructive"
                            >
                                {{ errors.fcfa }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="form.type_operation === 'remboursement_fcfa'"
                        class="space-y-1"
                    >
                        <Label
                            for="fcfa"
                            class="text-xs font-medium text-foreground"
                            >Montant FCFA (XOF)</Label
                        >
                        <Input
                            id="fcfa"
                            type="number"
                            min="0.01"
                            step="any"
                            v-model="form.fcfa"
                            placeholder="0"
                            required
                        />
                        <p v-if="errors.fcfa" class="text-xs text-destructive">
                            {{ errors.fcfa }}
                        </p>
                    </div>

                    <div
                        v-if="isLegacyOperation"
                        class="grid grid-cols-1 gap-3 border-t border-border/50 pt-3 sm:grid-cols-2"
                    >
                        <div class="space-y-1">
                            <Label for="ouguiya" class="text-xs font-medium"
                                >Montant Ouguiya (MRU)</Label
                            >
                            <Input
                                id="ouguiya"
                                type="number"
                                step="any"
                                v-model="form.ouguiya"
                                @input="recalculateAuto"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label for="fcfa" class="text-xs font-medium"
                                >Montant FCFA (XOF)</Label
                            >
                            <Input
                                id="fcfa"
                                type="number"
                                step="any"
                                v-model="form.fcfa"
                                @input="recalculateAuto"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label
                                for="payement_fcfa"
                                class="text-xs font-medium"
                                >Paiement FCFA</Label
                            >
                            <Input
                                id="payement_fcfa"
                                type="number"
                                step="any"
                                v-model="form.payement_fcfa"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label
                                for="payement_ouguiya"
                                class="text-xs font-medium"
                                >Paiement Ouguiya</Label
                            >
                            <Input
                                id="payement_ouguiya"
                                type="number"
                                step="any"
                                v-model="form.payement_ouguiya"
                            />
                        </div>
                    </div>
                </div>

                <!-- Simulation Preview Banner -->
                <div
                    v-if="simulatedBalances && !isEditing"
                    class="space-y-1.5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs dark:border-emerald-800/60 dark:bg-emerald-950/40"
                >
                    <div
                        class="flex items-center justify-between font-semibold text-emerald-900 dark:text-emerald-300"
                    >
                        <span>Simulation du solde après cette opération :</span>
                        <span
                            v-if="calculatedGain !== null"
                            class="font-bold"
                            :class="
                                calculatedGain >= 0
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-destructive'
                            "
                        >
                            {{
                                calculatedGain >= 0
                                    ? 'Gain estimé :'
                                    : 'Perte estimée :'
                            }}
                            {{ formatOuguiya(calculatedGain) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-muted-foreground">
                        <div>
                            Solde FCFA :
                            <span class="font-semibold text-foreground">{{
                                formatFcfa(simulatedBalances.curFcfa)
                            }}</span>
                            →
                            <span
                                class="font-bold text-emerald-700 dark:text-emerald-400"
                                >{{
                                    formatFcfa(simulatedBalances.newFcfa)
                                }}</span
                            >
                        </div>
                        <div>
                            Solde Ouguiya :
                            <span class="font-semibold text-foreground">{{
                                formatOuguiya(simulatedBalances.curOug)
                            }}</span>
                            →
                            <span
                                class="font-bold text-sky-700 dark:text-sky-400"
                                >{{
                                    formatOuguiya(simulatedBalances.newOug)
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <DialogFooter
                    class="flex items-center gap-2 pt-2 sm:justify-between"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        class="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                        :disabled="isSubmitting"
                    >
                        <Check class="size-4" />
                        {{
                            isSubmitting
                                ? 'Enregistrement...'
                                : isEditing
                                  ? 'Mettre à jour'
                                  : "Enregistrer l'opération"
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
