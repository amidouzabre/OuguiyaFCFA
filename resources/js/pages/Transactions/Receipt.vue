<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy, MessageCircle, Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    formatFcfa,
    formatOuguiya,
    formatRate,
    formatShortDate,
} from '@/lib/formatters';
import type { Transaction } from '@/types';

const props = defineProps<{
    transaction: Transaction;
}>();

const copied = ref(false);

function printReceipt() {
    window.print();
}

const summaryText = `*OUGUIYA CFA - REÇU D'OPÉRATION*
Réf : OP-${props.transaction.id} | Date : ${formatShortDate(props.transaction.date_operation)}
Client : ${props.transaction.client?.name || 'Client'}
Opération : ${props.transaction.type_operation.toUpperCase()}
${Number(props.transaction.ouguiya) > 0 ? `Montant Ouguiya : ${formatOuguiya(props.transaction.ouguiya)}\n` : ''}${Number(props.transaction.rate) > 0 ? `Taux : ${formatRate(props.transaction.rate)}\n` : ''}${Number(props.transaction.fcfa) > 0 ? `Contre-valeur FCFA : ${formatFcfa(props.transaction.fcfa)}\n` : ''}${Number(props.transaction.payement_fcfa) > 0 ? `Paiement FCFA reçu : ${formatFcfa(props.transaction.payement_fcfa)}\n` : ''}-------------------------
*Nouveau Solde FCFA restant : ${formatFcfa(props.transaction.solde_fcfa_ligne)}*
*Nouveau Solde Ouguiya : ${formatOuguiya(props.transaction.solde_ouguiya_ligne)}*
Merci de votre confiance !`;

function copySummary() {
    navigator.clipboard.writeText(summaryText);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function getWhatsAppUrl() {
    const phone = props.transaction.client?.telephone?.replace(/[^0-9]/g, '');
    const encoded = encodeURIComponent(summaryText);
    return phone
        ? `https://wa.me/${phone}?text=${encoded}`
        : `https://wa.me/?text=${encoded}`;
}
</script>

<template>
    <Head
        :title="`Reçu Opération N°${transaction.numero_operation} - OuguiyaCFA`"
    />

    <div
        class="min-h-screen bg-neutral-100 px-4 py-8 text-neutral-900 dark:bg-neutral-900 print:bg-white print:p-0"
    >
        <!-- Floating Actions (hidden on print) -->
        <div
            class="mx-auto mb-6 flex max-w-md items-center justify-between print:hidden"
        >
            <Link
                :href="
                    transaction.client_id
                        ? `/clients/${transaction.client_id}`
                        : '/transactions'
                "
            >
                <Button variant="outline" size="sm" class="gap-1.5 shadow-sm">
                    <ArrowLeft class="size-4" />
                    Retour
                </Button>
            </Link>

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="copySummary"
                    class="gap-1 text-xs"
                >
                    <Check v-if="copied" class="size-3.5 text-emerald-600" />
                    <Copy v-else class="size-3.5" />
                    {{ copied ? 'Copié !' : 'Copier texte' }}
                </Button>
                <a
                    :href="getWhatsAppUrl()"
                    target="_blank"
                    class="inline-flex h-8 items-center gap-1 rounded-lg bg-emerald-600 px-2.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                >
                    <MessageCircle class="size-3.5" />
                    WhatsApp
                </a>
                <Button
                    @click="printReceipt"
                    size="sm"
                    class="gap-1 bg-neutral-900 text-xs font-bold text-white shadow-sm hover:bg-black"
                >
                    <Printer class="size-3.5" />
                    Imprimer
                </Button>
            </div>
        </div>

        <!-- Ticket Receipt Container -->
        <div
            class="mx-auto max-w-md rounded-2xl border border-neutral-200 bg-white p-6 font-mono text-xs shadow-lg sm:p-8 print:border-none print:p-2 print:shadow-none"
        >
            <!-- Header -->
            <div class="space-y-1 border-b pb-4 text-center">
                <div
                    class="text-base font-black tracking-tight text-neutral-900 uppercase"
                >
                    OUGUIYA<span class="text-emerald-600">CFA</span>
                </div>
                <div
                    class="text-[10px] font-semibold tracking-widest text-neutral-500 uppercase"
                >
                    Bureau de Change & Règlements
                </div>
                <div class="text-[10px] text-neutral-400">
                    Ouguiya (MRU) ⇄ Franc CFA (XOF)
                </div>
            </div>

            <!-- Receipt Info -->
            <div class="space-y-1 border-b py-3 text-[11px]">
                <div class="flex justify-between">
                    <span class="text-neutral-500">Reçu N° :</span>
                    <span class="font-bold text-neutral-900"
                        >#OP-{{ transaction.id }} (Op.
                        {{ transaction.numero_operation }})</span
                    >
                </div>
                <div class="flex justify-between">
                    <span class="text-neutral-500">Date :</span>
                    <span class="font-bold text-neutral-900">{{
                        formatShortDate(transaction.date_operation)
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-neutral-500">Client :</span>
                    <span class="font-bold text-neutral-900">{{
                        transaction.client?.name || 'Client'
                    }}</span>
                </div>
                <div
                    v-if="transaction.client?.telephone"
                    class="flex justify-between"
                >
                    <span class="text-neutral-500">Téléphone :</span>
                    <span>{{ transaction.client?.telephone }}</span>
                </div>
            </div>

            <!-- Operation Detail -->
            <div class="space-y-2.5 border-b py-4">
                <div class="flex justify-between text-xs font-bold">
                    <span>Nature de l'opération :</span>
                    <span class="font-black text-emerald-700 uppercase">
                        {{
                            transaction.type_operation === 'vente'
                                ? 'Vente Ouguiya'
                                : transaction.type_operation === 'achat'
                                  ? 'Achat Ouguiya'
                                  : transaction.type_operation ===
                                      'remboursement_fcfa'
                                    ? 'Règlement FCFA'
                                    : 'Opération'
                        }}
                    </span>
                </div>

                <div
                    v-if="transaction.description"
                    class="rounded bg-neutral-50 p-1.5 text-[10px] text-neutral-500 italic"
                >
                    « {{ transaction.description }} »
                </div>

                <div
                    v-if="
                        transaction.type_operation === 'vente' &&
                        transaction.purchase_source
                    "
                    class="flex justify-between text-xs"
                >
                    <span class="text-neutral-600"
                        >Achat source n°{{
                            transaction.purchase_source.numero_operation
                        }}
                        :</span
                    >
                    <span class="font-bold">{{
                        formatOuguiya(transaction.purchase_source.ouguiya)
                    }}</span>
                </div>

                <div
                    v-if="Number(transaction.ouguiya) > 0"
                    class="flex justify-between"
                >
                    <span class="text-neutral-600"
                        >Montant Ouguiya (MRU) :</span
                    >
                    <span class="font-bold">{{
                        formatOuguiya(transaction.ouguiya)
                    }}</span>
                </div>

                <div
                    v-if="Number(transaction.rate) > 0"
                    class="flex justify-between"
                >
                    <span class="text-neutral-600"
                        >Taux appliqué (Ouguiya / FCFA) :</span
                    >
                    <span class="font-bold">{{
                        formatRate(transaction.rate)
                    }}</span>
                </div>

                <div
                    v-if="Number(transaction.fcfa) > 0"
                    class="flex justify-between border-t border-dashed pt-1 text-xs font-bold"
                >
                    <span class="text-neutral-800">Contre-valeur FCFA :</span>
                    <span class="font-black text-neutral-900">{{
                        formatFcfa(transaction.fcfa)
                    }}</span>
                </div>

                <div
                    v-if="Number(transaction.payement_fcfa) > 0"
                    class="flex justify-between text-xs font-bold text-emerald-700"
                >
                    <span>Paiement FCFA reçu :</span>
                    <span class="font-black">{{
                        formatFcfa(transaction.payement_fcfa)
                    }}</span>
                </div>

                <div
                    v-if="Number(transaction.payement_ouguiya) > 0"
                    class="flex justify-between text-xs font-bold text-sky-700"
                >
                    <span>Paiement Ouguiya reçu :</span>
                    <span class="font-black">{{
                        formatOuguiya(transaction.payement_ouguiya)
                    }}</span>
                </div>

                <div
                    v-if="transaction.type_operation === 'vente'"
                    class="flex justify-between text-xs font-bold"
                    :class="
                        Number(transaction.gain) >= 0
                            ? 'text-emerald-700'
                            : 'text-red-700'
                    "
                >
                    <span>Bénéfice / Perte en Ouguiya :</span>
                    <span class="font-black"
                        >{{ Number(transaction.gain) > 0 ? '+' : ''
                        }}{{ formatOuguiya(transaction.gain) }}</span
                    >
                </div>
            </div>

            <!-- New Balances after operation -->
            <div
                class="-mx-6 space-y-2 border-b bg-neutral-50/80 px-6 py-4 sm:-mx-8 sm:px-8"
            >
                <div
                    class="text-center text-[10px] font-bold tracking-wider text-neutral-500 uppercase"
                >
                    Situation du compte après cette opération
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-neutral-700"
                        >Nouveau Solde FCFA :</span
                    >
                    <span
                        class="text-sm font-black"
                        :class="
                            Number(transaction.solde_fcfa_ligne) > 0
                                ? 'text-amber-700'
                                : 'text-emerald-700'
                        "
                    >
                        {{ formatFcfa(transaction.solde_fcfa_ligne) }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-neutral-700"
                        >Nouveau Solde Ouguiya :</span
                    >
                    <span class="text-xs font-bold text-sky-800">
                        {{ formatOuguiya(transaction.solde_ouguiya_ligne) }}
                    </span>
                </div>
            </div>

            <!-- Footer Signatures & QR/Mentions -->
            <div class="space-y-3 pt-6 text-center">
                <div class="text-[10px] text-neutral-400">
                    Opération enregistrée par :
                    {{ transaction.creator?.name || 'Administrateur' }}
                </div>
                <div class="text-[10px] font-semibold text-neutral-600">
                    Merci pour votre confiance ! À très bientôt.
                </div>
                <div
                    class="mx-auto h-10 w-36 border-b border-dashed border-neutral-300"
                ></div>
                <div
                    class="text-[9px] tracking-widest text-neutral-400 uppercase"
                >
                    Signature / Cachet
                </div>
            </div>
        </div>
    </div>
</template>
