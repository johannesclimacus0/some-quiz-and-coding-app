import http from './http'
import type { Page, PageOptions } from './types'

export interface Answer {
    uuid: string
    text: string
    position: number
    is_correct: boolean
}
export interface Question {
    uuid: string
    text: string
    position: number
    type: 'single_choice' | 'text'
    max_points: number
    answers?: Answer[]
}
export interface Quiz {
    uuid: string
    title: string
    description: string | null
    due_at: string | null
    questions?: Question[]
}
export interface QuizInput {
    title: string
    description: string | null
    due_at: string | null
}
export interface TextInput {
    text: string
    position: number
}
export interface QuestionInput extends TextInput {
    type?: Question['type']
    max_points?: number
}
export type QuizPage = Page<Quiz>
export interface QuizImportResult {
    quizzes: number
    questions: number
    answers: number
}

const root = '/api/admin/quizzes'
const questionPath = (quiz: string, question: string) => `${root}/${quiz}/questions/${question}`

export const quizzesApi = {
    async list({ page = 1 }: PageOptions = {}): Promise<QuizPage> {
        return (await http.get<QuizPage>(root, { params: { page } })).data
    },
    async show({ uuid }: { uuid: string }): Promise<Quiz> {
        return (await http.get<{ data: Quiz }>(`${root}/${uuid}`)).data.data
    },
    async create(input: QuizInput): Promise<Quiz> {
        return (await http.post<{ data: Quiz }>(root, input)).data.data
    },
    async import({ file }: { file: File }): Promise<QuizImportResult> {
        const data = new FormData()
        data.append('file', file)

        return (await http.post<{ data: QuizImportResult }>(`${root}/import`, data)).data.data
    },
    async update({ uuid, input }: { uuid: string; input: QuizInput }): Promise<Quiz> {
        return (await http.patch<{ data: Quiz }>(`${root}/${uuid}`, input)).data.data
    },
    async remove({ uuid }: { uuid: string }): Promise<void> {
        await http.delete(`${root}/${uuid}`)
    },
    async createQuestion({
        quiz,
        input,
    }: {
        quiz: string
        input: QuestionInput
    }): Promise<Question> {
        return (await http.post<{ data: Question }>(`${root}/${quiz}/questions`, input)).data.data
    },
    async updateQuestion({
        quiz,
        question,
        input,
    }: {
        quiz: string
        question: string
        input: Partial<QuestionInput>
    }): Promise<void> {
        await http.patch(questionPath(quiz, question), input)
    },
    async removeQuestion({ quiz, question }: { quiz: string; question: string }): Promise<void> {
        await http.delete(questionPath(quiz, question))
    },
    async createAnswer({
        quiz,
        question,
        input,
    }: {
        quiz: string
        question: string
        input: TextInput
    }): Promise<void> {
        await http.post(`${questionPath(quiz, question)}/answers`, input)
    },
    async updateAnswer({
        quiz,
        question,
        answer,
        input,
    }: {
        quiz: string
        question: string
        answer: string
        input: TextInput
    }): Promise<void> {
        await http.patch(`${questionPath(quiz, question)}/answers/${answer}`, input)
    },
    async removeAnswer({
        quiz,
        question,
        answer,
    }: {
        quiz: string
        question: string
        answer: string
    }): Promise<void> {
        await http.delete(`${questionPath(quiz, question)}/answers/${answer}`)
    },
    async setCorrect({
        quiz,
        question,
        answer,
    }: {
        quiz: string
        question: string
        answer: string
    }): Promise<void> {
        await http.put(`${questionPath(quiz, question)}/correct-answer`, { answer_uuid: answer })
    },
}
