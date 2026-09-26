import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const defaultAppName = 'PistonCRM';

const getAppName = () => {
    if (typeof document !== 'undefined') {
        const meta = document.querySelector('meta[name="crm-name"]');
        if (meta && meta.content) {
            return meta.content;
        }
    }
    return import.meta.env.VITE_APP_NAME || defaultAppName;
};

createInertiaApp({
    title: (title) => {
        const appName = getAppName();
        return title ? `${title} - ${appName}` : appName;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
