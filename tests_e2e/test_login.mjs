import { chromium } from 'playwright-core';

async function testLogin() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('response', response => {
        if (response.url().includes('/login')) {
            console.log('Login response status:', response.status());
        }
    });

    console.log('Navigating to login...');
    await page.goto('https://piston.cubestack.in/login');
    
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    
    console.log('Clicking Log in button...');
    await page.locator('button:has-text("Log in")').click();
    
    await page.waitForTimeout(4000);
    console.log('Current URL after submit:', page.url());
    
    const bodyText = await page.innerText('body');
    console.log('Body snippet:\n', bodyText.substring(0, 500));
    
    await browser.close();
}

testLogin().catch(console.error);
