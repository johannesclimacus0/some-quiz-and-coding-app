<script setup lang="ts">
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
    quizAttemptsApi,
    type AttemptQuestion,
    type QuizDetails,
    type QuestionResponse,
} from '../api/quizAttempts'
import AlertMessage from '../components/AlertMessage.vue'
import BaseButton from '../components/BaseButton.vue'
import CodeEditor from '../components/CodeEditor.vue'
import { useApiOperation } from '../composables/useApiOperation'
import UserLayout from '../layouts/UserLayout.vue'

const route = useRoute()
const details = ref<QuizDetails | null>(null)
const responses = ref<Record<string, QuestionResponse>>({})
const textDrafts = ref<Record<string, string>>({})
const activeIndex = ref(0)
const savingQuestion = ref<string | null>(null)
const saveErrors = ref<Record<string, string>>({})
const { busy, error, run } = useApiOperation()

const attempt = computed(() => details.value?.attempt ?? null)
const questions = computed(() => attempt.value?.snapshot.questions ?? [])
const currentQuestion = computed(() => questions.value[activeIndex.value])
const savedCount = computed(
    () =>
        questions.value.filter((question) => isResponseComplete(responses.value[question.uuid]))
            .length,
)
const hasDrafts = computed(() => questions.value.some(isDirty))
const allAnswered = computed(
    () =>
        questions.value.length > 0 &&
        questions.value.every((question) => isResponseComplete(responses.value[question.uuid])),
)
const deadlineExpired = computed(() =>
    Boolean(details.value?.due_at && new Date(details.value.due_at).getTime() < Date.now()),
)
const mutable = computed(
    () => attempt.value?.status === 'in_progress' && !savingQuestion.value && !busy.value,
)

async function load(): Promise<void> {
    await run(async () => {
        details.value = await quizAttemptsApi.showQuiz({ quiz: String(route.params.quiz) })
        restoreResponses(details.value.attempt?.responses ?? {})
    })
}

async function start(): Promise<void> {
    if (!details.value) return
    await run(async () => {
        details.value!.attempt = await quizAttemptsApi.start({ quiz: details.value!.uuid })
        restoreResponses(details.value!.attempt!.responses)
    })
}

async function saveResponse(question: AttemptQuestion, response: QuestionResponse): Promise<void> {
    if (!details.value || !mutable.value) return
    const previous = responses.value[question.uuid]
    responses.value = { ...responses.value, [question.uuid]: response }
    savingQuestion.value = question.uuid
    delete saveErrors.value[question.uuid]

    try {
        const updated = await quizAttemptsApi.saveAnswer({
            quiz: details.value.uuid,
            question: question.uuid,
            response,
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

async function choose(question: AttemptQuestion, answerUuid: string): Promise<void> {
    await saveResponse(question, { answer_uuid: answerUuid })
}

async function saveText(question: AttemptQuestion): Promise<void> {
    await saveResponse(question, { text: textDrafts.value[question.uuid] ?? '' })
}

async function saveCode(question: AttemptQuestion): Promise<void> {
    await saveResponse(question, { code: textDrafts.value[question.uuid] ?? '' })
}

function restoreResponses(next: Record<string, QuestionResponse>): void {
    responses.value = { ...next }
    textDrafts.value = Object.fromEntries(
        Object.entries(next).flatMap(([questionUuid, response]) =>
            'text' in response
                ? [[questionUuid, response.text]]
                : 'code' in response
                  ? [[questionUuid, response.code]]
                  : [],
        ),
    )
}

function isResponseComplete(response: QuestionResponse | undefined): boolean {
    return (
        Boolean(selectedAnswerUuid(response)) ||
        responseText(response).trim() !== '' ||
        responseCode(response).trim() !== ''
    )
}

function selectedAnswerUuid(response: QuestionResponse | undefined): string | undefined {
    return response && 'answer_uuid' in response ? response.answer_uuid : undefined
}

function responseText(response: QuestionResponse | undefined): string {
    return response && 'text' in response ? response.text : ''
}

function responseCode(response: QuestionResponse | undefined): string {
    return response && 'code' in response ? response.code : ''
}

function responseAwardedPoints(response: QuestionResponse | undefined): number | null {
    return response?.awarded_points ?? null
}

function isDirty(question: AttemptQuestion): boolean {
    return (
        (question.type === 'text' || question.type === 'code') &&
        (textDrafts.value[question.uuid] ?? '') !==
            (question.type === 'code'
                ? responseCode(responses.value[question.uuid])
                : responseText(responses.value[question.uuid]))
    )
}

function goTo(index: number): void {
    if (index >= 0 && index < questions.value.length) activeIndex.value = index
}

function questionState(question: AttemptQuestion): string {
    if (savingQuestion.value === question.uuid) return 'saving'
    if (saveErrors.value[question.uuid]) return 'error'
    if (isDirty(question)) return 'draft'
    if (isResponseComplete(responses.value[question.uuid])) return 'saved'

    return 'empty'
}

const stateLabels: Record<string, string> = {
    saving: 'Сохранение…',
    saved: 'Сохранён',
    draft: 'Черновик',
    empty: 'Нет ответа',
    error: 'Ошибка сохранения',
}

async function submit(): Promise<void> {
    if (
        !details.value ||
        !allAnswered.value ||
        hasDrafts.value ||
        !mutable.value ||
        !window.confirm('Завершить попытку? После этого ответы изменить нельзя.')
    )
        return
    await run(async () => {
        details.value!.attempt = await quizAttemptsApi.submit({ quiz: details.value!.uuid })
    })
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : 'без срока'
}

watch(
    () => route.params.quiz,
    () => {
        activeIndex.value = 0
        details.value = null
        responses.value = {}
        textDrafts.value = {}
        saveErrors.value = {}
        void load()
    },
    { immediate: true },
)
</script>

<template>
    <UserLayout :title="details?.title ?? 'Квиз'">
        <template #header-actions>
            <RouterLink
                :to="{ name: 'home' }"
                class="font-mono text-xs text-[#447b9e] transition-colors hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
            >
                &lt;-- список квизов
            </RouterLink>
        </template>

        <div class="attempt-workspace min-w-0 space-y-4">
            <AlertMessage :message="error" />
            <p
                v-if="busy && !details"
                class="border border-[#cec9d5] p-8 text-center font-mono text-xs dark:border-[#363845]"
            >
                загрузка квиза...
            </p>
            <template v-if="details">
                <section
                    class="border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
                    >
                        <span class="inline-flex">
                            <span class="text-[#1793d1]">quiz</span>
                            <span class="text-[#827a8b] dark:text-[#9792a5]">://</span>
                            {{ details.uuid.slice(0, 16) }}
                        </span>
                        <span class="text-[#686171] dark:text-[#9792a5]">
                            {{ attempt?.status ?? 'not_started' }}
                        </span>
                    </header>
                    <div class="space-y-3 p-4">
                        <h2 class="break-words font-mono text-lg font-medium">
                            {{ details.title }}
                        </h2>
                        <p
                            v-if="details.description"
                            class="text-sm leading-6 text-[#5f5866] dark:text-[#b7b2c2]"
                        >
                            {{ details.description }}
                        </p>
                        <div
                            class="flex flex-wrap gap-x-6 gap-y-1 font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                        >
                            <span>вопросов={{ details.questions_count }}</span>
                            <span>due_at={{ formatDate(details.due_at) }}</span>
                        </div>
                    </div>
                </section>
                <section
                    v-if="!attempt"
                    class="border border-[#cec9d5] bg-[#fcfafd] p-4 dark:border-[#363845] dark:bg-[#101219]"
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
                    v-else-if="attempt.status === 'submitted'"
                    class="border border-[#cec9d5] bg-[#fcfafd] p-6 text-center dark:border-[#363845] dark:bg-[#101219]"
                >
                    <p class="font-mono text-lg">Ожидает проверки</p>
                    <p class="mt-2 text-sm text-[#686171] dark:text-[#9792a5]">
                        Администратор проверит ответы с ручной оценкой и опубликует итог.
                    </p>
                </section>
                <section
                    v-else-if="attempt.status === 'completed' && attempt.result"
                    class="border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
                >
                    <header
                        class="border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
                    >
                        result://completed
                    </header>
                    <div class="grid gap-4 p-6 text-center sm:grid-cols-2">
                        <div>
                            <p class="font-mono text-3xl">
                                {{ attempt.result.earned_points }} /
                                {{ attempt.result.max_points }}
                            </p>
                            <p class="mt-1 text-xs text-[#686171] dark:text-[#9792a5]">баллов</p>
                        </div>
                        <div>
                            <p class="font-mono text-3xl text-[#447b9e] dark:text-[#8eb4d1]">
                                {{ attempt.result.percentage }}%
                            </p>
                            <p class="mt-1 text-xs text-[#686171] dark:text-[#9792a5]">результат</p>
                        </div>
                    </div>
                    <div
                        v-if="
                            questions.some((question) => ['text', 'code'].includes(question.type))
                        "
                        class="space-y-2 border-t border-[#cec9d5] p-4 text-sm dark:border-[#363845]"
                    >
                        <article
                            v-for="question in questions"
                            :key="question.uuid"
                            v-show="question.type === 'text' || question.type === 'code'"
                            class="border border-[#cec9d5] p-3 dark:border-[#363845]"
                        >
                            <header class="flex flex-wrap items-start justify-between gap-2">
                                <span class="font-mono text-xs">{{ question.text }}</span>
                                <span class="font-mono text-xs text-[#447b9e] dark:text-[#8eb4d1]">
                                    {{ responseAwardedPoints(responses[question.uuid]) }} /
                                    {{ question.max_points }}
                                </span>
                            </header>
                            <p
                                v-if="responses[question.uuid].feedback"
                                class="mt-2 text-[#5f5866] dark:text-[#b7b2c2]"
                            >
                                {{ responses[question.uuid].feedback }}
                            </p>
                        </article>
                    </div>
                </section>
                <template v-else>
                    <p
                        v-if="attempt.status === 'expired'"
                        class="border border-[#d7a9bf] bg-[#fff4f8] p-3 font-mono text-xs text-[#a34a70] dark:border-[#623d53] dark:bg-[#2d1d28] dark:text-[#f077a8]"
                    >
                        Срок прохождения истёк. Ответы сохранены, но завершить попытку нельзя.
                    </p>

                    <section
                        class="overflow-hidden border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
                    >
                        <header
                            class="flex flex-wrap items-center justify-between gap-2 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
                        >
                            <span>
                                <span class="text-[#1793d1]">workspace</span>
                                <span class="text-[#827a8b]">/</span>
                                ~/questions
                            </span>
                            <span class="text-[#686171] dark:text-[#9792a5]">
                                Сохранено {{ savedCount }} из {{ questions.length }}
                            </span>
                        </header>
                        <div class="grid min-w-0 lg:grid-cols-[15rem_minmax(0,1fr)]">
                            <aside
                                class="min-w-0 border-b border-[#cec9d5] bg-[#f3f1f6] p-3 dark:border-[#363845] dark:bg-[#191b24] lg:border-r lg:border-b-0"
                            >
                                <p
                                    class="mb-3 hidden border-b border-[#cec9d5] pb-2 font-mono text-[0.625rem] uppercase tracking-widest text-[#686171] dark:border-[#363845] dark:text-[#9792a5] lg:block"
                                >
                                    explorer / вопросы
                                </p>
                                <nav
                                    aria-label="Вопросы попытки"
                                    class="flex flex-wrap gap-0.5 lg:flex-col"
                                >
                                    <button
                                        v-for="(item, index) in questions"
                                        :key="item.uuid"
                                        type="button"
                                        :aria-current="index === activeIndex ? 'step' : undefined"
                                        :aria-label="`Вопрос ${index + 1}: ${stateLabels[questionState(item)]}`"
                                        :title="item.text"
                                        class="flex min-h-10 min-w-10 items-center gap-2 border px-2 py-2 text-left font-mono text-xs transition-colors focus-visible:outline-2 focus-visible:outline-[#1793d1] lg:w-full"
                                        :class="
                                            index === activeIndex
                                                ? 'border-[#1793d1]/40 bg-[#1793d1]/10 shadow-[inset_2px_0_0_#1793d1]'
                                                : 'border-transparent hover:bg-[#f3f1f6] dark:hover:bg-[#242632]'
                                        "
                                        @click="goTo(index)"
                                    >
                                        <span class="shrink-0 text-[#1793d1]">
                                            {{ String(index + 1).padStart(2, '0') }}
                                        </span>
                                        <span class="hidden min-w-0 flex-1 lg:block">
                                            <span class="block truncate">
                                                <span class="text-[#827a8b]">
                                                    {{ String(index + 1).padStart(2, '0') }}_
                                                </span>
                                                {{
                                                    item.type === 'code'
                                                        ? 'solution.code'
                                                        : item.type === 'text'
                                                          ? 'response.txt'
                                                          : 'choice.quiz'
                                                }}
                                            </span>
                                            <span class="mt-1 block truncate text-[0.6875rem]">
                                                {{ item.text }}
                                            </span>
                                            <span
                                                class="mt-1 block text-[0.625rem] text-[#686171] dark:text-[#9792a5]"
                                            >
                                                {{ stateLabels[questionState(item)] }}
                                            </span>
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            class="shrink-0"
                                            :class="
                                                questionState(item) === 'saved'
                                                    ? 'text-[#447b9e] dark:text-[#8eb4d1]'
                                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                                            "
                                        >
                                            {{
                                                questionState(item) === 'saved'
                                                    ? '[x]'
                                                    : questionState(item) === 'draft'
                                                      ? '[~]'
                                                      : questionState(item) === 'error'
                                                        ? '[!]'
                                                        : '[ ]'
                                            }}
                                        </span>
                                    </button>
                                </nav>
                            </aside>
                            <div
                                v-if="currentQuestion"
                                class="flex min-w-0 flex-col"
                            >
                                <header
                                    class="flex flex-wrap items-center justify-between gap-2 border-b border-[#cec9d5] bg-[#f3f1f6] px-4 py-3 font-mono text-xs dark:border-[#363845] dark:bg-[#101219]"
                                >
                                    <span>
                                        <span class="mr-2 text-[#1793d1]">
                                            [{{ String(activeIndex + 1).padStart(2, '0') }}]
                                        </span>
                                        Вопрос {{ activeIndex + 1 }} / {{ questions.length }}
                                        <span class="text-[#827a8b]">·</span>
                                        {{
                                            currentQuestion.type === 'code'
                                                ? 'Код'
                                                : currentQuestion.type === 'text'
                                                  ? 'Текстовый ответ'
                                                  : 'Один вариант'
                                        }}
                                    </span>
                                    <span class="text-[#686171] dark:text-[#9792a5]">
                                        Баллы: {{ currentQuestion.max_points }}
                                    </span>
                                </header>
                                <div class="min-w-0 flex-1 space-y-5 p-4 sm:p-6 lg:min-h-[26rem]">
                                    <p
                                        aria-hidden="true"
                                        class="font-mono text-[0.6875rem] text-[#827a8b] dark:text-[#9792a5]"
                                    >
                                        // условие
                                    </p>
                                    <h2
                                        id="current-question-title"
                                        class="whitespace-pre-wrap break-words font-sans text-base leading-7"
                                    >
                                        {{ currentQuestion.text }}
                                    </h2>
                                    <fieldset
                                        :key="currentQuestion.uuid"
                                        :disabled="!mutable"
                                        aria-labelledby="current-question-title"
                                        class="min-w-0"
                                    >
                                        <div
                                            v-if="currentQuestion.type === 'single_choice'"
                                            class="divide-y divide-[#dfdae5] border border-[#cec9d5] dark:divide-[#242632] dark:border-[#363845]"
                                        >
                                            <label
                                                v-for="(answer, index) in currentQuestion
                                                    .public_config.answers"
                                                :key="answer.uuid"
                                                class="flex min-w-0 cursor-pointer items-start gap-3 px-3 py-3 font-sans text-sm transition-colors hover:bg-[#ede9f1] dark:hover:bg-[#191b24]"
                                                :class="
                                                    selectedAnswerUuid(
                                                        responses[currentQuestion.uuid],
                                                    ) === answer.uuid
                                                        ? 'bg-[#1793d1]/5 shadow-[inset_2px_0_0_#1793d1]'
                                                        : ''
                                                "
                                            >
                                                <input
                                                    type="radio"
                                                    :name="`question-${currentQuestion.uuid}`"
                                                    :checked="
                                                        selectedAnswerUuid(
                                                            responses[currentQuestion.uuid],
                                                        ) === answer.uuid
                                                    "
                                                    class="mt-1 size-4 shrink-0 accent-[#447b9e] dark:accent-[#8eb4d1]"
                                                    @change="choose(currentQuestion, answer.uuid)"
                                                />
                                                <span
                                                    class="mt-0.5 shrink-0 text-xs text-[#827a8b]"
                                                >
                                                    {{ String(index + 1).padStart(2, '0') }}
                                                </span>
                                                <span
                                                    class="min-w-0 whitespace-pre-wrap break-words leading-6"
                                                >
                                                    {{ answer.text }}
                                                </span>
                                            </label>
                                        </div>
                                        <div
                                            v-else-if="
                                                currentQuestion.type === 'text' ||
                                                currentQuestion.type === 'code'
                                            "
                                            class="min-w-0 space-y-3"
                                        >
                                            <p
                                                class="block font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                                            >
                                                {{
                                                    currentQuestion.type === 'code'
                                                        ? `${currentQuestion.public_config.label} · solution.${currentQuestion.public_config.file_extension}`
                                                        : 'Ваш ответ'
                                                }}
                                            </p>
                                            <CodeEditor
                                                v-if="currentQuestion.type === 'code'"
                                                v-model="textDrafts[currentQuestion.uuid]"
                                                :language="currentQuestion.public_config.editor_id"
                                                :max-length="
                                                    currentQuestion.public_config.max_length
                                                "
                                                :readonly="
                                                    attempt.status !== 'in_progress' ||
                                                    deadlineExpired ||
                                                    busy
                                                "
                                                height="26rem"
                                            />
                                            <textarea
                                                v-else
                                                :id="`response-${currentQuestion.uuid}`"
                                                aria-label="Ваш ответ"
                                                v-model="textDrafts[currentQuestion.uuid]"
                                                :maxlength="
                                                    currentQuestion.public_config.max_length
                                                "
                                                rows="10"
                                                class="block w-full min-w-0 resize-y border border-[#cec9d5] bg-[#fcfafd] p-3 font-sans text-sm leading-6 outline-none focus:border-[#1793d1] dark:border-[#363845] dark:bg-[#101219]"
                                                placeholder="Введите ответ…"
                                            />
                                            <div
                                                class="flex flex-wrap items-center justify-between gap-3"
                                            >
                                                <span
                                                    class="font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                                                >
                                                    {{
                                                        (textDrafts[currentQuestion.uuid] ?? '')
                                                            .length
                                                    }}
                                                    / {{ currentQuestion.public_config.max_length }}
                                                </span>
                                                <BaseButton
                                                    :loading="
                                                        savingQuestion === currentQuestion.uuid
                                                    "
                                                    :disabled="
                                                        !isDirty(currentQuestion) ||
                                                        !(
                                                            textDrafts[currentQuestion.uuid] ?? ''
                                                        ).trim()
                                                    "
                                                    loading-text="Сохранение…"
                                                    @click="
                                                        currentQuestion.type === 'code'
                                                            ? saveCode(currentQuestion)
                                                            : saveText(currentQuestion)
                                                    "
                                                >
                                                    Сохранить ответ
                                                </BaseButton>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <p
                                        aria-live="polite"
                                        class="font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                                    >
                                        {{ stateLabels[questionState(currentQuestion)] }}
                                    </p>
                                    <AlertMessage
                                        :message="saveErrors[currentQuestion.uuid] ?? ''"
                                    />
                                </div>
                                <footer
                                    class="space-y-3 border-t border-[#cec9d5] bg-[#f3f1f6] p-3 dark:border-[#363845] dark:bg-[#191b24]"
                                >
                                    <p
                                        v-if="hasDrafts"
                                        class="font-mono text-xs text-[#b24d91] dark:text-[#e781bd]"
                                    >
                                        Есть несохранённые изменения. Сохраните ответы перед
                                        завершением.
                                    </p>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <BaseButton
                                            :disabled="activeIndex === 0"
                                            @click="goTo(activeIndex - 1)"
                                        >
                                            &lt;-- Назад
                                        </BaseButton>
                                        <BaseButton
                                            v-if="activeIndex < questions.length - 1"
                                            variant="primary"
                                            @click="goTo(activeIndex + 1)"
                                        >
                                            Далее --&gt;
                                        </BaseButton>
                                        <BaseButton
                                            v-else-if="attempt.status === 'in_progress'"
                                            variant="primary"
                                            :loading="busy"
                                            :disabled="
                                                !allAnswered || hasDrafts || Boolean(savingQuestion)
                                            "
                                            loading-text="Завершение…"
                                            @click="submit"
                                        >
                                            Завершить попытку
                                        </BaseButton>
                                    </div>
                                </footer>
                            </div>
                        </div>
                    </section>
                </template>
            </template>
        </div>
    </UserLayout>
</template>

<style scoped>
.attempt-workspace {
    --panel-border: #cec9d5;
}

/* A restrained terminal palette; keep shared site controls and focus states. */
:global(.dark) .attempt-workspace {
    --panel-border: #363845;
}

.attempt-workspace :deep(button) {
    border-radius: 0;
}

.attempt-workspace :deep(textarea) {
    tab-size: 4;
    caret-color: #1793d1;
}

.attempt-workspace section,
.attempt-workspace aside,
.attempt-workspace header,
.attempt-workspace footer {
    border-color: var(--panel-border);
}
</style>
