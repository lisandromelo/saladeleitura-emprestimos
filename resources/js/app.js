import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (title) => {
        const name = document?.querySelector('meta[name="app-name"]')?.content || 'Sala de Leitura CEEBN';
        return title ? `${title} · ${name}` : name;
    },
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const colors = props.initialPage?.props?.branding?.colors || {};
        if (colors.primary) {
            document.documentElement.style.setProperty('--brand-primary', colors.primary);
            document.documentElement.style.setProperty('--brand-primary-dark', colors.primary_dark || colors.primary);
            document.documentElement.style.setProperty('--brand-secondary', colors.secondary || '#929497');
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#006D5B',
    },
});
