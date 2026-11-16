<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calculator,
    CheckCircle2,
    DollarSign,
    FileSpreadsheet,
    FileText,
    LogIn,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
    Wallet,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    has_users: boolean;
}>();
</script>

<template>
    <Head title="OuguiyaCFA - Gestion de Change Ouguiya (MRU) & FCFA (XOF)" />

    <div class="min-h-screen bg-gradient-to-b from-background via-muted/30 to-background flex flex-col justify-between text-foreground">
        <!-- Navigation Header -->
        <header class="border-b bg-background/80 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-6 h-16">
                <div class="flex items-center gap-2">
                    <AppLogo />
                </div>

                <nav class="flex items-center gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link href="/dashboard">
                            <Button class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-1.5 shadow-sm">
                                Tableau de bord <ArrowRight class="size-4" />
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/login">
                            <Button variant="ghost" class="text-xs font-semibold gap-1.5">
                                <LogIn class="size-4" /> Se connecter
                            </Button>
                        </Link>
                        <Link href="/register">
                            <Button class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs gap-1.5 shadow-sm">
                                Inscription
                            </Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Hero Section -->
        <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-20 flex flex-col items-center justify-center text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/50 px-3.5 py-1 text-xs font-bold text-emerald-800 dark:text-emerald-300 mb-6 shadow-sm">
                <Sparkles class="size-3.5 text-emerald-600" />
                <span>Application de Gestion de Change Spécialisée Ouguiya ⇄ FCFA</span>
            </div>

            <!-- Title -->
            <h1 class="text-4xl sm:text-6xl font-black tracking-tight max-w-3xl leading-tight">
                Gérez vos opérations de change
                <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent">
                    Ouguiya - FCFA
                </span>
            </h1>

            <p class="text-base sm:text-lg text-muted-foreground mt-4 max-w-2xl">
                Suivi précis des comptes clients en Ouguiya (MRU) et Franc CFA (XOF). Calcul automatique des soldes cumulés, gestion des remboursements et suivi des bénéfices en temps réel.
            </p>

            <!-- Admin Setup Alert if has_users is false -->
            <div v-if="!has_users" class="mt-8 p-6 rounded-2xl bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/60 max-w-lg w-full text-left shadow-md">
                <div class="flex items-start gap-3">
                    <ShieldCheck class="size-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div>
                        <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            Initialisation du système requise
                        </h2>
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">
                            Aucun compte n'a encore été créé. Veuillez configurer le compte Administrateur pour commencer à utiliser l'application.
                        </p>
                        <Link href="/register" class="mt-3 inline-block">
                            <Button class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold gap-1.5 shadow-sm">
                                Créer le compte Administrateur
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- CTAs if users exist -->
            <div v-else class="mt-8 flex flex-col sm:flex-row items-center gap-3">
                <template v-if="$page.props.auth?.user">
                    <Link href="/dashboard">
                        <Button size="lg" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-2 text-sm shadow-md">
                            Accéder au Tableau de Bord <ArrowRight class="size-4" />
                        </Button>
                    </Link>
                </template>
                <template v-else>
                    <Link href="/login">
                        <Button size="lg" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-2 text-sm shadow-md">
                            Se connecter à l'espace de change <LogIn class="size-4" />
                        </Button>
                    </Link>
                    <Link href="/register">
                        <Button size="lg" variant="outline" class="text-sm font-bold">
                            Créer un compte
                        </Button>
                    </Link>
                </template>
            </div>

            <!-- 4 Features Highlights Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-16 text-left w-full">
                <!-- Feature 1 -->
                <div class="rounded-2xl border border-border/80 bg-card p-5 shadow-sm space-y-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <FileSpreadsheet class="size-5" />
                    </span>
                    <h2 class="font-bold text-sm text-foreground">Remplacement Excel</h2>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Fini les erreurs de formules et les classeurs lourds. Chaque opération s'incrémente avec son numéro et ses soldes cumulés.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-2xl border border-border/80 bg-card p-5 shadow-sm space-y-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        <Wallet class="size-5" />
                    </span>
                    <h2 class="font-bold text-sm text-foreground">Double Solde MRU & FCFA</h2>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Visualisez immédiatement ce que le client vous doit en FCFA et ce qu'il détient en Ouguiya, avec rappel WhatsApp direct.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-2xl border border-border/80 bg-card p-5 shadow-sm space-y-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                        <TrendingUp class="size-5" />
                    </span>
                    <h2 class="font-bold text-sm text-foreground">Calculateur & Marges</h2>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Calcul automatique du taux de change, de la contrevaleur et des bénéfices réalisés entre le taux d'achat et le taux de vente.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="rounded-2xl border border-border/80 bg-card p-5 shadow-sm space-y-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                        <FileText class="size-5" />
                    </span>
                    <h2 class="font-bold text-sm text-foreground">Reçus & Relevés A4</h2>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        Imprimez des reçus de caisse ou des relevés A4 officiels prêts à être envoyés en PDF ou partagés sur WhatsApp.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t py-6 text-center text-xs text-muted-foreground">
            <p>© {{ new Date().getFullYear() }} OuguiyaCFA — Application Professionnelle de Gestion de Change.</p>
        </footer>
    </div>
</template>
