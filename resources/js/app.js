import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import {createI18n} from "vue-i18n"
import ru from './Lang/ru.json'
import {mask} from "vue-the-mask";
import { AuthPlugin } from "@/Modules/Auth/auth.plugin.ts";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const i18n = createI18n({
    locale: 'ru',
    fallbackLocale: 'ru',
    messages: {
        ru
    }
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        app.use(ZiggyVue)
        app.use(i18n)
        app.use(AuthPlugin)
        app.provide('scopes', app.config.globalProperties['$scopes'])

        app.directive('mask', mask)

        app.mount(el)
    },
    progress: {
        color: '#4B5563',
    },
});
