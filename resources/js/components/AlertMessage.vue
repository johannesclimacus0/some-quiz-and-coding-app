<script setup lang="ts">
defineProps<{
    message?: string
}>()
</script>

<template>
    <div
        v-if="message"
        role="alert"
        class="flex items-start gap-3 border border-[#d7a9bf] bg-[#fff4f8] px-3 py-2.5 font-mono text-xs leading-5 dark:border-[#623d53] dark:bg-[#2d1d28]"
    >
        <span class="shrink-0 text-[#c14378] dark:text-[#f077a8]">[!]</span>
        <p class="min-w-0 flex-1 break-words text-[#544c5a] dark:text-[#d8d2de]">
            {{ message }}
        </p>
    </div>
</template>
