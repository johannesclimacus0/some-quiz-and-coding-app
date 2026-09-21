<script setup lang="ts">
defineOptions({ inheritAttrs: false })

withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset'
        loading?: boolean
        loadingText?: string
        variant?: 'primary' | 'secondary'
    }>(),
    {
        type: 'button',
        loading: false,
        loadingText: 'Подождите…',
        variant: 'secondary',
    },
)
</script>

<template>
    <button
        class="inline-flex min-h-10 items-center justify-center gap-2 border px-3 py-1.5 font-mono text-xs transition-colors focus:outline-none focus:ring-1 focus:ring-[#1793d1]/40 disabled:cursor-not-allowed disabled:opacity-40"
        :class="
            variant === 'primary'
                ? 'border-[#1793d1] bg-[#1793d1]/10 text-[#447b9e] hover:bg-[#1793d1]/20 dark:text-[#8eb4d1] dark:hover:bg-[#1793d1]/20'
                : 'border-[#cec9d5] bg-[#fcfafd] text-[#2c2833] hover:border-[#9990a1] hover:bg-[#ede9f1] dark:border-[#363845] dark:bg-[#191b24] dark:text-[#e0dce8] dark:hover:border-[#626578] dark:hover:bg-[#232631]'
        "
        :type="type"
        v-bind="$attrs"
        :disabled="loading || Boolean($attrs.disabled)"
        :aria-busy="loading"
    >
        <slot v-if="!loading" />
        <template v-else>{{ loadingText }}</template>
    </button>
</template>
