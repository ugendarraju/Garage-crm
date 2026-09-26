import { chromium } from 'playwright-core';

async function triggerBackup() {
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

    // Click "🏢 Backup Single Garage"
    await page.locator('button:has-text("Backup Single Garage")').click();
    await page.waitForTimeout(1000);

    // Select Demo Auto Shop and submit
    await page.locator('select').first().selectOption({ label: 'Demo Auto Shop' });
    await page.locator('input[placeholder*="label"]').fill('test-demo-live');
    await page.locator('button:has-text("Generate Backup")').click();
    await page.waitForTimeout(5000);

    const bodyText = await page.innerText('body');
    console.log('Result:\n', bodyText.substring(0, 800));

    await browser.close();
}

triggerBackup().catch(console.error);
