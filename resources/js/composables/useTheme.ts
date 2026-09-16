import { computed, readonly, ref } from 'vue'

export type ThemePreference = 'system' | 'light' | 'dark'

const storageKey = 'theme-preference'
const preferences: ThemePreference[] = ['system', 'light', 'dark']
const preference = ref<ThemePreference>('system')
let mediaQuery: MediaQueryList | null = null
let listening = false

const isThemePreference = (value: string | null): value is ThemePreference => {
    return value !== null && preferences.includes(value as ThemePreference)
}

const applyTheme = (): void => {
    const dark = preference.value === 'dark'
        || (preference.value === 'system' && mediaQuery?.matches === true)

    document.documentElement.classList.toggle('dark', dark)
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light'
}

const savePreference = (): void => {
    localStorage.setItem(storageKey, preference.value)
}

export const initializeTheme = (): void => {
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

    try {
        const savedPreference = localStorage.getItem(storageKey)

        if (isThemePreference(savedPreference)) {
            preference.value = savedPreference
        }
    } catch {
        preference.value = 'system'
    }

    if (!listening) {
        mediaQuery.addEventListener('change', applyTheme)
        listening = true
    }

    applyTheme()
}

export const useTheme = () => {
    const label = computed(() => ({
        system: 'системная',
        light: 'светлая',
        dark: 'тёмная',
    })[preference.value])

    const setTheme = (theme: ThemePreference): void => {
        preference.value = theme
        savePreference()
        applyTheme()
    }

    const cycleTheme = (): void => {
        const currentIndex = preferences.indexOf(preference.value)

        setTheme(preferences[(currentIndex + 1) % preferences.length])
    }

    return {
        preference: readonly(preference),
        label,
        setTheme,
        cycleTheme,
    }
}
