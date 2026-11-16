<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MessageCircle, Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatFcfa, formatOuguiya, formatRate, formatShortDate } from '@/lib/formatters';
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

function getWhatsAppUrl(phone?: string | null, clientName = '', soldeFcfa = '') {
    if (!phone) return '#';
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const message = encodeURIComponent(
        `Bonjour ${clientName}, voici votre relevé de compte de change Ouguiya/FCFA arrêté au ${currentDate}. Votre solde FCFA restant dû est de ${soldeFcfa}. Merci de votre confiance.`
    );
    return `https://wa.me/${cleanPhone}?text=${message}`;
}
</script>

<template>
    <Head :title="`Relevé de Compte - ${client.name} - A4 Print`" />

    <div class="min-h-screen bg-neutral-100 dark:bg-neutral-900 py-6 px-4 print:p-0 print:bg-white text-neutral-900">
        <!-- Floating Action Header (Hidden in Print) -->
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <Link :href="`/clients/${client.id}`">
                <Button variant="outline" size="sm" class="gap-1.5 shadow-sm">
                    <ArrowLeft class="size-4" />
                    Retour à la fiche client
                </Button>
            </Link>

            <div class="flex items-center gap-2">
                <a
                    v-if="client.telephone"
                    :href="getWhatsAppUrl(client.telephone, client.name, formatFcfa(client.solde_fcfa))"
                    target="_blank"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-sm"
                >
                    <MessageCircle class="size-4" />
                    Envoyer par WhatsApp
                </a>
                <Button @click="printStatement" class="gap-1.5 bg-neutral-900 text-white hover:bg-black shadow-sm text-xs font-bold">
                    <Printer class="size-4" />
                    Imprimer / Enregistrer en PDF
                </Button>
            </div>
        </div>

        <!-- A4 Page Container -->
        <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 shadow-md rounded-xl print:shadow-none print:p-0 print:rounded-none border border-neutral-200 print:border-none">
            <!-- Header Section -->
            <div class="flex items-start justify-between border-b pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="size-9 rounded-lg bg-emerald-600 text-white font-black text-lg flex items-center justify-center">
                            ⇄
                        </div>
                        <div>
                            <h2 class="text-xl font-black uppercase tracking-tight text-neutral-900">
                                Ouguiya<span class="text-emerald-600">CFA</span>
                            </h2>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                Bureau de Change & Opérations Commerciales
                            </p>
                        </div>
                    </div>
                    <div class="text-xs text-neutral-500 mt-3 space-y-0.5">
                        <p>Change Devises : Ouguiya (MRU) / Franc CFA (XOF)</p>
                        <p>Transferts & Règlements Commerciaux</p>
                    </div>
                </div>

                <div class="text-right">
                    <span class="inline-block rounded-md bg-neutral-900 text-white px-3 py-1 text-xs font-black uppercase tracking-wider">
                        Relevé de Compte Client
                    </span>
                    <p class="text-xs text-neutral-500 mt-2 font-medium">Date d'édition : {{ currentDate }}</p>
                    <p class="text-xs text-neutral-400">Réf : REC-{{ client.id }}-{{ new Date().getFullYear() }}</p>
                </div>
            </div>

            <!-- Client Info & Balances Box -->
            <div class="grid grid-cols-2 gap-6 my-6 p-4 rounded-xl bg-neutral-50 border border-neutral-200">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Titulaire du compte</p>
                    <h3 class="text-base font-black text-neutral-900 mt-0.5">{{ client.name }}</h3>
                    <p v-if="client.telephone" class="text-xs text-neutral-600 mt-0.5">Tél : {{ client.telephone }}</p>
                    <p v-if="client.address" class="text-xs text-neutral-600">Adresse : {{ client.address }}</p>
                </div>

                <div class="text-right space-y-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Situation financière actuelle</p>
                    <div class="text-xl font-black text-neutral-900">
                        Solde FCFA : <span :class="Number(client.solde_fcfa) > 0 ? 'text-amber-700' : 'text-emerald-700'">{{ formatFcfa(client.solde_fcfa) }}</span>
                    </div>
                    <div class="text-sm font-bold text-neutral-700">
                        Solde Ouguiya : <span>{{ formatOuguiya(client.solde_ouguiya) }}</span>
                    </div>
                </div>
            </div>

            <!-- Statement Table -->
            <div class="overflow-x-auto my-6">
                <table class="w-full text-left text-xs border border-neutral-300 border-collapse">
                    <thead class="bg-neutral-100 font-bold uppercase tracking-wider text-[10px] border-b border-neutral-300">
                        <tr>
                            <th class="p-2 border-r border-neutral-300 text-center w-8">N°</th>
                            <th class="p-2 border-r border-neutral-300 whitespace-nowrap">Date</th>
                            <th class="p-2 border-r border-neutral-300">Libellé</th>
                            <th class="p-2 border-r border-neutral-300 text-right">Ouguiya</th>
                            <th class="p-2 border-r border-neutral-300 text-right">Pay. MRU</th>
                            <th class="p-2 border-r border-neutral-300 text-right font-black">Solde MRU</th>
                            <th class="p-2 border-r border-neutral-300 text-right">Taux MRU/XOF</th>
                            <th class="p-2 border-r border-neutral-300 text-right">FCFA</th>
                            <th class="p-2 border-r border-neutral-300 text-right">Pay. FCFA</th>
                            <th class="p-2 text-right font-black">Solde FCFA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                        <tr v-for="t in client.transactions" :key="t.id" class="border-b border-neutral-200">
                            <td class="p-2 border-r border-neutral-300 text-center font-bold text-neutral-500">
                                {{ t.numero_operation }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 whitespace-nowrap text-neutral-600">
                                {{ formatShortDate(t.date_operation) }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 font-medium">
                                {{ t.description || (t.type_operation === 'vente' ? 'Vente Ouguiya' : (t.type_operation === 'remboursement_fcfa' ? 'Règlement FCFA' : 'Opération')) }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ Number(t.ouguiya) > 0 ? formatOuguiya(t.ouguiya) : '-' }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ Number(t.payement_ouguiya) > 0 ? formatOuguiya(t.payement_ouguiya) : '-' }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap font-bold">
                                {{ formatOuguiya(t.solde_ouguiya_ligne) }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ formatRate(t.rate) }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ Number(t.fcfa) > 0 ? formatFcfa(t.fcfa) : '-' }}
                            </td>
                            <td class="p-2 border-r border-neutral-300 text-right whitespace-nowrap font-semibold text-emerald-700">
                                {{ Number(t.payement_fcfa) > 0 ? formatFcfa(t.payement_fcfa) : '-' }}
                            </td>
                            <td class="p-2 text-right whitespace-nowrap font-black">
                                {{ formatFcfa(t.solde_fcfa_ligne) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-neutral-100 font-bold border-t-2 border-neutral-400 text-[11px]">
                        <tr>
                            <td colspan="3" class="p-2.5 text-right uppercase tracking-wider border-r border-neutral-300">
                                Totaux :
                            </td>
                            <td class="p-2.5 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ formatOuguiya(totals.total_ouguiya) }}
                            </td>
                            <td class="p-2.5 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ formatOuguiya(totals.total_payement_ouguiya) }}
                            </td>
                            <td class="p-2.5 border-r border-neutral-300 text-right whitespace-nowrap font-black text-sky-800">
                                {{ formatOuguiya(client.solde_ouguiya) }}
                            </td>
                            <td class="p-2.5 border-r border-neutral-300 text-center">-</td>
                            <td class="p-2.5 border-r border-neutral-300 text-right whitespace-nowrap">
                                {{ formatFcfa(totals.total_fcfa) }}
                            </td>
                            <td class="p-2.5 border-r border-neutral-300 text-right whitespace-nowrap font-bold text-emerald-800">
                                {{ formatFcfa(totals.total_payement_fcfa) }}
                            </td>
                            <td class="p-2.5 text-right whitespace-nowrap font-black text-amber-900">
                                {{ formatFcfa(client.solde_fcfa) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-2 gap-12 mt-12 pt-6 border-t border-neutral-200">
                <div class="text-center">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Le Client (Bon pour accord)</p>
                    <div class="h-20 border-b border-dashed border-neutral-300 mt-2"></div>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Le Bureau de Change (Cachet & Signature)</p>
                    <div class="h-20 border-b border-dashed border-neutral-300 mt-2"></div>
                </div>
            </div>

            <!-- Footer note -->
            <div class="text-center text-[10px] text-neutral-400 mt-8 pt-4 border-t">
                Relevé certifié conforme aux écritures du journal des opérations. Merci de votre fidélité.
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
