<script setup lang="ts">
import axios from 'axios'
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
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
const route = useRoute()
const router = useRouter()

const formData = reactive({
    email: getLastAuthEmail(),
    password: '',
    remember: false,
})

const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const loading = ref(false)

const firstError = (field: string): string | undefined => errors.value[field]?.[0]

const redirectAfterLogin = (): string => {
    const redirect = route.query.redirect

    return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
        ? redirect
        : '/home'
}

const submit = async (): Promise<void> => {
    errors.value = {}
    generalError.value = ''
    loading.value = true

    try {
        await authStore.login({ ...formData })
        await router.push(redirectAfterLogin())
    } catch (error: unknown) {
        if (axios.isAxiosError<ErrorResponse>(error)) {
            errors.value = error.response?.data?.errors ?? {}

            if (Object.keys(errors.value).length === 0) {
                generalError.value = error.response?.data?.message ?? 'Не удалось войти'
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
    <AuthLayout title="Вход">
        <form
            class="flex flex-col gap-4"
            @submit.prevent="submit"
        >
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
                autocomplete="current-password"
                :error="firstError('password')"
                required
            />
            <label
                class="flex items-center gap-2 font-mono text-xs font-medium text-[#68616f] dark:text-[#918da0]"
            >
                <input
                    v-model="formData.remember"
                    type="checkbox"
                    class="size-3.5 accent-[#b44fd1]"
                />
                Запомнить меня
            </label>
            <BaseButton
                type="submit"
                variant="primary"
                :loading="loading"
                loading-text="Вход…"
            >
                Войти
            </BaseButton>
            <AlertMessage :message="generalError" />
        </form>
        <template #footer>
            <RouterLink
                :to="{ name: 'register' }"
                class="text-[#557789] transition-colors hover:text-[#287da8] dark:text-[#8ca8b7] dark:hover:text-[#65b7df]"
            >
                Создать аккаунт
            </RouterLink>
        </template>
    </AuthLayout>
</template>
