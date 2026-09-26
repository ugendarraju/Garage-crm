import { chromium } from 'playwright-core';

async function main() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard');

    const authData = await page.evaluate(() => {
        const app = document.getElementById('app');
        return JSON.parse(app.dataset.page);
    });
    console.log('AUTH USER:', JSON.stringify(authData.props.auth, null, 2));
    console.log('GARAGE PROPS:', JSON.stringify(authData.props.garage, null, 2));
    await browser.close();
}

main().catch(console.error);
