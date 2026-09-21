<script setup lang="ts">
import { useTheme, type ThemePreference } from '../composables/useTheme'

const { preference, setTheme } = useTheme()
const options: Array<{ value: ThemePreference; label: string }> = [
    { value: 'system', label: 'auto' },
    { value: 'light', label: 'light' },
    { value: 'dark', label: 'dark' },
]
</script>

<template>
    <div class="flex items-center gap-1 font-mono text-xs font-medium">
        <span class="mr-1 text-[#777080] dark:text-[#85819a]">theme</span>
        <button
            @click="setTheme(option.value)"
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-pressed="preference === option.value"
            :aria-label="`Тема: ${option.label}`"
            class="px-1.5 py-0.5 transition-colors hover:text-[#1793d1] dark:hover:text-[#8eb4d1]"
            :class="
                preference === option.value
                    ? 'bg-[#1793d1]/10 text-[#447b9e] dark:text-[#8eb4d1]'
                    : 'text-[#777080] dark:text-[#85819a]'
            "
        >
            {{ option.label }}
        </button>
    </div>
</template>
