import { createWebHistory, createRouter } from 'vue-router'


const routes = [
    { path: '/', redirect: '/home' },
    { path: '/home', component: () => import('../views/ReverbTest.vue')}
]

export const router = createRouter({
    history: createWebHistory(),
    routes,
})
