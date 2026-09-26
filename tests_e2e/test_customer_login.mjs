import { chromium } from 'playwright-core';

async function testCustomerLogin() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('Navigating to login...');
    await page.goto('https://piston.cubestack.in/login');

    await page.locator('#email').fill('customer1@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForTimeout(4000);
    console.log('Current URL after submit:', page.url());

    const bodyText = await page.innerText('body');
    console.log('Customer page snippet:\n', bodyText.substring(0, 500));

    await browser.close();
}

testCustomerLogin().catch(console.error);
