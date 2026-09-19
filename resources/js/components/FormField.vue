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
            class="block font-mono text-xs font-medium text-[#68616f] dark:text-[#918da0]"
            :for="id"
        >
            {{ label }}
        </label>
        <input
            @input="updateValue"
            class="w-full min-w-0 rounded-sm border border-[#c9c1cf] bg-[#fbfafd] px-2.5 py-2 font-mono text-xs text-[#27232d] outline-none transition-colors placeholder:text-[#96909e] focus:border-[#1793d1] focus:ring-1 focus:ring-[#1793d1]/30 dark:border-[#3b3d4d] dark:bg-[#11131a] dark:text-[#e8e5ef] dark:placeholder:text-[#656879]"
            :class="{ 'border-[#c14378]! dark:border-[#f077a8]!': error }"
            :id="id"
            :name="id"
            :value="modelValue"
            v-bind="$attrs"
        />
        <p
            v-if="error"
            class="flex items-start gap-2 font-mono text-xs font-medium leading-5 text-[#68616f] dark:text-[#918da0]"
        >
            <span class="shrink-0 text-[#c14378] dark:text-[#f077a8]">!</span>
            <span>{{ error }}</span>
        </p>
    </div>
</template>
