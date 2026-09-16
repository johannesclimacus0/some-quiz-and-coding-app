import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import { configureEcho } from '@laravel/echo-vue'
import { initializeFontScale } from './composables/useFontScale'
import { initializeTheme } from './composables/useTheme'
import { router } from './router/router'

initializeFontScale()
initializeTheme()

configureEcho({
    broadcaster: 'reverb',
})

const pinia = createPinia()
const app = createApp(App)

app.use(pinia).use(router).mount('#app')
