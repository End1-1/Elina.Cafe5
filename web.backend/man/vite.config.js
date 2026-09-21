import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => ({
  // App is served at https://elina.local/man/
  base: mode === 'development' ? '/' : '/man/',
  plugins: [vue()],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/engine/v2': {
        target: 'http://elina.local',
        changeOrigin: true,
        secure: false
      }
    }
  }
}))
