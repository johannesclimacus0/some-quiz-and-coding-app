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
        class="inline-flex min-h-8 items-center justify-center gap-2 border px-3 py-1.5 font-mono text-xs transition-colors focus:outline-none focus:ring-1 focus:ring-[#1793d1]/40 disabled:cursor-not-allowed disabled:opacity-40"
        :class="
            variant === 'primary'
                ? 'border-[#bdb2c2] bg-[#ddd6e1] text-[#3d3541] hover:border-[#aea2b4] hover:bg-[#d1c7d5] dark:border-[#555765] dark:bg-[#393b47] dark:text-[#ebe7ee] dark:hover:border-[#686b7a] dark:hover:bg-[#474956]'
                : 'border-[#c9c1cf] bg-[#fbfafd] text-[#27232d] hover:border-[#9990a1] hover:bg-[#eeeaf2] dark:border-[#3b3d4d] dark:bg-[#191b24] dark:text-[#e8e5ef] dark:hover:border-[#626578] dark:hover:bg-[#232631]'
        "
        :type="type"
        v-bind="$attrs"
        :disabled="loading || Boolean($attrs.disabled)"
    >
        <slot v-if="!loading" />
        <template v-else>{{ loadingText }}</template>
    </button>
</template>
