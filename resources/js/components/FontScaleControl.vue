<script setup lang="ts">
import { useFontScale } from '../composables/useFontScale'

const {
    scale,
    minimum,
    maximum,
    decrease,
    increase,
    reset
} = useFontScale()
</script>

<template>
    <div class="flex items-center gap-1 font-mono text-xs font-medium">
        <span class="mr-1 text-[#777080] dark:text-[#85819a]">font</span>
        <button @click="decrease"
                type="button"
                :disabled="scale <= minimum"
                class="px-1.5 py-0.5 text-[#777080] transition-colors hover:bg-[#ded8e3] hover:text-[#287da8] disabled:cursor-not-allowed disabled:opacity-30 dark:text-[#9894a3] dark:hover:bg-[#30333e] dark:hover:text-[#65b7df]"
        >−</button>
        <button @click="reset"
                type="button"
                class="min-w-11 px-1 py-0.5 text-center tabular-nums text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
        >{{ scale }}%
        </button>
        <button @click="increase"
                type="button"
                :disabled="scale >= maximum"
                class="px-1.5 py-0.5 text-[#777080] transition-colors hover:bg-[#ded8e3] hover:text-[#287da8] disabled:cursor-not-allowed disabled:opacity-30 dark:text-[#9894a3] dark:hover:bg-[#30333e] dark:hover:text-[#65b7df]"
        >+</button>
    </div>
</template>
