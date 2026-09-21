<script setup lang="ts">
const props = defineProps<{
    id: string
    label: string
    modelValue: string
    error?: string
    modelModifiers?: Record<string, boolean>
}>()

defineOptions({ inheritAttrs: false })

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const updateValue = (event: Event): void => {
    const input = event.target as HTMLInputElement
    const value = props.modelModifiers?.trim ? input.value.trim() : input.value

    emit('update:modelValue', value)
}
</script>

<template>
    <div class="space-y-2">
        <label
            class="block font-mono text-xs font-medium text-[#686171] dark:text-[#9792a5]"
            :for="id"
        >
            {{ label }}
        </label>
        <input
            @input="updateValue"
            class="w-full min-w-0 border border-[#cec9d5] bg-[#fcfafd] px-2.5 py-2 font-mono text-xs text-[#2c2833] outline-none transition-colors placeholder:text-[#827a8b] focus:border-[#1793d1] focus:ring-1 focus:ring-[#1793d1]/30 dark:border-[#363845] dark:bg-[#101219] dark:text-[#e0dce8] dark:placeholder:text-[#9792a5]"
            :class="{ 'border-[#c14378]! dark:border-[#f077a8]!': error }"
            :id="id"
            :name="id"
            :value="modelValue"
            v-bind="$attrs"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
        />
        <p
            v-if="error"
            :id="`${id}-error`"
            class="flex items-start gap-2 font-mono text-xs font-medium leading-5 text-[#686171] dark:text-[#9792a5]"
        >
            <span class="shrink-0 text-[#c14378] dark:text-[#f077a8]">!</span>
            <span>{{ error }}</span>
        </p>
    </div>
</template>
