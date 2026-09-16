<script setup lang="ts">
import AppFooter from '../components/AppFooter.vue'

defineProps<{
    title: string
}>()
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-[#f2eff5] text-[#27232d] selection:bg-[#d7a4ec] selection:text-[#2b1735] dark:bg-[#0f1117] dark:text-[#e8e5ef] dark:selection:bg-[#783f90] dark:selection:text-white">
        <header class="border-b border-[#c9c1cf] bg-[#fbfafd] px-4 dark:border-[#343746] dark:bg-[#171920] sm:px-6">
            <div class="mx-auto flex w-full max-w-[90rem] items-center justify-between gap-4 py-3">
                <div>
                    <p class="font-mono text-xs font-medium text-[#b24d91] dark:text-[#e781bd]">~/workspace</p>
                    <h1 class="font-mono text-sm font-medium">{{ title }}</h1>
                </div>
                <slot name="header-actions" />
            </div>
        </header>
        <main class="mx-auto w-full max-w-[90rem] min-w-0 flex-1 px-4 py-5 sm:px-6">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
