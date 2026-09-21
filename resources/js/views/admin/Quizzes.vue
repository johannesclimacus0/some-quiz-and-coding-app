<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { quizzesApi, type QuizImportResult, type QuizInput, type QuizPage } from '../../api/quizzes'
import AlertMessage from '../../components/AlertMessage.vue'
import BaseButton from '../../components/BaseButton.vue'
import Pagination from '../../components/Pagination.vue'
import QuizForm from '../../components/admin/QuizForm.vue'
import { useApiOperation } from '../../composables/useApiOperation'
import AdminLayout from '../../layouts/AdminLayout.vue'

const router = useRouter()
const page = ref<QuizPage | null>(null)
const creating = ref(false)
const importing = ref(false)
const importFile = ref<File | null>(null)
const importResult = ref<QuizImportResult | null>(null)
const importInputKey = ref(0)
const { busy, error, errors, run } = useApiOperation()

function load(number = 1) {
    return run(async () => {
        page.value = await quizzesApi.list({ page: number })
    })
}

async function create(input: QuizInput) {
    await run(async () => {
        const quiz = await quizzesApi.create(input)
        await router.push({ name: 'admin.quizzes.edit', params: { quiz: quiz.uuid } })
    })
}

function toggleCreate(): void {
    creating.value = !creating.value
    importing.value = false
}

function toggleImport(): void {
    importing.value = !importing.value
    creating.value = false
}

function selectImportFile(event: Event): void {
    const input = event.target as HTMLInputElement
    importFile.value = input.files?.[0] ?? null
    importResult.value = null
}

async function importQuizzes(): Promise<void> {
    if (!importFile.value) {
        errors.value = { file: ['Выберите файл для импорта.'] }
        return
    }

    const file = importFile.value

    await run(async () => {
        importResult.value = await quizzesApi.import({ file })
        page.value = await quizzesApi.list({ page: 1 })
        importFile.value = null
        importInputKey.value++
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
    <AdminLayout title="Квизы">
        <div class="space-y-4">
            <AlertMessage :message="error" />
            <section
                class="overflow-hidden border border-[#cec9d5] bg-[#fcfafd] dark:border-[#363845] dark:bg-[#101219]"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <div class="flex items-center font-mono text-xs">
                        <span class="text-[#1793d1]">db</span>
                        <span class="text-[#827a8b] dark:text-[#9792a5]">/</span>
                        <span>quizzes.index</span>
                        <span
                            v-if="busy"
                            class="ml-3 text-[#b24d91] dark:text-[#e781bd]"
                        >
                            синхронизация...
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            @click="toggleImport"
                            type="button"
                            class="font-mono text-xs text-[#447b9e] transition-colors hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        >
                            {{ importing ? '[ закрыть импорт ]' : '[ импорт ]' }}
                        </button>
                        <button
                            @click="toggleCreate"
                            type="button"
                            class="font-mono text-xs text-[#447b9e] transition-colors hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        >
                            {{ creating ? '[ закрыть ]' : '[ + новый квиз ]' }}
                        </button>
                    </div>
                </header>
                <div
                    v-if="creating"
                    class="border-b border-[#cec9d5] bg-[#f4f1f6] dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <div
                        class="border-b border-dashed border-[#cec9d5] px-4 py-2 font-mono text-[0.6875rem] text-[#827a8b] dark:border-[#363845] dark:text-[#9792a5]"
                    >
                        INSERT INTO quizzes
                    </div>
                    <QuizForm
                        :busy="busy"
                        :errors="errors"
                        @save="create"
                    />
                </div>
                <div
                    v-if="importing"
                    class="border-b border-[#cec9d5] bg-[#f4f1f6] dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <div
                        class="border-b border-dashed border-[#cec9d5] px-4 py-2 font-mono text-[0.6875rem] text-[#827a8b] dark:border-[#363845] dark:text-[#9792a5]"
                    >
                        COPY quizzes FROM file
                    </div>
                    <form
                        @submit.prevent="importQuizzes"
                        class="space-y-3 p-4"
                    >
                        <fieldset
                            :disabled="busy"
                            class="flex flex-wrap items-end gap-3"
                        >
                            <label
                                class="min-w-0 flex-1 basis-72 space-y-2 font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                            >
                                <span class="block">JSON или Markdown · максимум 2 МБ</span>
                                <input
                                    @change="selectImportFile"
                                    :key="importInputKey"
                                    type="file"
                                    accept=".json,.md,.markdown,application/json,text/markdown,text/plain"
                                    class="block w-full border border-[#cec9d5] bg-[#fcfafd] px-2.5 py-2 text-xs file:mr-3 file:border-0 file:bg-transparent file:font-mono file:text-[#447b9e] dark:border-[#363845] dark:bg-[#101219] dark:file:text-[#8eb4d1]"
                                />
                            </label>
                            <BaseButton
                                type="submit"
                                variant="primary"
                                :loading="busy"
                                loading-text="Импорт…"
                            >
                                Импортировать
                            </BaseButton>
                        </fieldset>
                        <ul
                            v-if="Object.keys(errors).length"
                            class="space-y-1 font-mono text-xs text-[#a34a70] dark:text-[#f077a8]"
                        >
                            <template
                                v-for="(messages, field) in errors"
                                :key="field"
                            >
                                <li
                                    v-for="message in messages"
                                    :key="message"
                                >
                                    <span class="text-[#827a8b] dark:text-[#9792a5]">
                                        {{ field }}:
                                    </span>
                                    {{ message }}
                                </li>
                            </template>
                        </ul>
                        <p
                            v-if="importResult"
                            class="font-mono text-xs text-[#447b9e] dark:text-[#8eb4d1]"
                        >
                            импорт завершён · квизов: {{ importResult.quizzes }} · вопросов:
                            {{ importResult.questions }} · ответов: {{ importResult.answers }}
                        </p>
                    </form>
                </div>
                <div
                    class="hidden grid-cols-[7rem_minmax(12rem,1fr)_11rem_8rem_3rem] gap-3 border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-[0.625rem] uppercase tracking-[0.12em] text-[#827a8b] dark:border-[#363845] dark:bg-[#191b24] dark:text-[#9792a5] xl:grid"
                >
                    <span>id</span>
                    <span>название</span>
                    <span>срок</span>
                    <span>состояние</span>
                    <span></span>
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
                    запрос вернул 0 строк
                </p>
                <ul class="divide-y divide-[#dfdae5] dark:divide-[#242632]">
                    <li
                        v-for="quiz in page?.data"
                        :key="quiz.uuid"
                    >
                        <RouterLink
                            :to="{ name: 'admin.quizzes.edit', params: { quiz: quiz.uuid } }"
                            class="group grid gap-1.5 px-3 py-3 transition-colors hover:bg-[#ede9f1] dark:hover:bg-[#191b24] xl:grid-cols-[7rem_minmax(12rem,1fr)_11rem_8rem_3rem] xl:items-center xl:gap-3 xl:py-2.5"
                        >
                            <code class="text-[0.6875rem] text-[#827a8b] dark:text-[#9792a5]">
                                {{ quiz.uuid.slice(0, 16) }}
                            </code>
                            <span class="min-w-0 truncate font-mono text-sm">{{ quiz.title }}</span>
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
                                class="hidden text-right font-mono text-xs text-[#aaa4af] transition-colors group-hover:text-[#287da8] dark:text-[#505362] dark:group-hover:text-[#65b7df] md:block"
                            >
                                открыть
                            </span>
                        </RouterLink>
                    </li>
                </ul>
                <Pagination
                    v-if="page"
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
    </AdminLayout>
</template>
