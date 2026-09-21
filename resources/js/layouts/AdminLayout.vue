<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppFooter from '../components/AppFooter.vue'

defineProps<{
    title: string
}>()
</script>

<template>
    <div
        class="flex min-h-dvh flex-col bg-[#f3f1f6] text-[#2c2833] selection:bg-[#d7a4ec] selection:text-[#2b1735] dark:bg-[#101219] dark:text-[#e0dce8] dark:selection:bg-[#783f90] dark:selection:text-white"
    >
        <header
            class="border-b border-[#cec9d5] bg-[#fcfafd] px-4 dark:border-[#363845] dark:bg-[#191b24] sm:px-6"
        >
            <div
                class="mx-auto flex w-full max-w-[90rem] flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3"
            >
                <div>
                    <p class="font-mono text-xs font-medium text-[#b24d91] dark:text-[#e781bd]">
                        ~/admin
                    </p>
                    <h1 class="font-mono text-sm font-medium">{{ title }}</h1>
                </div>
                <nav
                    aria-label="Разделы админки"
                    class="flex flex-wrap items-center gap-4 font-mono text-xs font-medium"
                >
                    <RouterLink
                        :to="{ name: 'admin.quizzes' }"
                        class="text-[#447b9e] hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        active-class="text-[#b24d91]! dark:text-[#e781bd]!"
                    >
                        quizzes
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'admin.groups' }"
                        class="text-[#447b9e] hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        active-class="text-[#b24d91]! dark:text-[#e781bd]!"
                    >
                        groups
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'home' }"
                        class="text-[#777080] transition-colors hover:text-[#4d4653] dark:text-[#9792a5] dark:hover:text-[#c5c0cd]"
                    >
                        workspace
                    </RouterLink>
                </nav>
            </div>
        </header>
        <main class="mx-auto w-full max-w-[90rem] min-w-0 flex-1 px-4 py-5 sm:px-6">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
