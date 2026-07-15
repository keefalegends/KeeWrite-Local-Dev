import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
  server: {
    port: 5173,
    // Proxy /api requests to Laravel so no CORS issue in development
    proxy: {
      '/api': {
        target: 'https://api.keewrite.my.id/api',
        changeOrigin: true,
      },
    },
  },
})
