import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig({
  plugins: [
    symfonyPlugin(),
  ],
  build: {
    rollupOptions: {
      input: {
        app: './frontend/src/main.jsx',
      },
    },
  },
});
