<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import http from '../../api/http'
import { quizzesApi, type Question, type QuestionInput, type TextInput } from '../../api/quizzes'
import AlertMessage from '../AlertMessage.vue'
import TextItemForm from './TextItemForm.vue'
import { useApiOperation } from '../../composables/useApiOperation'

const props = defineProps<{ quizUuid: string; question: Question }>()
const emit = defineEmits<{ updated: [question: Question]; removed: [] }>()
const { busy, error, errors, run } = useApiOperation()
const errorTarget = ref('')
const editingAnswer = ref<string | null>(null)
const addingAnswer = ref(false)
const answerFormKey = ref(0)
const settings = reactive<{ type: Question['type']; max_points: number }>({
    type: props.question.type,
    max_points: props.question.max_points,
})
const answers = computed(() => props.question.answers ?? [])
const nextAnswerPosition = computed(
    () => Math.max(-1, ...answers.value.map((answer) => answer.position)) + 1,
)

watch(
    () => props.question.uuid,
    () => {
        editingAnswer.value = null
        addingAnswer.value = false
        settings.type = props.question.type
        settings.max_points = props.question.max_points
    },
)

async function refresh(): Promise<void> {
    const response = await http.get<{ data: Question }>(
        `/api/admin/quizzes/${props.quizUuid}/questions/${props.question.uuid}`,
    )
    emit('updated', response.data.data)
}

async function mutate(target: string, operation: () => Promise<void>): Promise<boolean> {
    if (busy.value) return false
    errorTarget.value = target
    return run(async () => {
        await operation()
        await refresh()
    })
}

function saveQuestion(input: QuestionInput) {
    return mutate('question', () =>
        quizzesApi.updateQuestion({ quiz: props.quizUuid, question: props.question.uuid, input }),
    )
}

async function saveType(): Promise<void> {
    const previousType = props.question.type
    const saved = await mutate('question-settings', () =>
        quizzesApi.updateQuestion({
            quiz: props.quizUuid,
            question: props.question.uuid,
            input: { type: settings.type },
        }),
    )
    if (!saved) settings.type = previousType
}

async function savePoints(): Promise<void> {
    if (busy.value || settings.max_points === props.question.max_points) return
    await mutate('question-settings', () =>
        quizzesApi.updateQuestion({
            quiz: props.quizUuid,
            question: props.question.uuid,
            input: { max_points: settings.max_points },
        }),
    )
}

async function createAnswer(input: TextInput): Promise<void> {
    if (
        await mutate('new-answer', () =>
            quizzesApi.createAnswer({ quiz: props.quizUuid, question: props.question.uuid, input }),
        )
    ) {
        addingAnswer.value = false
        answerFormKey.value++
    }
}

async function saveAnswer(uuid: string, input: TextInput): Promise<void> {
    if (
        await mutate(uuid, () =>
            quizzesApi.updateAnswer({
                quiz: props.quizUuid,
                question: props.question.uuid,
                answer: uuid,
                input,
            }),
        )
    ) {
        editingAnswer.value = null
    }
}

async function removeQuestion(): Promise<void> {
    if (!window.confirm('Удалить вопрос и все его ответы?')) return
    if (
        await run(() =>
            quizzesApi.removeQuestion({ quiz: props.quizUuid, question: props.question.uuid }),
        )
    )
        emit('removed')
}

function removeAnswer(uuid: string) {
    if (!window.confirm('Удалить ответ?')) return
    return mutate(uuid, () =>
        quizzesApi.removeAnswer({
            quiz: props.quizUuid,
            question: props.question.uuid,
            answer: uuid,
        }),
    )
}
</script>

<template>
    <fieldset
        :disabled="busy"
        class="min-w-0"
    >
        <header
            class="flex flex-wrap items-start justify-between gap-3 border-b border-[#cec9d5] px-4 py-3 dark:border-[#363845]"
        >
            <div class="font-mono">
                <p
                    class="text-[0.625rem] uppercase tracking-[0.12em] text-[#827a8b] dark:text-[#9792a5]"
                >
                    запись вопроса
                </p>
                <p class="mt-1 text-xs text-[#686171] dark:text-[#9792a5]">
                    позиция={{ question.position }} · баллов={{ question.max_points }}
                </p>
            </div>
            <button
                type="button"
                class="font-mono text-xs text-[#a34a70] hover:text-[#c14378] dark:text-[#cf729a] dark:hover:text-[#f077a8]"
                @click="removeQuestion"
            >
                [ удалить вопрос ]
            </button>
        </header>
        <div class="space-y-6 p-4">
            <AlertMessage :message="error" />
            <TextItemForm
                :id="'question-' + question.uuid"
                label="Текст вопроса"
                :value="question"
                :max-length="4096"
                :busy="busy"
                :errors="errorTarget === 'question' ? errors : {}"
                submit-label="Сохранить вопрос"
                @save="saveQuestion"
            />
            <section
                class="grid gap-3 border border-[#cec9d5] p-3 dark:border-[#363845] sm:grid-cols-[minmax(0,1fr)_10rem]"
            >
                <label class="space-y-1 font-mono text-xs text-[#686171] dark:text-[#9792a5]">
                    <span>Тип вопроса</span>
                    <select
                        v-model="settings.type"
                        @change="saveType"
                        class="w-full border border-[#cec9d5] bg-white p-2 dark:border-[#363845] dark:bg-[#191b24]"
                    >
                        <option value="single_choice">Один вариант</option>
                        <option value="text">Текстовый ответ</option>
                    </select>
                </label>
                <label class="space-y-1 font-mono text-xs text-[#686171] dark:text-[#9792a5]">
                    <span>Максимальный балл</span>
                    <input
                        v-model.number="settings.max_points"
                        type="number"
                        min="1"
                        max="65535"
                        step="1"
                        class="w-full border border-[#cec9d5] bg-white p-2 dark:border-[#363845] dark:bg-[#191b24]"
                        @blur="savePoints"
                        @keydown.enter.prevent="savePoints"
                    />
                    <span
                        v-if="errorTarget === 'question-settings' && errors.max_points"
                        class="block text-[#b24d91] dark:text-[#e781bd]"
                    >
                        {{ errors.max_points[0] }}
                    </span>
                </label>
                <p
                    v-if="errorTarget === 'question-settings' && errors.type"
                    class="font-mono text-xs text-[#b24d91] sm:col-span-2 dark:text-[#e781bd]"
                >
                    {{ errors.type[0] }}
                </p>
            </section>
            <section
                v-if="question.type === 'single_choice'"
                class="border border-[#cec9d5] dark:border-[#363845]"
            >
                <header
                    class="flex items-center justify-between border-b border-[#cec9d5] bg-[#f3f1f6] px-3 py-2 font-mono text-xs dark:border-[#363845] dark:bg-[#191b24]"
                >
                    <span>
                        <span class="text-[#1793d1]">::</span>
                        ответы
                    </span>
                    <button
                        type="button"
                        class="text-[#447b9e] hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                        @click="addingAnswer = !addingAnswer"
                    >
                        {{ addingAnswer ? '[ закрыть ]' : '[ + ответ ]' }}
                    </button>
                </header>
                <p
                    v-if="!answers.length"
                    class="p-4 font-mono text-xs text-[#686171] dark:text-[#9792a5]"
                >
                    0 ответов · добавьте хотя бы одну запись
                </p>
                <ul
                    v-else
                    class="divide-y divide-[#dfdae5] dark:divide-[#242632]"
                >
                    <li
                        v-for="(answer, index) in answers"
                        :key="answer.uuid"
                    >
                        <div
                            class="grid grid-cols-[1.5rem_2.5rem_minmax(0,1fr)] items-center gap-2 px-3 py-2.5 font-mono text-xs xl:grid-cols-[1.5rem_2.5rem_minmax(0,1fr)_3.5rem_auto]"
                        >
                            <input
                                type="radio"
                                :name="'correct-' + question.uuid"
                                :checked="answer.is_correct"
                                :aria-label="`Отметить правильным: ${answer.text}`"
                                class="size-3.5 accent-[#447b9e] dark:accent-[#8eb4d1]"
                                @change="
                                    mutate(answer.uuid, () =>
                                        quizzesApi.setCorrect({
                                            quiz: quizUuid,
                                            question: question.uuid,
                                            answer: answer.uuid,
                                        }),
                                    )
                                "
                            />
                            <span class="text-[0.625rem] text-[#827a8b] dark:text-[#9792a5]">
                                A{{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <span
                                class="min-w-0 truncate"
                                :title="answer.text"
                            >
                                {{ answer.text }}
                            </span>
                            <span class="text-[0.625rem] text-[#827a8b] dark:text-[#9792a5]">
                                p={{ answer.position }}
                            </span>
                            <div class="col-span-2 flex flex-wrap gap-3 xl:col-span-1">
                                <button
                                    type="button"
                                    class="text-[#447b9e] hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
                                    @click="
                                        editingAnswer =
                                            editingAnswer === answer.uuid ? null : answer.uuid
                                    "
                                >
                                    изменить
                                </button>
                                <button
                                    type="button"
                                    class="text-[#a34a70] hover:text-[#c14378] dark:text-[#cf729a] dark:hover:text-[#f077a8]"
                                    @click="removeAnswer(answer.uuid)"
                                >
                                    удалить
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="editingAnswer === answer.uuid"
                            class="border-t border-dashed border-[#cec9d5] bg-[#f3f1f6] p-3 dark:border-[#363845] dark:bg-[#101219]"
                        >
                            <TextItemForm
                                :id="'answer-' + answer.uuid"
                                label="Редактирование ответа"
                                :value="answer"
                                :max-length="2048"
                                :busy="busy"
                                :errors="errorTarget === answer.uuid ? errors : {}"
                                @save="(input) => saveAnswer(answer.uuid, input)"
                            />
                        </div>
                    </li>
                </ul>
                <div
                    v-if="addingAnswer"
                    class="border-t border-dashed border-[#cec9d5] bg-[#f3f1f6] p-3 dark:border-[#363845] dark:bg-[#101219]"
                >
                    <TextItemForm
                        :key="answerFormKey"
                        :id="'new-answer-' + question.uuid"
                        label="Новый ответ"
                        :value="{ text: '', position: nextAnswerPosition }"
                        :max-length="2048"
                        :busy="busy"
                        :errors="errorTarget === 'new-answer' ? errors : {}"
                        submit-label="Создать ответ"
                        @save="createAnswer"
                    />
                </div>
            </section>
            <p
                v-if="
                    question.type === 'single_choice' &&
                    answers.length &&
                    !answers.some((answer) => answer.is_correct)
                "
                class="font-mono text-xs text-[#b24d91] dark:text-[#e781bd]"
            >
                предупреждение: правильный ответ не выбран
            </p>
        </div>
    </fieldset>
</template>
