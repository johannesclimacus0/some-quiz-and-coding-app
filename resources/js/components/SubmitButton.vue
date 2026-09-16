<script setup lang="ts">
import BaseButton from './BaseButton.vue'

withDefaults(defineProps<{
    loading?: boolean
    loadingText?: string
}>(), {
    loading: false,
    loadingText: 'Подождите…',
})
</script>

<template>
    <BaseButton type="submit" variant="primary" :loading="loading" :loading-text="loadingText">
        <slot />
    </BaseButton>
</template>
