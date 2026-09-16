<script setup lang="ts">
defineProps<{
    title?: string
}>()
</script>

<template>
    <section class="min-w-0">
        <header v-if="title || $slots.actions" class="flex flex-wrap items-center justify-between gap-2 border-y border-[#d8d1dc] bg-[#eeeaf2] px-3 py-2 dark:border-[#343746] dark:bg-[#1d2029]">
            <h2 class="font-mono text-xs font-medium text-[#68616f] dark:text-[#918da0]">
                <span class="mr-1.5 text-[#1793d1]">::</span>{{ title }}
            </h2>
            <slot name="actions" />
        </header>
        <slot />
    </section>
</template>
