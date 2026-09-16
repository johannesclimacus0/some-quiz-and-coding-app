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

export interface LoginResult {
    two_factor: boolean
}

export interface User {
    id: number
    uuid: string
    name: string
    email: string
    email_verified_at: string|null
    role: 'user'|'admin'
}

export const csrf = async (): Promise<void> => {
    await http.get('/sanctum/csrf-cookie')
}

export async function register(data: RegisterData): Promise<void> {
    await csrf()

    await http.post('/register', data)
}

export async function login(data: LoginData): Promise<LoginResult> {
    await csrf()

    return (await http.post<LoginResult>('/login', data)).data
}

export async function logout(): Promise<void> {
    await http.post('/logout')
}

export async function getCurrentUser(): Promise<User> {
    return (await http.get<User>('/api/user')).data
}
