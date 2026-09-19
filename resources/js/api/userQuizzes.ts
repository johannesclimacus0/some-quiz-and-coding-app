import http from './http'
import type { Page, PageOptions } from './types'

export interface AssignedQuiz {
    uuid: string
    title: string
    description: string | null
    due_at: string | null
    attempt_status: 'not_started' | 'in_progress' | 'completed' | 'expired'
    result: {
        correct_answers: number
        total_questions: number
        percentage: number
    } | null
}

export type AssignedQuizPage = Page<AssignedQuiz>

export const userQuizzesApi = {
    async list({ page = 1 }: PageOptions = {}): Promise<AssignedQuizPage> {
        return (await http.get<AssignedQuizPage>('/api/quizzes', { params: { page } })).data
    },
}
