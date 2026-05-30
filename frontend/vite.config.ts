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
})
