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
    console.log('On garages page.');

    const demoRow = page.locator('tr').filter({ hasText: 'Demo Auto Shop' });
    await demoRow.locator('button:has-text("Owners")').click();
    await page.waitForTimeout(1500);

    const modalHTML = await page.locator('.fixed.inset-0.z-50').innerHTML();
    console.log('Modal innerHTML:\n', modalHTML);

    await browser.close();
}

main().catch(console.error);
