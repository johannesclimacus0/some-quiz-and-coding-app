import http from './http'
import type { Page, PageOptions } from './types'

export interface AssignedQuiz {
    uuid: string
    title: string
    description: string | null
    due_at: string | null
    attempt_status: 'not_started' | 'in_progress' | 'submitted' | 'completed' | 'expired'
    result: {
        earned_points: number | null
        max_points: number
        percentage: number | null
        grading_status: 'pending' | 'graded'
    } | null
}

export type AssignedQuizPage = Page<AssignedQuiz>

export const userQuizzesApi = {
    async list({ page = 1 }: PageOptions = {}): Promise<AssignedQuizPage> {
        return (await http.get<AssignedQuizPage>('/api/quizzes', { params: { page } })).data
    },
}
