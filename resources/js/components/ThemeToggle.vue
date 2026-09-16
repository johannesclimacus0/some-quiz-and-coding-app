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
        <button @click="setTheme(option.value)"
                v-for="option in options"
                :key="option.value"
                type="button"
                class="px-1.5 py-0.5 transition-colors hover:text-[#8c4fd6] focus:outline-none dark:hover:text-[#d68adb]"
                :class="preference === option.value
                    ? 'bg-[#e6d9f2] text-[#7137b3] dark:bg-[#382742] dark:text-[#e39aca]'
                    : 'text-[#777080] dark:text-[#85819a]'"
        >{{ option.label }}</button>
    </div>
</template>
