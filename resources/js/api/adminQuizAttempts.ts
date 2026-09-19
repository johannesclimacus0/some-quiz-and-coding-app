import http from './http'
import type { Page, PageOptions } from './types'

export type AdminAttemptStatus = 'in_progress' | 'completed' | 'expired'

export interface AdminAttemptResult {
    correct_answers: number
    total_questions: number
    percentage: number
}

export interface AdminAttemptSummary {
    uuid: string
    user: { uuid: string; name: string; email: string }
    status: AdminAttemptStatus
    started_at: string
    submitted_at: string | null
    result: AdminAttemptResult | null
}

export interface AdminAttemptAnswer {
    uuid: string
    text: string
    position: number
    is_correct: boolean
    is_selected: boolean
}

export interface AdminAttemptQuestion {
    uuid: string
    text: string
    position: number
    state: 'correct' | 'incorrect' | 'empty'
    answers: AdminAttemptAnswer[]
}

export interface AdminAttemptDetail extends AdminAttemptSummary {
    questions: AdminAttemptQuestion[]
}

const path = (quiz: string) => `/api/admin/quizzes/${quiz}/attempts`

export const adminQuizAttemptsApi = {
    async list({
        quiz,
        page = 1,
    }: PageOptions & { quiz: string }): Promise<Page<AdminAttemptSummary>> {
        return (await http.get<Page<AdminAttemptSummary>>(path(quiz), { params: { page } })).data
    },
    async show({ quiz, attempt }: { quiz: string; attempt: string }): Promise<AdminAttemptDetail> {
        return (await http.get<{ data: AdminAttemptDetail }>(`${path(quiz)}/${attempt}`)).data.data
    },
}
