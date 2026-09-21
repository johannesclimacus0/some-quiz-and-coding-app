import http from './http'
import type { Page, PageOptions } from './types'

export type AdminAttemptStatus = 'in_progress' | 'submitted' | 'completed' | 'expired'

export interface AdminAttemptResult {
    earned_points: number | null
    max_points: number
    percentage: number | null
    grading_status: 'pending' | 'graded'
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

export interface AdminSingleChoiceAttemptQuestion {
    uuid: string
    type: 'single_choice'
    text: string
    position: number
    state: 'correct' | 'incorrect' | 'empty'
    answers: AdminAttemptAnswer[]
}

export interface AdminTextAttemptQuestion {
    uuid: string
    type: 'text'
    text: string
    position: number
    state: 'pending_manual' | 'graded' | 'empty'
    response: { text: string | null }
    criteria: string | null
    max_points: number
    awarded_points: number | null
    feedback: string | null
    grading_version: number
}

export type AdminAttemptQuestion = AdminSingleChoiceAttemptQuestion | AdminTextAttemptQuestion

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
    async gradeAttemptAnswer({
        quiz,
        attempt,
        question,
        awardedPoints,
        feedback,
        expectedVersion,
    }: {
        quiz: string
        attempt: string
        question: string
        awardedPoints: number
        feedback: string | null
        expectedVersion: number
    }): Promise<AdminAttemptDetail> {
        return (
            await http.put<{ data: AdminAttemptDetail }>(
                `${path(quiz)}/${attempt}/answers/${question}/grade`,
                {
                    awarded_points: awardedPoints,
                    feedback,
                    expected_version: expectedVersion,
                },
            )
        ).data.data
    },
}
