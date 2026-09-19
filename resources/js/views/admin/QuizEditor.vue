<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
    quizzesApi,
    type Quiz,
    type QuizInput,
    type Question,
    type TextInput,
} from '../../api/quizzes'
import AlertMessage from '../../components/AlertMessage.vue'
import QuizForm from '../../components/admin/QuizForm.vue'
import QuestionEditor from '../../components/admin/QuestionEditor.vue'
import QuizAttemptsPanel from '../../components/admin/QuizAttemptsPanel.vue'
import TextItemForm from '../../components/admin/TextItemForm.vue'
import { useApiOperation } from '../../composables/useApiOperation'
import AdminLayout from '../../layouts/AdminLayout.vue'

const route = useRoute()
const router = useRouter()
const quiz = ref<Quiz | null>(null)
const activeNode = ref('quiz')
const questionFormKey = ref(0)
const saved = ref(false)
const { busy, error, errors, run } = useApiOperation()
const errorTarget = ref('')

const questions = computed(() => quiz.value?.questions ?? [])
const activeQuestion = computed(
    () => questions.value.find((question) => question.uuid === activeNode.value) ?? null,
)
const nextQuestionPosition = computed(
    () => Math.max(-1, ...questions.value.map((question) => question.position)) + 1,
)

let loadVersion = 0
watch(
    () => route.params.quiz,
    async (uuid) => {
        const version = ++loadVersion
        quiz.value = null
        activeNode.value = 'quiz'
        await run(async () => {
            const result = await quizzesApi.show({ uuid: String(uuid) })
            if (version === loadVersion) quiz.value = result
        })
    },
    { immediate: true },
)

async function save(input: QuizInput) {
    if (!quiz.value) return
    const current = quiz.value
    errorTarget.value = 'quiz'
    saved.value = false
    await run(async () => {
        const result = await quizzesApi.update({ uuid: current.uuid, input })
        quiz.value = { ...result, questions: current.questions }
        saved.value = true
    })
}

async function addQuestion(input: TextInput) {
    if (!quiz.value) return
    const current = quiz.value
    errorTarget.value = 'question'
    await run(async () => {
        const created = await quizzesApi.createQuestion({ quiz: current.uuid, input })
        quiz.value = await quizzesApi.show({ uuid: current.uuid })
        questionFormKey.value++
        activeNode.value = created.uuid
    })
}

function updateQuestion(question: Question): void {
    if (!quiz.value?.questions) return
    quiz.value.questions = quiz.value.questions
        .map((item) => (item.uuid === question.uuid ? question : item))
        .sort((a, b) => a.position - b.position)
}

function removeQuestion(uuid: string): void {
    if (!quiz.value?.questions) return
    quiz.value.questions = quiz.value.questions.filter((question) => question.uuid !== uuid)
    activeNode.value = quiz.value.questions[0]?.uuid ?? 'quiz'
}

function questionState(question: Question): string {
    if (!question.answers?.length) return 'empty'
    return question.answers.some((answer) => answer.is_correct) ? 'ready' : 'invalid'
}

async function removeQuiz() {
    if (!quiz.value || !window.confirm('Удалить квиз со всеми вопросами и ответами?')) return
    const uuid = quiz.value.uuid
    await run(async () => {
        await quizzesApi.remove({ uuid })
        await router.push({ name: 'admin.quizzes' })
    })
}
</script>

<template>
    <AdminLayout :title="quiz?.title ?? 'Редактирование квиза'">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 font-mono text-xs">
                <RouterLink
                    :to="{ name: 'admin.quizzes' }"
                    class="text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
                >
                    <-- список квизов
                </RouterLink>
                <span
                    v-if="quiz"
                    class="text-[#96909e] dark:text-[#656879]"
                >
                    id:{{ quiz.uuid.slice(0, 16) }}
                </span>
            </div>
            <AlertMessage :message="error" />
            <p
                v-if="busy && !quiz"
                class="border border-[#c9c1cf] p-8 text-center font-mono text-xs dark:border-[#343746]"
            >
                загрузка рабочей области...
            </p>
            <section
                v-if="quiz"
                class="overflow-hidden border border-[#c9c1cf] bg-[#fbfafd] dark:border-[#343746] dark:bg-[#11131a]"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c9c1cf] bg-[#e8e4eb] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#181b23]"
                >
                    <span class="inline-flex">
                        <span class="text-[#1793d1]">editor</span>
                        <span class="text-[#96909e] dark:text-[#656879]">://</span>
                        {{ quiz.title }}
                    </span>
                    <div class="flex items-center gap-4">
                        <span
                            v-if="busy"
                            class="text-[#b24d91] dark:text-[#e781bd]"
                        >
                            сохранение...
                        </span>
                        <button
                            type="button"
                            class="text-[#a34a70] transition-colors hover:text-[#c14378] dark:text-[#cf729a] dark:hover:text-[#f077a8]"
                            :disabled="busy"
                            @click="removeQuiz"
                        >
                            [ удалить квиз ]
                        </button>
                    </div>
                </header>
                <div class="grid min-h-[36rem] lg:grid-cols-[22rem_minmax(0,1fr)]">
                    <aside
                        class="border-b border-[#c9c1cf] bg-[#f2eff5] dark:border-[#343746] dark:bg-[#15171e] lg:border-r lg:border-b-0"
                    >
                        <div
                            class="border-b border-[#d8d1dc] px-3 py-2.5 font-mono text-xs font-semibold tracking-[0.1em] text-[#5f5866] dark:border-[#343746] dark:text-[#b7b2c2]"
                        >
                            Project tree
                        </div>
                        <nav class="p-2 font-mono text-xs">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 px-2 py-2 text-left transition-colors"
                                :class="
                                    activeNode === 'quiz'
                                        ? 'bg-[#ddd6e1] text-[#27232d] dark:bg-[#30333e] dark:text-[#f0edf3]'
                                        : 'text-[#68616f] hover:bg-[#e8e4eb] dark:text-[#918da0] dark:hover:bg-[#1d2029]'
                                "
                                @click="activeNode = 'quiz'"
                            >
                                <span class="text-[#1793d1]">◆</span>
                                <span class="truncate">настройки квиза</span>
                            </button>
                            <button
                                type="button"
                                class="mt-1 flex w-full items-center gap-2 px-2 py-2 text-left transition-colors"
                                :class="
                                    activeNode === 'attempts'
                                        ? 'bg-[#ddd6e1] text-[#27232d] dark:bg-[#30333e] dark:text-[#f0edf3]'
                                        : 'text-[#68616f] hover:bg-[#e8e4eb] dark:text-[#918da0] dark:hover:bg-[#1d2029]'
                                "
                                @click="activeNode = 'attempts'"
                            >
                                <span class="text-[#1793d1]">◇</span>
                                <span class="truncate">Попытки/</span>
                            </button>
                            <div
                                class="mt-2 border-t border-dashed border-[#c9c1cf] pt-2 dark:border-[#343746]"
                            >
                                <div
                                    class="px-2 pb-1.5 font-mono text-[0.6875rem] font-semibold tracking-[0.1em] text-[#5f5866] dark:text-[#b7b2c2]"
                                >
                                    Вопросы/
                                </div>
                                <button
                                    v-for="(question, index) in questions"
                                    :key="question.uuid"
                                    type="button"
                                    class="grid w-full grid-cols-[2.5rem_minmax(0,1fr)] items-start gap-2 px-2 py-2.5 text-left transition-colors"
                                    :class="
                                        activeNode === question.uuid
                                            ? 'bg-[#ddd6e1] text-[#27232d] dark:bg-[#30333e] dark:text-[#f0edf3]'
                                            : 'text-[#68616f] hover:bg-[#e8e4eb] dark:text-[#918da0] dark:hover:bg-[#1d2029]'
                                    "
                                    @click="activeNode = question.uuid"
                                >
                                    <span
                                        class="pt-0.5 text-[0.6875rem] text-[#96909e] dark:text-[#656879]"
                                    >
                                        Q{{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block break-words text-[0.8125rem] leading-5">
                                            {{ question.text }}
                                        </span>
                                        <span
                                            class="mt-0.5 block text-[0.625rem]"
                                            :class="
                                                questionState(question) === 'ready'
                                                    ? 'text-[#557789] dark:text-[#8ca8b7]'
                                                    : 'text-[#b24d91] dark:text-[#e781bd]'
                                            "
                                        >
                                            {{ questionState(question) }} ·
                                            {{ question.answers?.length ?? 0 }} ответов
                                        </span>
                                    </span>
                                </button>
                                <button
                                    @click="activeNode = 'new-question'"
                                    type="button"
                                    class="mt-1 w-full px-2 py-2 text-left text-[#557789] transition-colors hover:bg-[#e8e4eb] hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:bg-[#1d2029] dark:hover:text-[#65b7df]"
                                >
                                    + новый вопрос
                                </button>
                            </div>
                        </nav>
                    </aside>

                    <main class="min-w-0">
                        <div
                            class="border-b border-[#d8d1dc] bg-[#f7f5f8] px-4 py-2 font-mono text-[0.6875rem] text-[#96909e] dark:border-[#343746] dark:bg-[#13151c] dark:text-[#656879]"
                        >
                            buffer://{{
                                activeNode === 'quiz'
                                    ? 'quiz.config'
                                    : activeNode === 'attempts'
                                      ? 'attempts.index'
                                      : activeNode === 'new-question'
                                        ? 'question.new'
                                        : `question/${activeQuestion?.uuid.slice(0, 16)}`
                            }}
                        </div>
                        <div v-if="activeNode === 'quiz'">
                            <QuizForm
                                :quiz="quiz"
                                :busy="busy"
                                :errors="errorTarget === 'quiz' ? errors : {}"
                                @save="save"
                            />
                            <p
                                v-if="saved"
                                class="px-4 pb-4 font-mono text-xs text-[#557789] dark:text-[#8ca8b7]"
                            >
                                изменения сохранены.
                            </p>
                        </div>
                        <QuizAttemptsPanel
                            v-else-if="activeNode === 'attempts'"
                            :quiz-uuid="quiz.uuid"
                        />
                        <div
                            v-else-if="activeNode === 'new-question'"
                            class="p-4"
                        >
                            <div class="mb-5 font-mono text-xs text-[#68616f] dark:text-[#918da0]">
                                <p class="text-sm text-[#27232d] dark:text-[#e8e5ef]">
                                    Создание вопроса
                                </p>
                                <p class="mt-1 text-[0.6875rem]">
                                    следующая позиция: {{ nextQuestionPosition }}
                                </p>
                            </div>
                            <TextItemForm
                                :key="questionFormKey"
                                id="new-question"
                                label="Текст вопроса"
                                :value="{ text: '', position: nextQuestionPosition }"
                                :max-length="4096"
                                :busy="busy"
                                :errors="errorTarget === 'question' ? errors : {}"
                                submit-label="Создать вопрос"
                                @save="addQuestion"
                            />
                        </div>
                        <QuestionEditor
                            v-else-if="activeQuestion"
                            :quiz-uuid="quiz.uuid"
                            :question="activeQuestion"
                            @updated="updateQuestion"
                            @removed="removeQuestion(activeQuestion.uuid)"
                        />
                    </main>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
