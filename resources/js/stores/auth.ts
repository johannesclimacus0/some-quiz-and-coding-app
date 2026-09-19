import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi, type LoginData, type RegisterData, type User } from '../api/auth'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const initialized = ref(false)
    const isAuthenticated = computed(() => user.value !== null)
    const isAdmin = computed(() => user.value?.role === 'admin')

    const fetchUser = async (): Promise<User | null> => {
        try {
            user.value = await authApi.getCurrentUser()

            return user.value
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 401) {
                user.value = null
                return null
            }
            throw error
        } finally {
            initialized.value = true
        }
    }

    const requireAuthenticatedUser = async (): Promise<User> => {
        const authenticatedUser = await fetchUser()

        if (!authenticatedUser) {
            throw new Error('Сессия авторизации не была создана')
        }

        return authenticatedUser
    }

    const register = async (data: RegisterData): Promise<User> => {
        await authApi.register(data)

        return requireAuthenticatedUser()
    }

    const login = async (data: LoginData): Promise<User> => {
        await authApi.login(data)

        return requireAuthenticatedUser()
    }

    const logout = async (): Promise<void> => {
        await authApi.logout()
        user.value = null
        initialized.value = true
    }

    return {
        user,
        initialized,
        isAuthenticated,
        isAdmin,
        fetchUser,
        login,
        register,
        logout,
    }
})
