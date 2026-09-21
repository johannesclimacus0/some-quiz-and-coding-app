<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AlertMessage from './AlertMessage.vue'
import BaseButton from './BaseButton.vue'
import FontScaleControl from './FontScaleControl.vue'
import ThemeToggle from './ThemeToggle.vue'

const authStore = useAuthStore()
const router = useRouter()
const logoutError = ref('')
const loggingOut = ref(false)

const logout = async (): Promise<void> => {
    logoutError.value = ''
    loggingOut.value = true

    try {
        await authStore.logout()
        await router.push('/login')
    } catch (error: unknown) {
        logoutError.value = error instanceof Error ? error.message : 'Не удалось выйти'
    } finally {
        loggingOut.value = false
    }
}
</script>

<template>
    <footer
        class="border-t border-[#cec9d5] bg-[#ede9f1] px-4 py-1.5 dark:border-[#363845] dark:bg-[#242632] sm:px-6"
    >
        <div
            class="mx-auto flex w-full max-w-[90rem] flex-wrap items-center justify-between gap-4 font-mono text-xs font-medium text-[#777080] dark:text-[#85899a]"
        >
            <div
                v-if="authStore.user"
                class="min-w-0"
            >
                <p class="truncate text-[#342d3a] dark:text-[#ece8f1]">{{ authStore.user.name }}</p>
                <p class="truncate">{{ authStore.user.email }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <FontScaleControl />
                <ThemeToggle />
                <BaseButton
                    :loading="loggingOut"
                    loading-text="Выход…"
                    class="min-h-7 px-2 py-0.5 text-xs"
                    @click="logout"
                >
                    Выйти
                </BaseButton>
            </div>
        </div>
        <AlertMessage
            v-if="logoutError"
            :message="logoutError"
            class="mx-auto mt-2 max-w-[90rem]"
        />
    </footer>
</template>
