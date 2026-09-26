import { chromium } from 'playwright-core';

async function main() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('1. Logging in as super admin...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForURL('**/garages', { timeout: 15000 });
    console.log('2. On garages page. Opening owners modal...');

    const demoRow = page.locator('tr').filter({ hasText: 'Demo Auto Shop' });
    await demoRow.locator('button:has-text("Owners")').click();
    await page.waitForTimeout(1000);

    const modal = page.locator('.fixed.inset-0.z-50');
    const form = modal.locator('form');

    console.log('3. Filling owner form...');
    await form.locator('input[type="text"]').fill('Demo Owner');
    await form.locator('input[type="email"]').fill('demoowner@cubestack.in');
    await form.locator('input[type="tel"]').fill('9876543210');
    await form.locator('input[type="password"]').fill('8^Ek=JZ0@|rN');

    console.log('4. Clicking Add button...');
    await form.locator('button[type="submit"]').click();
    await page.waitForTimeout(3000);

    const updatedText = await modal.innerText();
    console.log('Modal text after submit:\n', updatedText);

    if (updatedText.includes('demoowner@cubestack.in')) {
        console.log('SUCCESS: demoowner@cubestack.in was added!');
    } else {
        console.log('Checking for errors...');
        const errText = await form.locator('.text-red-500').allInnerTexts();
        console.log('Validation errors:', errText);
    }

    await browser.close();
}

main().catch(console.error);
