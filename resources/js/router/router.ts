import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
    { path: '/', redirect: { name: 'home' } },
    { path: '/home', name: 'home', component: () => import('../views/Home.vue'), meta: { requiresAuth: true } },
    { path: '/admin', name: 'admin.dashboard', component: () => import('../views/admin/Dashboard.vue'), meta: { requiresAuth: true, requiresAdmin: true },},
    { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: () => import('../views/Register.vue'), meta: { guestOnly: true } },
]

export const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const authStore = useAuthStore()

    if (!authStore.initialized) {
        try {
            await authStore.fetchUser()
        } catch (error: unknown) {
            console.error('Failed to initialize authentication state.', error)
        }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return {name: 'login', query: { redirect: to.fullPath } }
    }

    if (to.meta.requiresAdmin && !authStore.isAdmin) {
        return { name: 'home' }
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return { name: 'home' }
    }
})
