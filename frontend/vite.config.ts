import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import basicssl from '@vitejs/plugin-basic-ssl'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    basicssl(),
  ],
  server: {
    host: true,
    port: 3000,
    https: true,
  },
})
