<script setup lang="ts">
import FontScaleControl from '../components/FontScaleControl.vue'
import ThemeToggle from '../components/ThemeToggle.vue'

defineProps<{
    title: string
}>()
</script>

<template>
    <main
        class="flex min-h-dvh items-center justify-center bg-[#f2eff5] px-5 py-12 text-[#27232d] selection:bg-[#d7a4ec] selection:text-[#2b1735] dark:bg-[#0f1117] dark:text-[#e8e5ef] dark:selection:bg-[#783f90] dark:selection:text-white"
    >
        <section
            class="w-full max-w-md overflow-hidden rounded-md border border-[#c9c1cf] bg-[#fbfafd] shadow-[0_16px_45px_rgba(64,45,78,0.12)] dark:border-[#343746] dark:bg-[#171920] dark:shadow-[0_18px_55px_rgba(0,0,0,0.42)]"
        >
            <div
                class="flex items-center justify-between border-b border-[#d8d1dc] bg-[#eeeaf2] px-3 py-2 font-mono text-xs font-medium text-[#777080] dark:border-[#343746] dark:bg-[#1d2029] dark:text-[#85899a]"
            >
                <span>auth@localhost</span>
                <span class="text-[#1793d1]">tty1</span>
            </div>
            <div class="px-5 py-6 sm:px-7">
                <header class="mb-6 border-b border-[#d8d1dc] pb-4 dark:border-[#343746]">
                    <p
                        class="mb-2 font-mono text-xs font-medium text-[#b24d91] dark:text-[#e781bd]"
                    >
                        ~/auth
                    </p>
                    <h1 class="font-mono text-base font-medium">{{ title }}</h1>
                </header>
                <div class="space-y-4"><slot /></div>
                <div
                    v-if="$slots.footer"
                    class="mt-6 border-t border-[#d8d1dc] pt-4 font-mono text-xs text-[#7137b3] dark:border-[#343746] dark:text-[#ca83dd]"
                >
                    <slot name="footer" />
                </div>
            </div>
            <footer
                class="flex flex-wrap items-center justify-between gap-3 border-t border-[#d8d1dc] bg-[#eeeaf2] px-3 py-1.5 dark:border-[#343746] dark:bg-[#1d2029]"
            >
                <span class="font-mono text-xs font-medium text-[#1793d1]">● guest</span>
                <div class="flex flex-wrap items-center gap-3">
                    <FontScaleControl />
                    <ThemeToggle />
                </div>
            </footer>
        </section>
    </main>
</template>
