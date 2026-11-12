import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/chat/main.js', 'resources/css/operator.css', 'resources/js/operator.js'],
            refresh: true,
        }),
    ],
    build: { target: 'es2022', chunkSizeWarningLimit: 600 },
});
