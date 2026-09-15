<script setup lang="ts">
import { useConnectionStatus, useEchoPublic } from '@laravel/echo-vue'
import { ref } from 'vue'

const status = useConnectionStatus()
const messages = ref<string[]>([])

useEchoPublic<{ message: string }>(
    'reverb-test',
    'ReverbTest',
    (event) => {
        messages.value.unshift(event.message)
    },
)
</script>

<template>
    <p>status: {{ status }}</p>
    <p>msg: {{ messages[0] }}</p>
</template>
