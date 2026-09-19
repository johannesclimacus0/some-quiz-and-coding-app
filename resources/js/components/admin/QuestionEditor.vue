<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import http from '../../api/http'
import { quizzesApi, type Question, type TextInput } from '../../api/quizzes'
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
const answers = computed(() => props.question.answers ?? [])
const nextAnswerPosition = computed(
    () => Math.max(-1, ...answers.value.map((answer) => answer.position)) + 1,
)

watch(
    () => props.question.uuid,
    () => {
        editingAnswer.value = null
        addingAnswer.value = false
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

function saveQuestion(input: TextInput) {
    return mutate('question', () =>
        quizzesApi.updateQuestion({ quiz: props.quizUuid, question: props.question.uuid, input }),
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
            class="flex flex-wrap items-start justify-between gap-3 border-b border-[#d8d1dc] px-4 py-3 dark:border-[#343746]"
        >
            <div class="font-mono">
                <p
                    class="text-[0.625rem] uppercase tracking-[0.12em] text-[#96909e] dark:text-[#656879]"
                >
                    запись вопроса
                </p>
                <p class="mt-1 text-xs text-[#68616f] dark:text-[#918da0]">
                    позиция={{ question.position }} · ответов={{ answers.length }}
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
            <section class="border border-[#d8d1dc] dark:border-[#343746]">
                <header
                    class="flex items-center justify-between border-b border-[#d8d1dc] bg-[#f2eff5] px-3 py-2 font-mono text-xs dark:border-[#343746] dark:bg-[#15171e]"
                >
                    <span>
                        <span class="text-[#1793d1]">::</span>
                        ответы
                    </span>
                    <button
                        type="button"
                        class="text-[#557789] hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
                        @click="addingAnswer = !addingAnswer"
                    >
                        {{ addingAnswer ? '[ закрыть ]' : '[ + ответ ]' }}
                    </button>
                </header>
                <p
                    v-if="!answers.length"
                    class="p-4 font-mono text-xs text-[#68616f] dark:text-[#918da0]"
                >
                    0 ответов · добавьте хотя бы одну запись
                </p>
                <ul
                    v-else
                    class="divide-y divide-[#e0dae4] dark:divide-[#292c36]"
                >
                    <li
                        v-for="(answer, index) in answers"
                        :key="answer.uuid"
                    >
                        <div
                            class="grid grid-cols-[1.5rem_2.5rem_minmax(0,1fr)_3.5rem_auto] items-center gap-2 px-3 py-2.5 font-mono text-xs"
                        >
                            <input
                                type="radio"
                                :name="'correct-' + question.uuid"
                                :checked="answer.is_correct"
                                class="size-3.5 accent-[#557789] dark:accent-[#8ca8b7]"
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
                            <span class="text-[0.625rem] text-[#96909e] dark:text-[#656879]">
                                A{{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <span
                                class="min-w-0 truncate"
                                :title="answer.text"
                            >
                                {{ answer.text }}
                            </span>
                            <span class="text-[0.625rem] text-[#96909e] dark:text-[#656879]">
                                p={{ answer.position }}
                            </span>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="text-[#557789] hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
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
                            class="border-t border-dashed border-[#d8d1dc] bg-[#f7f5f8] p-3 dark:border-[#343746] dark:bg-[#13151c]"
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
                    class="border-t border-dashed border-[#d8d1dc] bg-[#f7f5f8] p-3 dark:border-[#343746] dark:bg-[#13151c]"
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
                v-if="answers.length && !answers.some((answer) => answer.is_correct)"
                class="font-mono text-xs text-[#b24d91] dark:text-[#e781bd]"
            >
                предупреждение: правильный ответ не выбран
            </p>
        </div>
    </fieldset>
</template>
