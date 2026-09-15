import { createApp } from "vue";
import App from './App.vue';
import { configureEcho } from '@laravel/echo-vue';
import { router } from './router/router';

configureEcho({
    broadcaster: 'reverb',
});

createApp(App).use(router).mount('#app');
