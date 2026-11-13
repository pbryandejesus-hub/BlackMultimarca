import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Montserrat', {
                    weights: [400, 500, 600],
                }),
                bunny('Cinzel', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // En Docker Vite escucha en 0.0.0.0, pero el navegador tiene que pedir los archivos a localhost
        // (Chrome bloquea 0.0.0.0 y la página se ve sin estilos).
        origin: 'http://localhost:5173',
        // La tienda (localhost:8000) pide los scripts a Vite (localhost:5173): sin esto Chrome los bloquea
        // por CORS y no funciona ningún botón (cantidades, talles, carrito, favoritos…).
        cors: {
            origin: /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/,
        },
        hmr: {
            host: 'localhost',
        },
        watch: {
            // vendor/ y storage/ tienen miles de archivos que el front no usa: vigilarlos en Windows
            // hacía que Vite tardara decenas de segundos en responder o se quedara colgado.
            ignored: ['**/vendor/**', '**/storage/**', '**/public/build/**'],
        },
    },
});
