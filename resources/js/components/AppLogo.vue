<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-500/20"
    >
        <AppLogoIcon class="size-4.5 fill-current text-white" />
    </div>
    <div class="ml-2 grid flex-1 text-left">
        <span class="truncate text-sm font-bold tracking-tight text-foreground">Ouguiya<span class="text-emerald-600 dark:text-emerald-400">CFA</span></span>
        <span class="truncate text-[10px] uppercase font-semibold tracking-wider text-muted-foreground">Gestion de Change</span>
    </div>
</template>
