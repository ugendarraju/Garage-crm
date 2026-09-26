import { chromium } from 'playwright-core';
import fs from 'fs';
import path from 'path';

async function downloadBackup() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();

    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/garages');

    await page.goto('https://piston.cubestack.in/admin/backups');
    await page.waitForTimeout(2000);

    // Click download on backup-demo
    const [ download ] = await Promise.all([
        page.waitForEvent('download'),
        page.locator('a[href*="download"], button:has-text("Download")').first().click()
    ]);

    const savePath = 'tests_e2e/downloaded_backup.zip';
    await download.saveAs(savePath);
    console.log('Downloaded backup to:', savePath);

    await browser.close();
}

downloadBackup().catch(console.error);
