import { chromium } from 'playwright-core';

async function checkLogs() {
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

    const logsText = await page.innerText('body');
    console.log('Action logs:\n', logsText.substring(0, 1500));

    await browser.close();
}

checkLogs().catch(console.error);
