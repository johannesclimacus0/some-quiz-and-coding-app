<script setup lang="ts">
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
    quizAttemptsApi,
    type AttemptQuestion,
    type QuizDetails,
    type SingleChoiceResponse,
} from '../api/quizAttempts'
import AlertMessage from '../components/AlertMessage.vue'
import BaseButton from '../components/BaseButton.vue'
import { useApiOperation } from '../composables/useApiOperation'
import UserLayout from '../layouts/UserLayout.vue'

const route = useRoute()
const details = ref<QuizDetails | null>(null)
const responses = ref<Record<string, SingleChoiceResponse>>({})
const savingQuestion = ref<string | null>(null)
const saveErrors = ref<Record<string, string>>({})
const { busy, error, run } = useApiOperation()

const attempt = computed(() => details.value?.attempt ?? null)
const questions = computed(() => attempt.value?.snapshot.questions ?? [])
const allAnswered = computed(
    () =>
        questions.value.length > 0 &&
        questions.value.every((question) => Boolean(responses.value[question.uuid]?.answer_uuid)),
)
const deadlineExpired = computed(() =>
    Boolean(details.value?.due_at && new Date(details.value.due_at).getTime() < Date.now()),
)
const mutable = computed(() => attempt.value?.status === 'in_progress' && !savingQuestion.value)

async function load(): Promise<void> {
    await run(async () => {
        details.value = await quizAttemptsApi.showQuiz({ quiz: String(route.params.quiz) })
        responses.value = { ...(details.value.attempt?.responses ?? {}) }
    })
}

async function start(): Promise<void> {
    if (!details.value) return
    await run(async () => {
        details.value!.attempt = await quizAttemptsApi.start({ quiz: details.value!.uuid })
        responses.value = { ...details.value!.attempt!.responses }
    })
}

async function choose(question: AttemptQuestion, answerUuid: string): Promise<void> {
    if (!details.value || !mutable.value) return
    const previous = responses.value[question.uuid]
    responses.value = { ...responses.value, [question.uuid]: { answer_uuid: answerUuid } }
    savingQuestion.value = question.uuid
    delete saveErrors.value[question.uuid]

    try {
        const updated = await quizAttemptsApi.saveAnswer({
            quiz: details.value.uuid,
            question: question.uuid,
            response: { answer_uuid: answerUuid },
        })
        details.value.attempt = updated
        responses.value = { ...updated.responses }
    } catch (cause: unknown) {
        const next = { ...responses.value }
        if (previous) next[question.uuid] = previous
        else delete next[question.uuid]
        responses.value = next
        saveErrors.value[question.uuid] = axios.isAxiosError<{ message?: string }>(cause)
            ? (cause.response?.data?.message ?? 'Не удалось сохранить ответ.')
            : 'Не удалось сохранить ответ.'
    } finally {
        savingQuestion.value = null
    }
}

async function submit(): Promise<void> {
    if (
        !details.value ||
        !allAnswered.value ||
        !window.confirm('Завершить попытку? После этого ответы изменить нельзя.')
    )
        return
    await run(async () => {
        const result = await quizAttemptsApi.submit({ quiz: details.value!.uuid })
        details.value!.attempt!.status = 'completed'
        details.value!.attempt!.result = result
    })
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : 'без срока'
}

watch(
    () => route.params.quiz,
    () => void load(),
    { immediate: true },
)
</script>

<template>
    <UserLayout :title="details?.title ?? 'Квиз'">
        <template #header-actions>
            <RouterLink
                :to="{ name: 'home' }"
                class="font-mono text-xs text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
            >
                &lt;-- список квизов
            </RouterLink>
        </template>

        <div class="mx-auto max-w-4xl space-y-4">
            <AlertMessage :message="error" />
            <p
                v-if="busy && !details"
                class="border border-[#c9c1cf] p-8 text-center font-mono text-xs dark:border-[#343746]"
            >
                загрузка квиза...
            </p>
            <template v-if="details">
                <section
                    class="border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                    >
                        <span class="inline-flex">
                            <span class="text-[#1793d1]">quiz</span>
                            <span class="text-[#96909e] dark:text-[#656879]">://</span>
                            {{ details.uuid.slice(0, 16) }}
                        </span>
                        <span class="text-[#68616f] dark:text-[#918da0]">
                            {{ attempt?.status ?? 'not_started' }}
                        </span>
                    </header>
                    <div class="space-y-3 p-4">
                        <h2 class="font-mono text-lg font-medium">{{ details.title }}</h2>
                        <p
                            v-if="details.description"
                            class="text-sm leading-6 text-[#5f5866] dark:text-[#b7b2c2]"
                        >
                            {{ details.description }}
                        </p>
                        <div
                            class="flex flex-wrap gap-x-6 gap-y-1 font-mono text-xs text-[#68616f] dark:text-[#918da0]"
                        >
                            <span>вопросов={{ details.questions_count }}</span>
                            <span>due_at={{ formatDate(details.due_at) }}</span>
                        </div>
                    </div>
                </section>
                <section
                    v-if="!attempt"
                    class="border border-[#c9c1cf] bg-[#fbfafd] p-4 dark:border-[#343746] dark:bg-[#11131a]"
                >
                    <p
                        v-if="deadlineExpired"
                        class="mb-4 font-mono text-xs text-[#b24d91] dark:text-[#e781bd]"
                    >
                        Срок прохождения квиза истёк.
                    </p>
                    <BaseButton
                        variant="primary"
                        :loading="busy"
                        :disabled="deadlineExpired"
                        loading-text="Запуск…"
                        @click="start"
                    >
                        Начать
                    </BaseButton>
                </section>
                <section
                    v-else-if="attempt.status === 'completed' && attempt.result"
                    class="border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                >
                    <header
                        class="border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                    >
                        result://completed
                    </header>
                    <div class="grid gap-4 p-6 text-center sm:grid-cols-2">
                        <div>
                            <p class="font-mono text-3xl">
                                {{ attempt.result.correct_answers }} /
                                {{ attempt.result.total_questions }}
                            </p>
                            <p class="mt-1 text-xs text-[#68616f] dark:text-[#918da0]">
                                правильных ответов
                            </p>
                        </div>
                        <div>
                            <p class="font-mono text-3xl text-[#557789] dark:text-[#8ca8b7]">
                                {{ attempt.result.percentage }}%
                            </p>
                            <p class="mt-1 text-xs text-[#68616f] dark:text-[#918da0]">результат</p>
                        </div>
                    </div>
                </section>
                <template v-else>
                    <p
                        v-if="attempt.status === 'expired'"
                        class="border border-[#d7a9bf] bg-[#fff4f8] p-3 font-mono text-xs text-[#a34a70] dark:border-[#623d53] dark:bg-[#2d1d28] dark:text-[#f077a8]"
                    >
                        Срок прохождения истёк. Ответы сохранены, но завершить попытку нельзя.
                    </p>

                    <fieldset
                        :disabled="!mutable"
                        class="space-y-4"
                    >
                        <section
                            v-for="(question, index) in questions"
                            :key="question.uuid"
                            class="border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
                        >
                            <header
                                class="flex items-center justify-between gap-3 border-b border-[#d8d1dc] bg-[#f2eff5] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#15171e]"
                            >
                                <span>
                                    <span class="text-[#1793d1]">
                                        Q{{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                    · {{ question.text }}
                                </span>
                                <span class="text-[0.6875rem] text-[#96909e] dark:text-[#656879]">
                                    {{
                                        savingQuestion === question.uuid
                                            ? 'saving'
                                            : responses[question.uuid]?.answer_uuid
                                              ? 'saved'
                                              : 'empty'
                                    }}
                                </span>
                            </header>
                            <div class="divide-y divide-[#e0dae4] dark:divide-[#292c36]">
                                <label
                                    v-for="answer in question.answers"
                                    :key="answer.uuid"
                                    class="flex cursor-pointer items-start gap-3 px-4 py-3 text-sm transition-colors hover:bg-[#eeeaf2] dark:hover:bg-[#1b1e27]"
                                >
                                    <input
                                        type="radio"
                                        :name="`question-${question.uuid}`"
                                        :checked="
                                            responses[question.uuid]?.answer_uuid === answer.uuid
                                        "
                                        class="mt-0.5 size-4 accent-[#557789] dark:accent-[#8ca8b7]"
                                        @change="choose(question, answer.uuid)"
                                    />
                                    <span>{{ answer.text }}</span>
                                </label>
                            </div>
                            <p
                                v-if="saveErrors[question.uuid]"
                                class="border-t border-[#d7a9bf] px-4 py-2 font-mono text-xs text-[#a34a70] dark:border-[#623d53] dark:text-[#f077a8]"
                            >
                                {{ saveErrors[question.uuid] }}
                            </p>
                        </section>
                    </fieldset>
                    <div
                        v-if="attempt.status === 'in_progress'"
                        class="flex flex-wrap items-center justify-between gap-3 border border-[#c9c1cf] bg-[#e8e4eb] p-3 dark:border-[#343746] dark:bg-[#181b23]"
                    >
                        <span class="font-mono text-xs text-[#68616f] dark:text-[#918da0]">
                            answered={{ Object.keys(responses).length }}/{{ questions.length }}
                        </span>
                        <BaseButton
                            variant="primary"
                            :loading="busy"
                            :disabled="!allAnswered || Boolean(savingQuestion)"
                            loading-text="Завершение…"
                            @click="submit"
                        >
                            Завершить попытку
                        </BaseButton>
                    </div>
                </template>
            </template>
        </div>
    </UserLayout>
</template>
