import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Ensure manifest is generated in the correct location
            buildDirectory: 'build',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        // Set the output directory to match Laravel's expected location
        outDir: 'public/build',
        manifest: true,
        emptyOutDir: true,
        chunkSizeWarningLimit: 1000,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@vue/runtime-core', '@vue/runtime-dom'],
                    'vendor-inertia': ['@inertiajs/vue3', '@inertiajs/core'],
                    'vendor-ui': ['sweetalert2', 'moment'],
                    'vendor-utils': ['lodash', 'axios'],
                    'chart-libs': ['chart.js', 'html2canvas', 'canvg'],
                },
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
    },
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'axios', 'lodash'],
    },
});
