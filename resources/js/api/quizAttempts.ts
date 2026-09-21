import http from './http'
import type { ProgrammingLanguage } from './quizzes'

export type AttemptStatus = 'in_progress' | 'submitted' | 'completed' | 'expired'

export interface AttemptAnswer {
    uuid: string
    text: string
    position: number
}

interface BaseAttemptQuestion {
    uuid: string
    text: string
    position: number
    max_points: number
}

export interface SingleChoiceAttemptQuestion extends BaseAttemptQuestion {
    type: 'single_choice'
    public_config: {
        answers: AttemptAnswer[]
    }
}

export interface TextAttemptQuestion extends BaseAttemptQuestion {
    type: 'text'
    public_config: {
        max_length: number
    }
}

export interface CodeAttemptQuestion extends BaseAttemptQuestion {
    type: 'code'
    public_config: {
        language: ProgrammingLanguage
        label: string
        editor_id: string
        file_extension: string
        max_length: number
    }
}

export type AttemptQuestion =
    SingleChoiceAttemptQuestion | TextAttemptQuestion | CodeAttemptQuestion

export interface AttemptResult {
    earned_points: number | null
    max_points: number
    percentage: number | null
    grading_status: 'pending' | 'graded'
    graded_at: string | null
}

export interface SingleChoiceResponse {
    answer_uuid: string
    awarded_points?: number | null
    feedback?: string | null
}

export interface TextResponse {
    text: string
    awarded_points?: number | null
    feedback?: string | null
}

export interface CodeResponse {
    code: string
    awarded_points?: number | null
    feedback?: string | null
}

export type QuestionResponse = SingleChoiceResponse | TextResponse | CodeResponse

export interface QuizAttempt {
    uuid: string
    status: AttemptStatus
    snapshot: {
        quiz: { uuid: string; title: string; description: string | null }
        questions: AttemptQuestion[]
    }
    responses: Record<string, QuestionResponse>
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
        response,
    }: {
        quiz: string
        question: string
        response: QuestionResponse
    }): Promise<QuizAttempt> {
        return (
            await http.put<{ data: QuizAttempt }>(`${root}/${quiz}/attempt/answers/${question}`, {
                response,
            })
        ).data.data
    },
    async submit({ quiz }: { quiz: string }): Promise<QuizAttempt> {
        return (await http.post<{ data: QuizAttempt }>(`${root}/${quiz}/attempt/submit`)).data.data
    },
}
