import http from './http'

export interface RegisterData {
    name: string
    email: string
    password: string
    password_confirmation: string
}

export interface LoginData {
    email: string
    password: string
    remember: boolean
}

export interface User {
    id: number
    uuid: string
    name: string
    email: string
    email_verified_at: string | null
    role: 'user' | 'admin'
}

export const authApi = {
    async csrf(): Promise<void> {
        await http.get('/sanctum/csrf-cookie')
    },
    async register(data: RegisterData): Promise<void> {
        await authApi.csrf()

        await http.post('/register', data)
    },
    async login(data: LoginData): Promise<void> {
        await authApi.csrf()

        await http.post('/login', data)
    },
    async logout(): Promise<void> {
        await http.post('/logout')
    },
    async getCurrentUser(): Promise<User> {
        return (await http.get<User>('/api/user')).data
    },
}
