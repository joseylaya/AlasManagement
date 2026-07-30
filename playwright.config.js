import { defineConfig, devices } from '@playwright/test';

const e2eDatabase = new URL('./database/e2e.sqlite', import.meta.url).pathname;
const e2eEnv = {
    ...process.env,
    APP_ENV: 'e2e',
    APP_DEBUG: 'true',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: e2eDatabase,
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
};

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    timeout: 45_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://127.0.0.1:8091',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8091',
        url: 'http://127.0.0.1:8091/login',
        reuseExistingServer: false,
        timeout: 30_000,
        env: e2eEnv,
    },
});
