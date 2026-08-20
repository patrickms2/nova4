import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/nova.css',
                'resources/js/app.js',
                'resources/js/comunigest-login.js',
                'resources/css/filament/admin/theme.css',
                'resources/js/front.js',
                'resources/js/react-flow-panel-builder.jsx',
                'resources/css/filament/app/theme.css',
                'resources/css/filament/portal/theme.css',
                'resources/img/logo.png',
                'resources/img/logo-dark.png',
                'resources/css/portal-taxista.css',
                'resources/css/portal.css',            ],
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        tailwindcss(),
        nativephpMobile(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
