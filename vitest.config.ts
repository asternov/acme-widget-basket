import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['resources/js/**/*.test.{ts,tsx}'],
        setupFiles: ['resources/js/test-setup.ts'],
    },
});
