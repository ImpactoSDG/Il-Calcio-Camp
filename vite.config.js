import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  define: {
    '__APP_UPDATE_TIMESTAMP__': JSON.stringify(new Date().toLocaleString()),
    '__APP_VERSION__': JSON.stringify(process.env.npm_package_version),
  },
})