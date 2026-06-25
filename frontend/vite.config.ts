import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import fs from 'fs'
import path from 'path'
import { defineConfig } from 'vite'

const sslCert = process.env.FRONTEND_SSL_CERT
const sslKey = process.env.FRONTEND_SSL_KEY

// https://vite.dev/config/
export default defineConfig({
    plugins: [react(), tailwindcss()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
        },
    },
    server: {
        host: true,
        port: 3000,
        https:
            sslCert && sslKey
                ? { cert: fs.readFileSync(sslCert), key: fs.readFileSync(sslKey) }
                : undefined,
    },
    build: {
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: 'react-vendor',
                            test: /node_modules\/(react|react-dom|react-router)/,
                        },
                        { name: 'query', test: /node_modules\/@tanstack/ },
                        {
                            name: 'ui',
                            test: /node_modules\/(radix-ui|@radix-ui|lucide-react|next-themes|sonner|class-variance-authority|tailwind-merge|clsx)/,
                        },
                        { name: 'forms', test: /node_modules\/(react-hook-form|@hookform|zod)/ },
                        { name: 'sentry', test: /node_modules\/@sentry/ },
                    ],
                },
            },
        },
    },
})
