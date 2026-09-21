<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { userQuizzesApi, type AssignedQuizPage } from '../api/userQuizzes'
import AlertMessage from '../components/AlertMessage.vue'
import BaseButton from '../components/BaseButton.vue'
import Pagination from '../components/Pagination.vue'
import { useApiOperation } from '../composables/useApiOperation'
import UserLayout from '../layouts/UserLayout.vue'

const page = ref<AssignedQuizPage | null>(null)
const { busy, error, run } = useApiOperation()

function load(number = 1): Promise<boolean> {
    return run(async () => {
        page.value = await userQuizzesApi.list({ page: number })
    })
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '--'
}

function state(value: string | null): string {
    if (!value) return 'no_deadline'
    return new Date(value).getTime() < Date.now() ? 'expired' : 'active'
}

onMounted(() => load())
</script>

<template>
    <UserLayout title="Квизы">
        <div class="space-y-4">
            <AlertMessage :message="error" />
            <section
                class="overflow-hidden border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <span class="inline-flex">
                        <span class="text-[#1793d1]">workspace</span>
                        <span class="text-[#827a8b] dark:text-[#9792a5]">/</span>
                        quizzes.index
                    </span>
                    <span
                        v-if="busy"
                        class="text-[#b24d91] dark:text-[#e781bd]"
                    >
                        синхронизация...
                    </span>
                </header>
                <div
                    class="hidden grid-cols-[7rem_minmax(12rem,1fr)_12rem_8rem_8rem] gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-[0.625rem] tracking-[0.12em] text-[#827a8b] dark:border-[#363845] dark:bg-[#191b24] dark:text-[#9792a5] xl:grid"
                >
                    <span>id</span>
                    <span>название</span>
                    <span>срок</span>
                    <span>состояние</span>
                    <span>попытка</span>
                </div>
                <p
                    v-if="busy && !page"
                    class="px-4 py-8 text-center font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    запрос выполняется...
                </p>
                <p
                    v-else-if="page && !page.data.length"
                    class="px-4 py-8 text-center font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    назначенных квизов нет
                </p>
                <ul class="divide-y divide-[#dfdae5] dark:divide-[#242632]">
                    <li
                        v-for="quiz in page?.data"
                        :key="quiz.uuid"
                    >
                        <RouterLink
                            :to="{ name: 'quizzes.attempt', params: { quiz: quiz.uuid } }"
                            class="group grid gap-1.5 px-3 py-3 transition-colors hover:bg-[#ede9f1] dark:hover:bg-[#191b24] xl:grid-cols-[7rem_minmax(12rem,1fr)_12rem_8rem_8rem] xl:items-center xl:gap-3 xl:py-2.5"
                        >
                            <code class="text-[0.6875rem] text-[#827a8b] dark:text-[#9792a5]">
                                {{ quiz.uuid.slice(0, 16) }}
                            </code>
                            <div class="min-w-0">
                                <p class="truncate font-mono text-sm">{{ quiz.title }}</p>
                                <p
                                    v-if="quiz.description"
                                    class="mt-0.5 truncate text-xs text-[#686171] dark:text-[#9792a5]"
                                >
                                    {{ quiz.description }}
                                </p>
                            </div>
                            <time
                                class="font-mono text-[0.6875rem] text-[#686171] dark:text-[#9792a5]"
                            >
                                {{ formatDate(quiz.due_at) }}
                            </time>
                            <span
                                class="font-mono text-[0.6875rem]"
                                :class="
                                    state(quiz.due_at) === 'expired'
                                        ? 'text-[#b24d91] dark:text-[#e781bd]'
                                        : 'text-[#447b9e] dark:text-[#8eb4d1]'
                                "
                            >
                                {{ state(quiz.due_at) }}
                            </span>
                            <span
                                class="font-mono text-[0.6875rem] text-[#447b9e] dark:text-[#8eb4d1]"
                            >
                                {{
                                    quiz.result?.percentage != null
                                        ? `${quiz.result.percentage}%`
                                        : quiz.attempt_status === 'submitted'
                                          ? '[~] На проверке'
                                          : quiz.attempt_status === 'in_progress'
                                            ? '[>] Продолжить'
                                            : quiz.attempt_status === 'expired'
                                              ? 'Срок истёк'
                                              : '[ ] Начать'
                                }}
                            </span>
                        </RouterLink>
                    </li>
                </ul>
                <Pagination
                    v-if="page"
                    label="pager://workspace"
                    :current-page="page.meta.current_page"
                    :last-page="page.meta.last_page"
                    :loading="busy"
                    @change="load"
                />
            </section>
            <BaseButton
                v-if="!page && !busy"
                @click="load()"
            >
                Повторить запрос
            </BaseButton>
        </div>
    </UserLayout>
</template>
