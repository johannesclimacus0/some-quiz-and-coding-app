<script setup lang="ts">
import axios from 'axios'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AlertMessage from '../components/AlertMessage.vue'
import FormField from '../components/FormField.vue'
import BaseButton from '../components/BaseButton.vue'
import { getLastAuthEmail } from '../composables/useLastAuthEmail'
import AuthLayout from '../layouts/AuthLayout.vue'
import { useAuthStore } from '../stores/auth'

interface ErrorResponse {
    errors?: Record<string, string[]>
    message?: string
}

const authStore = useAuthStore()
const router = useRouter()

const formData = reactive({
    name: '',
    email: getLastAuthEmail(),
    password: '',
    password_confirmation: '',
})

const errors = ref<Record<string, string[]>>({})
const loading = ref(false)
const generalError = ref('')

const firstError = (field: string): string | undefined => errors.value[field]?.[0]

const submit = async function (): Promise<void> {
    errors.value = {}
    loading.value = true
    generalError.value = ''

    try {
        await authStore.register({ ...formData })
        await router.push('/home')
    } catch (error: unknown) {
        if (axios.isAxiosError<ErrorResponse>(error)) {
            errors.value = error.response?.data?.errors ?? {}

            if (Object.keys(errors.value).length === 0) {
                generalError.value = error.response?.data?.message ?? 'Не удалось создать аккаунт'
            }
        } else {
            generalError.value = error instanceof Error ? error.message : 'Неизвестная ошибка'
        }
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <AuthLayout title="Регистрация">
        <form
            @submit.prevent="submit"
            class="flex flex-col gap-4"
        >
            <FormField
                id="name"
                v-model.trim="formData.name"
                label="Имя"
                type="text"
                autocomplete="name"
                :error="firstError('name')"
                required
            />
            <FormField
                id="email"
                v-model.trim="formData.email"
                label="Почта"
                type="email"
                autocomplete="email"
                :error="firstError('email')"
                required
            />
            <FormField
                id="password"
                v-model="formData.password"
                label="Пароль"
                type="password"
                autocomplete="new-password"
                minlength="8"
                :error="firstError('password')"
                required
            />
            <FormField
                id="password_confirmation"
                v-model="formData.password_confirmation"
                label="Повторите пароль"
                type="password"
                autocomplete="new-password"
                minlength="8"
                :error="firstError('password_confirmation')"
                required
            />
            <BaseButton
                type="submit"
                variant="primary"
                :loading="loading"
                loading-text="Регистрация…"
            >
                Зарегистрироваться
            </BaseButton>
            <AlertMessage :message="generalError" />
        </form>
        <template #footer>
            <RouterLink
                :to="{ name: 'login' }"
                class="text-[#447b9e] transition-colors hover:text-[#287da8] dark:text-[#8eb4d1] dark:hover:text-[#65b7df]"
            >
                Уже есть аккаунт? Войти
            </RouterLink>
        </template>
    </AuthLayout>
</template>
