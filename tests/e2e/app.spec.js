import { expect, test } from '@playwright/test';

const credentials = {
    owner: { username: 'owner', password: 'password' },
    manager: { username: 'manager', password: 'password' },
    staff: { username: 'staff', password: 'password' },
};

async function signIn(page, role) {
    await page.goto('/login');
    await page.getByLabel('Username').fill(credentials[role].username);
    await page.getByRole('textbox', { name: 'Password' }).fill(credentials[role].password);
    await page.getByRole('button', { name: /^Sign in/ }).click();
    await expect(page).toHaveURL(/\/(?:dashboard)?$/);
}

test('sign-in is production-ready on desktop', async ({ page }) => {
    await page.goto('/login');

    await expect(page.getByRole('heading', { name: 'Welcome back' })).toBeVisible();
    await expect(page.getByLabel('Username')).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Password' })).toBeVisible();
    await expect(page.getByText('Quick Demo Access')).toHaveCount(0);
    await expect(page.locator('aside[aria-label="Business Manager editorial background"]')).toBeVisible();
});

test.describe('mobile sign-in and inventory', () => {
    test.use({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true,
    });

    test('keeps login focused and hides the desktop editorial panel', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByRole('heading', { name: 'Welcome back' })).toBeVisible();
        await expect(page.locator('aside[aria-label="Business Manager editorial background"]')).toBeHidden();
        await expect(page.getByRole('button', { name: /^Sign in/ })).toHaveCSS('min-height', '52px');
    });

    test('opens and dismisses the manager restock bottom sheet', async ({ page }) => {
        await signIn(page, 'manager');
        await page.goto('/inventory');

        await page.getByRole('button', { name: /Restock/ }).first().click();
        const sheet = page.locator('.app-modal-sheet').last();
        await expect(sheet).toBeVisible();
        await expect(sheet).toContainText('Restock Product');

        const bounds = await sheet.boundingBox();
        expect(bounds?.x).toBe(0);
        expect(bounds?.width).toBe(390);

        const backdrop = page.locator('div[wire\\:click\\.self="resetModal"]');
        await expect(backdrop).toBeVisible();
        await backdrop.click({ position: { x: 12, y: 12 } });
        await expect(sheet).toBeHidden();
    });
});

test('owner can reach every production application area', async ({ page }) => {
    await signIn(page, 'owner');

    const routes = [
        '/',
        '/products',
        '/inventory',
        '/orders',
        '/finance',
        '/reports',
        '/activity-logs',
        '/promotion-activities',
        '/announcements',
        '/users',
        '/settings',
    ];

    for (const route of routes) {
        const response = await page.goto(route);
        expect(response?.status(), `Expected ${route} to load for the owner`).toBe(200);
        await expect(page.locator('body')).not.toContainText('Server Error');
    }
});

test('owner can send an image announcement and staff can read it in a modal', async ({ browser }) => {
    const ownerContext = await browser.newContext();
    const ownerPage = await ownerContext.newPage();
    await signIn(ownerPage, 'owner');
    await ownerPage.goto('/announcements');

    await ownerPage.getByRole('button', { name: /New announcement/ }).click();
    await ownerPage.locator('input[wire\\:model="title"]').fill('New lookbook');
    await ownerPage.locator('textarea[wire\\:model="message"]').fill('Please review the new ALAS clothing designs before your next shift.');
    await ownerPage.locator('select[wire\\:model="target_role"]').selectOption('staff');
    await ownerPage.locator('input[wire\\:model="image"]').setInputFiles('public/images/alas-logo.png');
    await ownerPage.getByRole('button', { name: 'Send announcement' }).click();
    await expect(ownerPage.getByText('Announcement sent to the selected recipients.')).toBeVisible();

    const staffContext = await browser.newContext();
    const staffPage = await staffContext.newPage();
    await signIn(staffPage, 'staff');
    await staffPage.getByRole('button', { name: 'Open notifications' }).click();
    await staffPage.getByRole('banner').getByText('New lookbook', { exact: true }).click();

    const reader = staffPage.getByRole('dialog', { name: 'Announcement' });
    await expect(reader).toBeVisible();
    await expect(reader).toContainText('Please review the new ALAS clothing designs');
    await expect(reader.getByRole('img', { name: 'New lookbook' })).toBeVisible();

    await ownerContext.close();
    await staffContext.close();
});

test('manager can manage stock but cannot access owner-only administration', async ({ page }) => {
    await signIn(page, 'manager');
    await page.goto('/inventory');
    await expect(page.getByText('Inventory Control')).toBeVisible();

    const usersResponse = await page.goto('/users');
    expect(usersResponse?.status()).toBe(403);
    await expect(page.getByRole('heading', { name: 'Access Denied' })).toBeVisible();

    const settingsResponse = await page.goto('/settings');
    expect(settingsResponse?.status()).toBe(403);
});

test('staff sees personal work only and protected pages remain inaccessible', async ({ page }) => {
    await signIn(page, 'staff');

    await page.goto('/activity-logs');
    await expect(page.getByRole('heading', { name: 'My Activity History' })).toBeVisible();
    await expect(page.getByRole('option', { name: /Owner|Manager/i })).toHaveCount(0);

    await page.goto('/finance');
    await expect(page.getByText('Finance ledger')).toBeVisible();
    await expect(page.getByText('Some balances and reports have limited access.')).toBeVisible();

    const reportsResponse = await page.goto('/reports');
    expect(reportsResponse?.status()).toBe(403);
    const usersResponse = await page.goto('/users');
    expect(usersResponse?.status()).toBe(403);
});

test('staff order submission enters the approval workflow and manager can approve it', async ({ browser }) => {
    const staffContext = await browser.newContext();
    const staffPage = await staffContext.newPage();
    const customerName = `E2E customer ${Date.now()}`;

    await signIn(staffPage, 'staff');
    await staffPage.goto('/orders/create');
    await staffPage.getByRole('button', { name: /Add Line/ }).click();
    await expect(staffPage.getByText('1 Item(s)')).toBeVisible();
    await staffPage.locator('input[wire\\:model="customer_name"]').fill(customerName);
    await staffPage.locator('textarea[wire\\:model="shipping_address"]').fill('100 E2E Test Street, Manila');
    await staffPage.getByRole('button', { name: 'Save & Create Order' }).click();
    await expect(staffPage).toHaveURL(/\/orders$/);
    const staffOrderTable = staffPage.getByRole('table');
    await expect(staffOrderTable.getByText(customerName)).toBeVisible();
    await expect(staffOrderTable.locator('tr', { hasText: customerName }).getByText('Pending Approval')).toBeVisible();

    const managerContext = await browser.newContext();
    const managerPage = await managerContext.newPage();
    await signIn(managerPage, 'manager');
    await managerPage.goto('/orders');

    const orderRow = managerPage.locator('tr', { hasText: customerName });
    await expect(orderRow).toBeVisible();
    await orderRow.getByRole('button', { name: /Approve/ }).click();
    await expect(orderRow.getByText('Approved')).toBeVisible();

    await staffContext.close();
    await managerContext.close();
});
