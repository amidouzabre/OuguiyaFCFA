<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MessageCircle, Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
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
        count: number;
    };
}>();

function printStatement() {
    window.print();
}

const currentDate = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'long',
}).format(new Date());

function getWhatsAppUrl(
    phone?: string | null,
    clientName = '',
    soldeFcfa = '',
) {
    const message = `Bonjour ${clientName}, voici votre relevé de compte de change Ouguiya/FCFA arrêté au ${currentDate}. Votre solde FCFA restant dû est de ${soldeFcfa}. Merci de votre confiance.`;
    return phone ? createWhatsAppUrl(phone, message) : '#';
}
</script>

<template>
    <Head :title="`Relevé de Compte - ${client.name} - A4 Print`" />

    <div
        class="min-h-screen bg-neutral-100 px-4 py-6 text-neutral-900 dark:bg-neutral-900 print:bg-white print:p-0"
    >
        <!-- Floating Action Header (Hidden in Print) -->
        <div
            class="mx-auto mb-6 flex max-w-4xl items-center justify-between print:hidden"
        >
            <Link :href="`/clients/${client.id}`">
                <Button variant="outline" size="sm" class="gap-1.5 shadow-sm">
                    <ArrowLeft class="size-4" />
                    Retour à la fiche client
                </Button>
            </Link>

            <div class="flex items-center gap-2">
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
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                >
                    <MessageCircle class="size-4" />
                    Envoyer par WhatsApp
                </a>
                <Button
                    @click="printStatement"
                    class="gap-1.5 bg-neutral-900 text-xs font-bold text-white shadow-sm hover:bg-black"
                >
                    <Printer class="size-4" />
                    Imprimer / Enregistrer en PDF
                </Button>
            </div>
        </div>

        <!-- A4 Page Container -->
        <div
            class="mx-auto max-w-4xl rounded-xl border border-neutral-200 bg-white p-8 shadow-md sm:p-12 print:rounded-none print:border-none print:p-0 print:shadow-none"
        >
            <!-- Header Section -->
            <div class="flex items-start justify-between border-b pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-lg font-black text-white"
                        >
                            ⇄
                        </div>
                        <div>
                            <h2
                                class="text-xl font-black tracking-tight text-neutral-900 uppercase"
                            >
                                Ouguiya<span class="text-emerald-600">CFA</span>
                            </h2>
                            <p
                                class="text-[11px] font-semibold tracking-wider text-neutral-500 uppercase"
                            >
                                Bureau de Change & Opérations Commerciales
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 space-y-0.5 text-xs text-neutral-500">
                        <p>Change Devises : Ouguiya (MRU) / Franc CFA (XOF)</p>
                        <p>Transferts & Règlements Commerciaux</p>
                    </div>
                </div>

                <div class="text-right">
                    <span
                        class="inline-block rounded-md bg-neutral-900 px-3 py-1 text-xs font-black tracking-wider text-white uppercase"
                    >
                        Relevé de Compte Client
                    </span>
                    <p class="mt-2 text-xs font-medium text-neutral-500">
                        Date d'édition : {{ currentDate }}
                    </p>
                    <p class="text-xs text-neutral-400">
                        Réf : REC-{{ client.id }}-{{ new Date().getFullYear() }}
                    </p>
                </div>
            </div>

            <!-- Client Info & Balances Box -->
            <div
                class="my-6 grid grid-cols-2 gap-6 rounded-xl border border-neutral-200 bg-neutral-50 p-4"
            >
                <div>
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                    >
                        Titulaire du compte
                    </p>
                    <h3 class="mt-0.5 text-base font-black text-neutral-900">
                        {{ client.name }}
                    </h3>
                    <p
                        v-if="client.telephone"
                        class="mt-0.5 text-xs text-neutral-600"
                    >
                        Tél : {{ client.telephone }}
                    </p>
                    <p v-if="client.address" class="text-xs text-neutral-600">
                        Adresse : {{ client.address }}
                    </p>
                </div>

                <div class="space-y-1 text-right">
                    <p
                        class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                    >
                        Situation financière actuelle
                    </p>
                    <div class="text-xl font-black text-neutral-900">
                        Solde FCFA :
                        <span
                            :class="
                                Number(client.solde_fcfa) > 0
                                    ? 'text-amber-700'
                                    : 'text-emerald-700'
                            "
                            >{{ formatFcfa(client.solde_fcfa) }}</span
                        >
                    </div>
                    <div class="text-sm font-bold text-neutral-700">
                        Solde Ouguiya :
                        <span>{{ formatOuguiya(client.solde_ouguiya) }}</span>
                    </div>
                </div>
            </div>

            <!-- Statement Table -->
            <div class="my-6 overflow-x-auto">
                <table
                    class="w-full border-collapse border border-neutral-300 text-left text-xs"
                >
                    <thead
                        class="border-b border-neutral-300 bg-neutral-100 text-[10px] font-bold tracking-wider uppercase"
                    >
                        <tr>
                            <th
                                class="w-8 border-r border-neutral-300 p-2 text-center"
                            >
                                N°
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 whitespace-nowrap"
                            >
                                Date
                            </th>
                            <th class="border-r border-neutral-300 p-2">
                                Libellé
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right"
                            >
                                Ouguiya
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right"
                            >
                                Pay. MRU
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right font-black"
                            >
                                Solde MRU
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right"
                            >
                                Taux MRU/XOF
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right"
                            >
                                FCFA
                            </th>
                            <th
                                class="border-r border-neutral-300 p-2 text-right"
                            >
                                Pay. FCFA
                            </th>
                            <th class="p-2 text-right font-black">
                                Solde FCFA
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        <tr
                            v-for="t in client.transactions"
                            :key="t.id"
                            class="border-b border-neutral-200"
                        >
                            <td
                                class="border-r border-neutral-300 p-2 text-center font-bold text-neutral-500"
                            >
                                {{ t.numero_operation }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 whitespace-nowrap text-neutral-600"
                            >
                                {{ formatShortDate(t.date_operation) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 font-medium"
                            >
                                {{
                                    t.description ||
                                    (t.type_operation === 'vente'
                                        ? 'Vente Ouguiya'
                                        : t.type_operation ===
                                            'remboursement_fcfa'
                                          ? 'Règlement FCFA'
                                          : 'Opération')
                                }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right whitespace-nowrap"
                            >
                                {{
                                    Number(t.ouguiya) > 0
                                        ? formatOuguiya(t.ouguiya)
                                        : '-'
                                }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right whitespace-nowrap"
                            >
                                {{
                                    Number(t.payement_ouguiya) > 0
                                        ? formatOuguiya(t.payement_ouguiya)
                                        : '-'
                                }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right font-bold whitespace-nowrap"
                            >
                                {{ formatOuguiya(t.solde_ouguiya_ligne) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right whitespace-nowrap"
                            >
                                {{ formatRate(t.rate) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right whitespace-nowrap"
                            >
                                {{
                                    Number(t.fcfa) > 0
                                        ? formatFcfa(t.fcfa)
                                        : '-'
                                }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2 text-right font-semibold whitespace-nowrap text-emerald-700"
                            >
                                {{
                                    Number(t.payement_fcfa) > 0
                                        ? formatFcfa(t.payement_fcfa)
                                        : '-'
                                }}
                            </td>
                            <td
                                class="p-2 text-right font-black whitespace-nowrap"
                            >
                                {{ formatFcfa(t.solde_fcfa_ligne) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        class="border-t-2 border-neutral-400 bg-neutral-100 text-[11px] font-bold"
                    >
                        <tr>
                            <td
                                colspan="3"
                                class="border-r border-neutral-300 p-2.5 text-right tracking-wider uppercase"
                            >
                                Totaux :
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-right whitespace-nowrap"
                            >
                                {{ formatOuguiya(totals.total_ouguiya) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-right whitespace-nowrap"
                            >
                                {{
                                    formatOuguiya(totals.total_payement_ouguiya)
                                }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-right font-black whitespace-nowrap text-sky-800"
                            >
                                {{ formatOuguiya(client.solde_ouguiya) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-center"
                            >
                                -
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-right whitespace-nowrap"
                            >
                                {{ formatFcfa(totals.total_fcfa) }}
                            </td>
                            <td
                                class="border-r border-neutral-300 p-2.5 text-right font-bold whitespace-nowrap text-emerald-800"
                            >
                                {{ formatFcfa(totals.total_payement_fcfa) }}
                            </td>
                            <td
                                class="p-2.5 text-right font-black whitespace-nowrap text-amber-900"
                            >
                                {{ formatFcfa(client.solde_fcfa) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures Section -->
            <div
                class="mt-12 grid grid-cols-2 gap-12 border-t border-neutral-200 pt-6"
            >
                <div class="text-center">
                    <p
                        class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Le Client (Bon pour accord)
                    </p>
                    <div
                        class="mt-2 h-20 border-b border-dashed border-neutral-300"
                    ></div>
                </div>
                <div class="text-center">
                    <p
                        class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                    >
                        Le Bureau de Change (Cachet & Signature)
                    </p>
                    <div
                        class="mt-2 h-20 border-b border-dashed border-neutral-300"
                    ></div>
                </div>
            </div>

            <!-- Footer note -->
            <div
                class="mt-8 border-t pt-4 text-center text-[10px] text-neutral-400"
            >
                Relevé certifié conforme aux écritures du journal des
                opérations. Merci de votre fidélité.
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
}
</style>
