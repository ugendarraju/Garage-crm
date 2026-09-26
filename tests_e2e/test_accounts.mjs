import { chromium } from 'playwright-core';

async function testAccounts() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    const tryLogin = async (email, pass) => {
        await page.goto('https://piston.cubestack.in/login');
        await page.locator('#email').fill(email);
        await page.locator('#password').fill(pass);
        await page.locator('button:has-text("Log in")').click();
        await page.waitForTimeout(3000);
        console.log(email, '-> URL:', page.url());
        const hasErr = await page.locator('text=These credentials do not match').count();
        console.log(email, 'has error:', hasErr > 0);
        if (page.url() !== 'https://piston.cubestack.in/login') {
            console.log('SUCCESSFUL LOGIN FOR', email);
            return true;
        }
        return false;
    };

    await tryLogin('admin@garage.com', 'password');
    await tryLogin('owner@demoauto.com', 'password');
    await tryLogin('admin@garage.com', '8^Ek=JZ0@|rN');
    await tryLogin('demoowner@cubestack.in', 'password');

    await browser.close();
}

testAccounts().catch(console.error);
