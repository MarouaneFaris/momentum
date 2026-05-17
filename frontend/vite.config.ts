import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import fs from 'fs'
import path from 'path'
import { defineConfig } from 'vite'

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
        https: {
            cert: fs.readFileSync(process.env.FRONTEND_SSL_CERT as string),
            key: fs.readFileSync(process.env.FRONTEND_SSL_KEY as string),
        },
    },
})
