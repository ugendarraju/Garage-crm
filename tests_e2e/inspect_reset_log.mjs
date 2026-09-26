import { chromium } from 'playwright-core';

async function inspectResetLog() {
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

    const resetRow = page.locator('tr').filter({ hasText: "Reset password for customer 'Customer One'" });
    await resetRow.locator('button:has-text("Inspect")').click();
    await page.waitForTimeout(1000);

    const modalText = await page.locator('.fixed.inset-0.z-50').innerText();
    console.log('Inspect Modal:\n', modalText);

    await browser.close();
}

inspectResetLog().catch(console.error);
