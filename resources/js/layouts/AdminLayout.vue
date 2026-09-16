<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppFooter from '../components/AppFooter.vue'

defineProps<{
    title: string
}>()
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-[#f2eff5] text-[#27232d] selection:bg-[#d7a4ec] selection:text-[#2b1735] dark:bg-[#0f1117] dark:text-[#e8e5ef] dark:selection:bg-[#783f90] dark:selection:text-white">
        <header class="border-b border-[#c9c1cf] bg-[#fbfafd] px-4 dark:border-[#343746] dark:bg-[#171920] sm:px-6">
            <div class="mx-auto flex w-full max-w-[90rem] flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3">
                <div>
                    <p class="font-mono text-xs font-medium text-[#b24d91] dark:text-[#e781bd]">~/admin</p>
                    <h1 class="font-mono text-sm font-medium">{{ title }}</h1>
                </div>
                <nav class="flex items-center gap-4 font-mono text-xs font-medium">
                    <RouterLink
                        :to="{ name: 'admin.dashboard' }"
                        class="text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
                        exact-active-class="text-[#b24d91]! dark:text-[#e781bd]!"
                    >overview</RouterLink>
                    <RouterLink
                        :to="{ name: 'home' }"
                        class="text-[#777080] transition-colors hover:text-[#4d4653] dark:text-[#918da0] dark:hover:text-[#c5c0cd]"
                    >workspace</RouterLink>
                </nav>
            </div>
        </header>
        <main class="mx-auto w-full max-w-[90rem] min-w-0 flex-1 px-4 py-5 sm:px-6">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
