import { chromium } from 'playwright-core';

async function testBackups() {
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

    await page.goto('https://piston.cubestack.in/admin/backups');
    await page.waitForTimeout(2000);

    const bodyText = await page.innerText('body');
    console.log('Backups page content:\n', bodyText.substring(0, 1000));

    await browser.close();
}

testBackups().catch(console.error);
