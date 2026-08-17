import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { collectModuleAssetsPaths } from './vite-module-loader';

async function getModulePaths() {
    const paths = [];
    await collectModuleAssetsPaths(paths, 'Modules');
    return paths;
}

export default defineConfig(async () => {
    const modulePaths = await getModulePaths();

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    ...modulePaths,
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});