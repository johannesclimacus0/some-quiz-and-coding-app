import axios from 'axios'
import { ref } from 'vue'

export function useApiOperation() {
    const busy = ref(false)
    const error = ref('')
    const errors = ref<Record<string, string[]>>({})
    async function run(operation: () => Promise<void>): Promise<boolean> {
        if (busy.value) return false
        busy.value = true
        error.value = ''
        errors.value = {}
        try {
            await operation()
            return true
        } catch (cause: unknown) {
            if (
                axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(cause)
            ) {
                errors.value = cause.response?.data?.errors ?? {}
                const status = cause.response?.status
                error.value =
                    status === 401 || status === 419
                        ? 'Сессия истекла. Обновите страницу и войдите снова.'
                        : status === 403
                          ? 'Недостаточно прав.'
                          : status === 404
                            ? 'Запись не найдена или была удалена. Обновите страницу.'
                            : status === 422
                              ? 'Проверьте введённые данные.'
                              : 'Не удалось выполнить запрос. Попробуйте ещё раз.'
            } else {
                error.value = 'Не удалось выполнить операцию. Попробуйте ещё раз.'
            }
            return false
        } finally {
            busy.value = false
        }
    }
    return { busy, error, errors, run }
}
