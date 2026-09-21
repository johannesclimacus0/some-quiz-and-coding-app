<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { Quiz, QuizInput } from '../../api/quizzes'
import FormField from '../FormField.vue'
import BaseButton from '../BaseButton.vue'

const props = defineProps<{ quiz?: Quiz; busy: boolean; errors: Record<string, string[]> }>()
const emit = defineEmits<{ save: [input: QuizInput] }>()
const form = reactive({ title: '', description: '', dueAt: '' })

function localDate(value: string | null): string {
    if (!value) return ''
    const date = new Date(value)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}
watch(
    [() => props.quiz?.title, () => props.quiz?.description, () => props.quiz?.due_at],
    ([title, description, dueAt]) => {
        form.title = title ?? ''
        form.description = description ?? ''
        form.dueAt = localDate(dueAt ?? null)
    },
    { immediate: true },
)

function submit() {
    emit('save', {
        title: form.title,
        description: form.description || null,
        due_at: form.dueAt ? new Date(form.dueAt).toISOString() : null,
    })
}
</script>

<template>
    <form @submit.prevent="submit">
        <fieldset
            :disabled="busy"
            class="space-y-4 p-4"
        >
            <FormField
                id="quiz-title"
                v-model="form.title"
                label="Название"
                required
                maxlength="255"
                :error="errors.title?.[0]"
            />
            <div class="space-y-2">
                <label
                    for="quiz-description"
                    class="block font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    Описание
                </label>
                <textarea
                    id="quiz-description"
                    v-model="form.description"
                    rows="3"
                    maxlength="4096"
                    class="w-full border border-[#cec9d5] bg-[#fcfafd] p-2.5 font-mono text-xs outline-none focus:border-[#1793d1] dark:border-[#363845] dark:bg-[#101219]"
                />
                <p
                    v-if="errors.description"
                    class="text-xs text-[#c14378] dark:text-[#f077a8]"
                >
                    {{ errors.description[0] }}
                </p>
            </div>
            <FormField
                id="quiz-due-at"
                v-model="form.dueAt"
                label="Срок окончания (местное время)"
                type="datetime-local"
                :error="errors.due_at?.[0]"
            />
            <BaseButton
                type="submit"
                variant="primary"
                :loading="busy"
            >
                {{ quiz ? 'Сохранить квиз' : 'Создать квиз' }}
            </BaseButton>
        </fieldset>
    </form>
</template>
