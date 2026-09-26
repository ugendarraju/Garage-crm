import { chromium } from 'playwright-core';

async function checkCustomerLogs() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/garages');

    await page.goto('https://piston.cubestack.in/action-logs?module=customers');
    await page.waitForTimeout(2000);

    const logs = await page.locator('tbody tr').allInnerTexts();
    console.log('Customer logs:\n', logs);

    await browser.close();
}

checkCustomerLogs().catch(console.error);
