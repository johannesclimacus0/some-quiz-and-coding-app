<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import {
    adminQuizAttemptsApi,
    type AdminAttemptDetail,
    type AdminAttemptSummary,
} from '../../api/adminQuizAttempts'
import type { Page } from '../../api/types'
import AlertMessage from '../AlertMessage.vue'
import Pagination from '../Pagination.vue'
import BaseButton from '../BaseButton.vue'
import { useApiOperation } from '../../composables/useApiOperation'

const props = defineProps<{ quizUuid: string }>()
const page = ref<Page<AdminAttemptSummary> | null>(null)
const selected = ref<AdminAttemptDetail | null>(null)
const listOperation = useApiOperation()
const detailOperation = useApiOperation()
const gradeOperation = useApiOperation()
const gradeForms = reactive<Record<string, { awardedPoints: number | null; feedback: string }>>({})

function load(number = 1): Promise<boolean> {
    return listOperation.run(async () => {
        page.value = await adminQuizAttemptsApi.list({ quiz: props.quizUuid, page: number })
    })
}

async function open(attempt: AdminAttemptSummary): Promise<void> {
    await detailOperation.run(async () => {
        selected.value = await adminQuizAttemptsApi.show({
            quiz: props.quizUuid,
            attempt: attempt.uuid,
        })
        hydrateGrades()
    })
}

function hydrateGrades(): void {
    if (!selected.value) return
    for (const question of selected.value.questions) {
        if (question.type === 'text') {
            gradeForms[question.uuid] = {
                awardedPoints: question.awarded_points,
                feedback: question.feedback ?? '',
            }
        }
    }
}

async function grade(
    question: Extract<AdminAttemptDetail['questions'][number], { type: 'text' }>,
): Promise<void> {
    if (!selected.value?.submitted_at || !canGrade(question) || gradeOperation.busy.value) return
    await gradeOperation.run(async () => {
        selected.value = await adminQuizAttemptsApi.gradeAttemptAnswer({
            quiz: props.quizUuid,
            attempt: selected.value!.uuid,
            question: question.uuid,
            awardedPoints: Number(gradeForms[question.uuid].awardedPoints),
            feedback: gradeForms[question.uuid].feedback || null,
            expectedVersion: question.grading_version,
        })
        hydrateGrades()
        await load(page.value?.meta.current_page)
    })
}

function canGrade(
    question: Extract<AdminAttemptDetail['questions'][number], { type: 'text' }>,
): boolean {
    const points = gradeForms[question.uuid]?.awardedPoints
    return (
        typeof points === 'number' &&
        Number.isInteger(points) &&
        points >= 0 &&
        points <= question.max_points
    )
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '--'
}

watch(
    () => props.quizUuid,
    () => {
        page.value = null
        selected.value = null
        void load()
    },
)

onMounted(() => load())
</script>

<template>
    <div class="space-y-4 p-4">
        <AlertMessage
            :message="
                listOperation.error.value ||
                detailOperation.error.value ||
                gradeOperation.error.value
            "
        />
        <section class="overflow-hidden border border-[#cec9d5] dark:border-[#363845]">
            <header
                class="flex items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
            >
                <span class="inline-flex">
                    <span class="text-[#1793d1]">attempts</span>
                    <span class="text-[#827a8b] dark:text-[#9792a5]">://</span>
                    index
                </span>
                <span class="text-[#827a8b] dark:text-[#9792a5]">
                    rows={{ page?.meta.total ?? 0 }}
                </span>
            </header>
            <div
                class="hidden grid-cols-[minmax(10rem,1fr)_9rem_7rem_10rem] gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-[0.625rem] tracking-[0.1em] text-[#827a8b] dark:border-[#363845] dark:bg-[#101219] dark:text-[#9792a5] xl:grid"
            >
                <span>пользователь</span>
                <span>статус</span>
                <span>результат</span>
                <span>начато</span>
            </div>
            <p
                v-if="listOperation.busy.value && !page"
                class="p-6 text-center font-mono text-xs"
            >
                загрузка попыток...
            </p>
            <p
                v-else-if="page && !page.data.length"
                class="p-6 text-center font-mono text-xs text-[#686171] dark:text-[#9792a5]"
            >
                попыток пока нет
            </p>
            <ul class="divide-y divide-[#dfdae5] dark:divide-[#242632]">
                <li
                    v-for="attempt in page?.data"
                    :key="attempt.uuid"
                >
                    <button
                        type="button"
                        class="grid w-full gap-1 px-3 py-3 text-left transition-colors hover:bg-[#ede9f1] dark:hover:bg-[#191b24] xl:grid-cols-[minmax(10rem,1fr)_9rem_7rem_10rem] xl:items-center xl:gap-3"
                        :class="
                            selected?.uuid === attempt.uuid ? 'bg-[#ede9f1] dark:bg-[#191b24]' : ''
                        "
                        :disabled="detailOperation.busy.value"
                        @click="open(attempt)"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-mono text-sm">
                                {{ attempt.user.name }}
                            </span>
                            <span
                                class="block truncate font-mono text-[0.6875rem] text-[#686171] dark:text-[#9792a5]"
                            >
                                {{ attempt.user.email }}
                            </span>
                        </span>
                        <span
                            class="font-mono text-xs"
                            :class="
                                attempt.status === 'completed'
                                    ? 'text-[#447b9e] dark:text-[#8eb4d1]'
                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                            "
                        >
                            {{ attempt.status }}
                        </span>
                        <span class="font-mono text-xs">
                            {{
                                attempt.result
                                    ? attempt.result.earned_points === null
                                        ? 'ожидает проверки'
                                        : `${attempt.result.earned_points}/${attempt.result.max_points} · ${attempt.result.percentage}%`
                                    : '--'
                            }}
                        </span>
                        <time class="font-mono text-[0.6875rem] text-[#686171] dark:text-[#9792a5]">
                            {{ formatDate(attempt.started_at) }}
                        </time>
                    </button>
                </li>
            </ul>
            <Pagination
                v-if="page"
                label="pager://attempts"
                :current-page="page.meta.current_page"
                :last-page="page.meta.last_page"
                :loading="listOperation.busy.value"
                @change="load"
            />
        </section>
        <p
            v-if="detailOperation.busy.value"
            class="border border-[#cec9d5] p-5 text-center font-mono text-xs dark:border-[#363845]"
        >
            чтение снимка...
        </p>
        <section
            v-if="selected && !detailOperation.busy.value"
            class="border border-[#cec9d5] dark:border-[#363845]"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
            >
                <span class="inline-flex">
                    <span class="text-[#1793d1]">attempt</span>
                    <span class="text-[#827a8b] dark:text-[#9792a5]">://</span>
                    {{ selected.uuid.slice(0, 16) }}
                </span>
                <span>{{ selected.user.name }} · {{ selected.status }}</span>
            </header>

            <div class="space-y-4 p-3">
                <article
                    v-for="(question, index) in selected.questions"
                    :key="question.uuid"
                    class="border border-[#cec9d5] dark:border-[#363845]"
                >
                    <header
                        class="flex items-start justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#101219]"
                    >
                        <span>
                            <span class="text-[#1793d1]">
                                Q{{ String(index + 1).padStart(2, '0') }}
                            </span>
                            · {{ question.text }}
                        </span>
                        <span
                            :class="
                                question.state === 'correct'
                                    ? 'text-[#447b9e] dark:text-[#8eb4d1]'
                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                            "
                        >
                            {{ question.state }}
                        </span>
                    </header>
                    <ul
                        v-if="question.type === 'single_choice'"
                        class="divide-y divide-[#dfdae5] font-mono text-xs dark:divide-[#242632]"
                    >
                        <li
                            v-for="answer in question.answers"
                            :key="answer.uuid"
                            class="flex items-start justify-between gap-3 px-3 py-2.5"
                            :class="answer.is_selected ? 'bg-[#ede9f1] dark:bg-[#191b24]' : ''"
                        >
                            <span>{{ answer.text }}</span>
                            <span class="shrink-0">
                                <span
                                    v-if="answer.is_selected"
                                    class="text-[#b24d91] dark:text-[#e781bd]"
                                >
                                    selected
                                </span>
                                <span v-if="answer.is_selected && answer.is_correct">·</span>
                                <span
                                    v-if="answer.is_correct"
                                    class="text-[#447b9e] dark:text-[#8eb4d1]"
                                >
                                    correct
                                </span>
                            </span>
                        </li>
                    </ul>
                    <div
                        v-else
                        class="space-y-3 p-3 font-mono text-xs"
                    >
                        <p
                            class="break-words whitespace-pre-wrap border border-[#cec9d5] bg-[#f3f1f6] p-3 leading-6 dark:border-[#363845] dark:bg-[#101219]"
                        >
                            {{ question.response.text || 'Ответ не сохранён' }}
                        </p>
                        <p class="text-[#686171] dark:text-[#9792a5]">
                            Критерии: {{ question.criteria || 'не заданы' }} · диапазон: 0–{{
                                question.max_points
                            }}
                        </p>
                        <div
                            v-if="selected.submitted_at"
                            class="grid min-w-0 gap-3 xl:grid-cols-[6rem_minmax(0,1fr)_auto]"
                        >
                            <label class="space-y-1">
                                <span>Баллы</span>
                                <input
                                    v-model.number="gradeForms[question.uuid].awardedPoints"
                                    type="number"
                                    min="0"
                                    :max="question.max_points"
                                    class="w-full border border-[#cec9d5] bg-white p-2 dark:border-[#363845] dark:bg-[#191b24]"
                                />
                            </label>
                            <label class="space-y-1">
                                <span>Комментарий</span>
                                <textarea
                                    v-model="gradeForms[question.uuid].feedback"
                                    rows="3"
                                    maxlength="4096"
                                    class="w-full border border-[#cec9d5] bg-white p-2 dark:border-[#363845] dark:bg-[#191b24]"
                                />
                            </label>
                            <BaseButton
                                class="self-end"
                                :loading="gradeOperation.busy.value"
                                :disabled="!canGrade(question)"
                                @click="grade(question)"
                            >
                                Сохранить оценку
                            </BaseButton>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
