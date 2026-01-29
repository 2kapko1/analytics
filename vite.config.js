import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.jsx',
                'resources/js/tracker/tracker.ts',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                // Ensure tracker.js has a predictable name
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'tracker') {
                        return 'assets/tracker.js';
                    }
                    return 'assets/[name]-[hash].js';
                },
            },
        },
    },
});
