import { readonly, ref } from 'vue'

const storageKey = 'font-scale'
const minimum = 80
const maximum = 200
const step = 10
const scale = ref(100)

const applyScale = (): void => {
    if (scale.value === 100) {
        document.documentElement.style.removeProperty('font-size')
        return
    }

    document.documentElement.style.fontSize = `${scale.value}%`
}

export const initializeFontScale = (): void => {
    try {
        const savedScale = Number(localStorage.getItem(storageKey))

        if (Number.isInteger(savedScale) && savedScale >= minimum && savedScale <= maximum && savedScale % step === 0) {
            scale.value = savedScale
        }
    } catch {
        scale.value = 100
    }

    applyScale()
}

const setScale = (value: number): void => {
    scale.value = Math.min(maximum, Math.max(minimum, value))
    applyScale()

    localStorage.setItem(storageKey, String(scale.value))
}

export const useFontScale = () => ({
    scale: readonly(scale),
    minimum,
    maximum,
    decrease: (): void => setScale(scale.value - step),
    increase: (): void => setScale(scale.value + step),
    reset: (): void => setScale(100),
})
