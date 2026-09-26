import { chromium } from 'playwright-core';

async function main() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('Logging in as super admin...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForURL('**/garages', { timeout: 15000 });
    console.log('Logged in to:', page.url());

    const demoRow = page.locator('tr').filter({ hasText: 'Demo Auto Shop' });
    if (await demoRow.count() > 0) {
        console.log('Demo Auto Shop row found!');
        await demoRow.locator('button:has-text("Owners")').click();
        await page.waitForTimeout(1000);

        const modal = page.locator('div:has(> h3:has-text("Manage Owners"))');
        const modalText = await modal.innerText();
        console.log('Owners modal content:\n', modalText);

        if (modalText.includes('demoowner@cubestack.in')) {
            console.log('demoowner@cubestack.in already exists! Resetting password to 8^Ek=JZ0@|rN...');
            const ownerRow = modal.locator('div').filter({ hasText: 'demoowner@cubestack.in' }).first();
            await ownerRow.locator('button:has-text("Reset Password")').click();
            await page.waitForTimeout(500);
            await ownerRow.locator('input[placeholder="Enter new password..."]').fill('8^Ek=JZ0@|rN');
            await ownerRow.locator('button:has-text("Save")').click();
            await page.waitForTimeout(2000);
            console.log('Reset password submitted.');
        } else {
            console.log('Adding demoowner@cubestack.in as owner to Demo Auto Shop...');
            const form = modal.locator('form');
            await form.locator('input[type="text"]').fill('Demo Owner');
            await form.locator('input[type="email"]').fill('demoowner@cubestack.in');
            await form.locator('input[type="tel"]').fill('9876543210');
            await form.locator('input[type="password"]').fill('8^Ek=JZ0@|rN');
            await form.locator('button[type="submit"]').click();
            await page.waitForTimeout(3000);
            console.log('Owner added!');
        }
    }

    await browser.close();
}

main().catch(console.error);
