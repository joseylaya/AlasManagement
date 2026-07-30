import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';

const databasePath = new URL('../../database/e2e.sqlite', import.meta.url).pathname;

export default function globalSetup() {
    // This is a dedicated test database. Production and local development data are never touched.
    const env = {
        ...process.env,
        APP_ENV: 'e2e',
        APP_DEBUG: 'true',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: databasePath,
        CACHE_STORE: 'array',
        QUEUE_CONNECTION: 'sync',
        SESSION_DRIVER: 'file',
    };

    // E2E must not inherit a production config cache, which would otherwise ignore DB_DATABASE.
    execFileSync('php', ['artisan', 'config:clear'], {
        cwd: new URL('../..', import.meta.url).pathname,
        env,
        stdio: 'inherit',
    });

    writeFileSync(databasePath, '');

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        cwd: new URL('../..', import.meta.url).pathname,
        env,
        stdio: 'inherit',
    });
}
