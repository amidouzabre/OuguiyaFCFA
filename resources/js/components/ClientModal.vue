<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, UserPlus } from '@lucide/vue';
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
import type { Client } from '@/types';

const props = defineProps<{
    open: boolean;
    client?: Client | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const isEditing = computed(() => !!props.client);

const form = ref({
    name: '',
    telephone: '',
    address: '',
    notes: '',
    solde_initial_fcfa: '' as number | string,
    solde_initial_ouguiya: '' as number | string,
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        errors.value = {};
        if (props.client) {
            form.value = {
                name: props.client.name || '',
                telephone: props.client.telephone || '',
                address: props.client.address || '',
                notes: props.client.notes || '',
                solde_initial_fcfa: '',
                solde_initial_ouguiya: '',
            };
        } else {
            form.value = {
                name: '',
                telephone: '',
                address: '',
                notes: '',
                solde_initial_fcfa: '',
                solde_initial_ouguiya: '',
            };
        }
    },
    { immediate: true }
);

function handleSubmit() {
    errors.value = {};
    if (!form.value.name.trim()) {
        errors.value.name = 'Le nom du client est obligatoire.';
        return;
    }

    isSubmitting.value = true;

    if (isEditing.value && props.client) {
        router.put(`/clients/${props.client.id}`, {
            name: form.value.name,
            telephone: form.value.telephone,
            address: form.value.address,
            notes: form.value.notes,
        }, {
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
        router.post('/clients', form.value, {
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
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <UserPlus class="size-4.5" />
                    </span>
                    {{ isEditing ? 'Modifier la fiche client' : 'Ajouter un nouveau client' }}
                </DialogTitle>
                <DialogDescription>
                    Créez un compte client pour suivre ses opérations de change et ses soldes Ouguiya / FCFA.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-4 py-2">
                <div class="space-y-1">
                    <Label for="client_name" class="text-sm font-semibold">Nom complet du client *</Label>
                    <Input
                        id="client_name"
                        type="text"
                        v-model="form.name"
                        placeholder="ex: ZABRE AMIDOU, Salam Kaboré..."
                        required
                    />
                    <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <Label for="client_phone" class="text-xs font-medium">Téléphone (WhatsApp)</Label>
                        <Input
                            id="client_phone"
                            type="text"
                            v-model="form.telephone"
                            placeholder="ex: 0707108448"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label for="client_address" class="text-xs font-medium">Ville / Adresse</Label>
                        <Input
                            id="client_address"
                            type="text"
                            v-model="form.address"
                            placeholder="ex: Abidjan, Nouakchott..."
                        />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label for="client_notes" class="text-xs font-medium">Notes / Remarques</Label>
                    <textarea
                        id="client_notes"
                        v-model="form.notes"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        placeholder="Informations complémentaires, préférences de change..."
                    ></textarea>
                </div>

                <!-- Initial Balance (Only when creating a new client) -->
                <div v-if="!isEditing" class="rounded-xl border border-dashed border-border/80 bg-muted/30 p-3 space-y-2">
                    <div class="text-xs font-semibold text-foreground flex items-center justify-between">
                        <span>Solde initial de départ (Optionnel)</span>
                        <span class="text-[10px] text-muted-foreground">Report Excel précédent</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label for="init_fcfa" class="text-[11px] font-medium text-muted-foreground">
                                Solde FCFA initial
                            </Label>
                            <Input
                                id="init_fcfa"
                                type="number"
                                step="any"
                                v-model="form.solde_initial_fcfa"
                                placeholder="0 (si dette client)"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label for="init_mru" class="text-[11px] font-medium text-muted-foreground">
                                Solde Ouguiya initial
                            </Label>
                            <Input
                                id="init_mru"
                                type="number"
                                step="any"
                                v-model="form.solde_initial_ouguiya"
                                placeholder="0 (si dette client)"
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex sm:justify-between items-center gap-2 pt-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Annuler
                    </Button>
                    <Button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5" :disabled="isSubmitting">
                        <Check class="size-4" />
                        {{ isSubmitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer le client') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
