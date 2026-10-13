import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { fileURLToPath, URL } from "node:url";
const dockerDev = process.env.DOCKER_DEV === "true";
export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
            ...(dockerDev ? { hotFile: "storage/framework/vite.hot" } : {}),
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
    ...(dockerDev ? {
        server: {
            host: "0.0.0.0",
            port: 5173,
            strictPort: true,
            hmr: {
                host: "localhost",
                clientPort: Number(process.env.DOCKER_VITE_PORT || 5173),
            },
            watch: { usePolling: true, interval: 300 },
        },
    } : {}),
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
        },
        extensions: [".mjs", ".js", ".ts", ".json", ".vue"],
    },
});
