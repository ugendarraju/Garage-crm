import { chromium } from 'playwright-core';

async function checkAllLogs() {
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

    await page.goto('https://piston.cubestack.in/action-logs');
    await page.waitForTimeout(2000);

    const logs = await page.locator('tbody tr').allInnerTexts();
    console.log('All action logs (count: ' + logs.length + '):\n');
    logs.forEach(l => console.log('---', l.replace(/\n+/g, ' ')));

    await browser.close();
}

checkAllLogs().catch(console.error);
