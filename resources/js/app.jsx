import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'StataNex.Ai Admin';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        // Set favicon
        const favicon = document.querySelector('link[rel="icon"]');
        if (favicon) {
            favicon.href = '/favicon.ico';
        }

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
