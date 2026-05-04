import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';

dotenv.config();

export default defineConfig({
    testDir: './tests/playwright',
    timeout: 30_000,
    retries: process.env.CI ? 1 : 0,
    workers: 1,

    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    reporter: [
        ['list'],
        ['html', { outputFolder: 'tests/playwright/report', open: 'never' }],
    ],
});
