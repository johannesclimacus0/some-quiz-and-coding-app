<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { TextInput } from '../../api/quizzes'
import FormField from '../FormField.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps<{
    id: string
    label: string
    value?: TextInput
    maxLength: number
    busy: boolean
    errors?: Record<string, string[]>
    submitLabel?: string
}>()
const emit = defineEmits<{ save: [input: TextInput] }>()
const form = reactive({ text: '', position: '0' })
watch(
    [() => props.value?.text, () => props.value?.position],
    ([text, position]) => {
        form.text = text ?? ''
        form.position = String(position ?? 0)
    },
    { immediate: true },
)
</script>

<template>
    <form @submit.prevent="emit('save', { text: form.text, position: Number(form.position) })">
        <fieldset
            :disabled="busy"
            class="flex flex-wrap items-end gap-3"
        >
            <div class="min-w-0 flex-1 basis-64">
                <FormField
                    :id="id + '-text'"
                    v-model="form.text"
                    :label="label"
                    required
                    :maxlength="maxLength"
                    :error="errors?.text?.[0]"
                />
            </div>
            <div class="w-28">
                <FormField
                    :id="id + '-position'"
                    v-model="form.position"
                    label="Позиция"
                    type="number"
                    min="0"
                    max="65535"
                    step="1"
                    required
                    :error="errors?.position?.[0]"
                />
            </div>
            <BaseButton
                type="submit"
                :loading="busy"
            >
                {{ submitLabel ?? 'Сохранить' }}
            </BaseButton>
        </fieldset>
    </form>
</template>
