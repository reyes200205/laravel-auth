import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';

// Configuration for local HTTPS with Laragon
const host = 'laravel-auth.test';
let serverConfig = {};

if (fs.existsSync('C:/laragon/etc/ssl/laragon.crt') && fs.existsSync('C:/laragon/etc/ssl/laragon.key')) {
    serverConfig = {
        host: '0.0.0.0',
        port: 5173,
        https: {
            key: fs.readFileSync('C:/laragon/etc/ssl/laragon.key'),
            cert: fs.readFileSync('C:/laragon/etc/ssl/laragon.crt'),
        },
        hmr: {
            host: host,
        },
    };
}

export default defineConfig({
    server: serverConfig,
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
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
});

