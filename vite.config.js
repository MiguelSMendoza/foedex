import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'public/app',
    emptyOutDir: true,
    sourcemap: false,
    cssCodeSplit: false,
    assetsDir: '',
    rollupOptions: {
      input: 'frontend/src/main.jsx',
      output: {
        entryFileNames: 'app.js',
        chunkFileNames: 'chunks/[name].js',
        assetFileNames: (assetInfo) => assetInfo.name?.endsWith('.css') ? 'app.css' : 'assets/[name][extname]'
      }
    }
  }
});
