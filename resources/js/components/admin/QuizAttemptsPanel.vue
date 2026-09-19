<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import {
    adminQuizAttemptsApi,
    type AdminAttemptDetail,
    type AdminAttemptSummary,
} from '../../api/adminQuizAttempts'
import type { Page } from '../../api/types'
import AlertMessage from '../AlertMessage.vue'
import Pagination from '../Pagination.vue'
import { useApiOperation } from '../../composables/useApiOperation'

const props = defineProps<{ quizUuid: string }>()
const page = ref<Page<AdminAttemptSummary> | null>(null)
const selected = ref<AdminAttemptDetail | null>(null)
const listOperation = useApiOperation()
const detailOperation = useApiOperation()

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
    })
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
        <AlertMessage :message="listOperation.error.value || detailOperation.error.value" />
        <section class="overflow-hidden border border-[#d8d1dc] dark:border-[#343746]">
            <header
                class="flex items-center justify-between gap-3 border-b border-[#d8d1dc] bg-[#f2eff5] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#15171e]"
            >
                <span class="inline-flex">
                    <span class="text-[#1793d1]">attempts</span>
                    <span class="text-[#96909e] dark:text-[#656879]">://</span>
                    index
                </span>
                <span class="text-[#96909e] dark:text-[#656879]">
                    rows={{ page?.meta.total ?? 0 }}
                </span>
            </header>
            <div
                class="hidden grid-cols-[minmax(10rem,1fr)_9rem_7rem_10rem] gap-3 border-b border-[#d8d1dc] bg-[#f7f5f8] px-3 py-2 font-mono text-[0.625rem] tracking-[0.1em] text-[#96909e] dark:border-[#343746] dark:bg-[#13151c] dark:text-[#656879] md:grid"
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
                class="p-6 text-center font-mono text-xs text-[#68616f] dark:text-[#918da0]"
            >
                попыток пока нет
            </p>
            <ul class="divide-y divide-[#e0dae4] dark:divide-[#292c36]">
                <li
                    v-for="attempt in page?.data"
                    :key="attempt.uuid"
                >
                    <button
                        type="button"
                        class="grid w-full gap-1 px-3 py-3 text-left transition-colors hover:bg-[#eeeaf2] dark:hover:bg-[#1b1e27] md:grid-cols-[minmax(10rem,1fr)_9rem_7rem_10rem] md:items-center md:gap-3"
                        :class="
                            selected?.uuid === attempt.uuid ? 'bg-[#eeeaf2] dark:bg-[#1b1e27]' : ''
                        "
                        :disabled="detailOperation.busy.value"
                        @click="open(attempt)"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-mono text-sm">
                                {{ attempt.user.name }}
                            </span>
                            <span
                                class="block truncate font-mono text-[0.6875rem] text-[#68616f] dark:text-[#918da0]"
                            >
                                {{ attempt.user.email }}
                            </span>
                        </span>
                        <span
                            class="font-mono text-xs"
                            :class="
                                attempt.status === 'completed'
                                    ? 'text-[#557789] dark:text-[#8ca8b7]'
                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                            "
                        >
                            {{ attempt.status }}
                        </span>
                        <span class="font-mono text-xs">
                            {{
                                attempt.result
                                    ? `${attempt.result.correct_answers}/${attempt.result.total_questions} · ${attempt.result.percentage}%`
                                    : '--'
                            }}
                        </span>
                        <time class="font-mono text-[0.6875rem] text-[#68616f] dark:text-[#918da0]">
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
            class="border border-[#d8d1dc] p-5 text-center font-mono text-xs dark:border-[#343746]"
        >
            чтение снимка...
        </p>
        <section
            v-if="selected && !detailOperation.busy.value"
            class="border border-[#d8d1dc] dark:border-[#343746]"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-3 border-b border-[#d8d1dc] bg-[#f2eff5] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#15171e]"
            >
                <span class="inline-flex">
                    <span class="text-[#1793d1]">attempt</span>
                    <span class="text-[#96909e] dark:text-[#656879]">://</span>
                    {{ selected.uuid.slice(0, 16) }}
                </span>
                <span>{{ selected.user.name }} · {{ selected.status }}</span>
            </header>

            <div class="space-y-4 p-3">
                <article
                    v-for="(question, index) in selected.questions"
                    :key="question.uuid"
                    class="border border-[#d8d1dc] dark:border-[#343746]"
                >
                    <header
                        class="flex items-start justify-between gap-3 border-b border-[#d8d1dc] bg-[#f7f5f8] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#13151c]"
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
                                    ? 'text-[#557789] dark:text-[#8ca8b7]'
                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                            "
                        >
                            {{ question.state }}
                        </span>
                    </header>
                    <ul class="divide-y divide-[#e0dae4] font-mono text-xs dark:divide-[#292c36]">
                        <li
                            v-for="answer in question.answers"
                            :key="answer.uuid"
                            class="flex items-start justify-between gap-3 px-3 py-2.5"
                            :class="answer.is_selected ? 'bg-[#eeeaf2] dark:bg-[#1b1e27]' : ''"
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
                                    class="text-[#557789] dark:text-[#8ca8b7]"
                                >
                                    correct
                                </span>
                            </span>
                        </li>
                    </ul>
                </article>
            </div>
        </section>
    </div>
</template>
