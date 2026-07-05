import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    // Replicar el alias '@' → /src que usa vite.config.js
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    // Buscar tests junto al código fuente (patrón *.test.js / *.spec.js)
    include: ['src/**/*.{test,spec}.{js,mjs,ts}'],
  },
})
