import { chromium } from 'playwright-core';

async function main() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('Logging in as demoowner@cubestack.in...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForURL('**/dashboard', { timeout: 15000 });
    console.log('Logged in! Navigating to /customers...');

    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    const bodyText = await page.innerText('body');
    console.log('Customers page content:\n', bodyText.substring(0, 1000));

    await browser.close();
}

main().catch(console.error);
