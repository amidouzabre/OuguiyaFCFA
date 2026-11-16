<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Check,
    Pencil,
    Plus,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    UserCheck,
    UserPlus,
    Users,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { formatShortDate } from '@/lib/formatters';
import type { User } from '@/types';

const props = defineProps<{
    users: Array<User & { role?: string; telephone?: string | null }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: '/dashboard',
            },
            {
                title: 'Utilisateurs',
                href: '/users',
            },
        ],
    },
});

const currentUser = usePage().props.auth.user as User;

const isModalOpen = ref(false);
const editingUser = ref<(User & { role?: string; telephone?: string | null }) | null>(null);
const isEditing = computed(() => !!editingUser.value);

const form = ref({
    name: '',
    email: '',
    role: 'user',
    telephone: '',
    password: '',
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

function openCreateUser() {
    editingUser.value = null;
    form.value = {
        name: '',
        email: '',
        role: 'user',
        telephone: '',
        password: '',
    };
    errors.value = {};
    isModalOpen.value = true;
}

function openEditUser(user: User & { role?: string; telephone?: string | null }) {
    editingUser.value = user;
    form.value = {
        name: user.name,
        email: user.email,
        role: user.role || 'user',
        telephone: user.telephone || '',
        password: '',
    };
    errors.value = {};
    isModalOpen.value = true;
}

function deleteUser(user: User) {
    if (user.id === currentUser.id) {
        alert('Vous ne pouvez pas supprimer votre propre compte utilisateur.');
        return;
    }
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${user.name}" ?`)) {
        router.delete(`/users/${user.id}`);
    }
}

function handleSubmit() {
    errors.value = {};
    isSubmitting.value = true;

    if (isEditing.value && editingUser.value) {
        router.put(`/users/${editingUser.value.id}`, form.value, {
            onSuccess: () => {
                isSubmitting.value = false;
                isModalOpen.value = false;
            },
            onError: (err) => {
                isSubmitting.value = false;
                errors.value = err;
            },
        });
    } else {
        router.post('/users', form.value, {
            onSuccess: () => {
                isSubmitting.value = false;
                isModalOpen.value = false;
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
    <Head title="Gestion des Utilisateurs - OuguiyaCFA" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
        <!-- Top Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-5">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-foreground flex items-center gap-2">
                    Gestion des Utilisateurs & Rôles
                    <span class="rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-0.5 text-xs font-semibold">
                        {{ users.length }} utilisateur(s)
                    </span>
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Gérez les administrateurs et opérateurs autorisés à saisir et consulter les opérations de change.
                </p>
            </div>

            <Button
                @click="openCreateUser"
                class="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm text-xs font-semibold self-start sm:self-auto"
            >
                <UserPlus class="size-4" />
                Nouvel Utilisateur
            </Button>
        </div>

        <!-- Users Table -->
        <Card class="border-border/70 shadow-sm overflow-hidden">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-muted/50 text-muted-foreground font-semibold border-b uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="p-3 pl-4">Nom</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Rôle</th>
                                <th class="p-3">Téléphone</th>
                                <th class="p-3">Date de création</th>
                                <th class="p-3 text-center pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="u in users"
                                :key="u.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <td class="p-3 pl-4 font-bold text-foreground whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="flex size-7 items-center justify-center rounded-full bg-muted font-bold text-xs uppercase text-muted-foreground">
                                            {{ u.name.substring(0, 2) }}
                                        </div>
                                        <span>{{ u.name }}</span>
                                        <span v-if="u.id === currentUser.id" class="rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-1.5 py-0.2 text-[10px] font-bold">
                                            Vous
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 text-muted-foreground whitespace-nowrap">
                                    {{ u.email }}
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span
                                        v-if="u.role === 'admin'"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300"
                                    >
                                        <ShieldCheck class="size-3" /> Administrateur
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300"
                                    >
                                        <UserCheck class="size-3" /> Opérateur
                                    </span>
                                </td>
                                <td class="p-3 text-muted-foreground whitespace-nowrap">
                                    {{ u.telephone || '-' }}
                                </td>
                                <td class="p-3 text-muted-foreground whitespace-nowrap">
                                    {{ formatShortDate(u.created_at) }}
                                </td>
                                <td class="p-3 text-center pr-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground"
                                            title="Modifier"
                                            @click="openEditUser(u)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            v-if="u.id !== currentUser.id"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                            title="Supprimer"
                                            @click="deleteUser(u)"
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

    <!-- User Modal -->
    <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <Users class="size-4.5" />
                    </span>
                    {{ isEditing ? "Modifier l'utilisateur" : 'Nouvel utilisateur' }}
                </DialogTitle>
                <DialogDescription>
                    Configurez l'accès au système et attribuez les permissions nécessaires.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-4 py-2">
                <div class="space-y-1">
                    <Label for="user_name" class="text-sm font-semibold">Nom complet *</Label>
                    <Input id="user_name" type="text" v-model="form.name" required placeholder="ex: Nom Prénom" />
                    <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                </div>

                <div class="space-y-1">
                    <Label for="user_email" class="text-sm font-semibold">Adresse Email *</Label>
                    <Input id="user_email" type="email" v-model="form.email" required placeholder="utilisateur@domaine.com" />
                    <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <Label for="user_role" class="text-xs font-semibold">Rôle *</Label>
                        <select
                            id="user_role"
                            v-model="form.role"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs"
                        >
                            <option value="admin">Administrateur</option>
                            <option value="user">Opérateur</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label for="user_phone" class="text-xs font-semibold">Téléphone</Label>
                        <Input id="user_phone" type="text" v-model="form.telephone" placeholder="0707108448" />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label for="user_password" class="text-sm font-semibold">
                        {{ isEditing ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe *' }}
                    </Label>
                    <Input
                        id="user_password"
                        type="password"
                        v-model="form.password"
                        :required="!isEditing"
                        placeholder="••••••••"
                    />
                    <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
                </div>

                <DialogFooter class="flex sm:justify-between items-center gap-2 pt-2">
                    <Button type="button" variant="outline" @click="isModalOpen = false">
                        Annuler
                    </Button>
                    <Button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5" :disabled="isSubmitting">
                        <Check class="size-4" />
                        {{ isSubmitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer utilisateur') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
