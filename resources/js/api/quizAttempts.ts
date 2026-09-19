import http from './http'

export type AttemptStatus = 'in_progress' | 'completed' | 'expired'

export interface AttemptAnswer {
    uuid: string
    text: string
    position: number
}

export interface AttemptQuestion {
    uuid: string
    text: string
    position: number
    answers: AttemptAnswer[]
}

export interface AttemptResult {
    correct_answers: number
    total_questions: number
    percentage: number
    submitted_at: string
}

export interface QuizAttempt {
    uuid: string
    status: AttemptStatus
    snapshot: {
        quiz: { uuid: string; title: string; description: string | null }
        questions: AttemptQuestion[]
    }
    selected_answers: Record<string, string>
    result: AttemptResult | null
    started_at: string
}

export interface QuizDetails {
    uuid: string
    title: string
    description: string | null
    due_at: string | null
    questions_count: number
    attempt: QuizAttempt | null
}

const root = '/api/quizzes'

export const quizAttemptsApi = {
    async showQuiz({ quiz }: { quiz: string }): Promise<QuizDetails> {
        return (await http.get<{ data: QuizDetails }>(`${root}/${quiz}`)).data.data
    },
    async start({ quiz }: { quiz: string }): Promise<QuizAttempt> {
        return (await http.post<{ data: QuizAttempt }>(`${root}/${quiz}/attempt`)).data.data
    },
    async saveAnswer({
        quiz,
        question,
        answerUuid,
    }: {
        quiz: string
        question: string
        answerUuid: string
    }): Promise<QuizAttempt> {
        return (
            await http.put<{ data: QuizAttempt }>(`${root}/${quiz}/attempt/answers/${question}`, {
                answer_uuid: answerUuid,
            })
        ).data.data
    },
    async submit({ quiz }: { quiz: string }): Promise<AttemptResult> {
        return (await http.post<{ data: AttemptResult }>(`${root}/${quiz}/attempt/submit`)).data
            .data
    },
}
