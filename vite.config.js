import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/editor/tinymce-init.js',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    // TinyMCE loads itself dynamically from /vendor/tinymce (web root),
                    // so copy to public/vendor/tinymce rather than public/build/vendor/tinymce.
                    src: 'node_modules/tinymce/**/*',
                    dest: '../vendor/tinymce',
                },
                {
                    src: 'resources/tiny-plugins/**/*',
                    dest: '../tiny-plugins',
                },
                {
                    src: 'node_modules/@fortawesome/fontawesome-free/webfonts/*',
                    dest: 'vendor/fontawesome/webfonts',
                },
            ],
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
