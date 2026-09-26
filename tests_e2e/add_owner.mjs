import { chromium } from 'playwright-core';

async function main() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('Logging in as super admin...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForURL('**/garages', { timeout: 15000 });
    console.log('On garages page.');

    const demoRow = page.locator('tr').filter({ hasText: 'Demo Auto Shop' });
    await demoRow.locator('button:has-text("Owners")').click();
    await page.waitForTimeout(1500);

    // Let's inspect all owners listed
    const modal = page.locator('div').filter({ hasText: 'Manage Owners: Demo Auto Shop' }).last();
    console.log('Current modal text:\n', await modal.innerText());

    // Fill form
    const form = modal.locator('form');
    await form.locator('input[type="text"]').first().fill('Demo Garage Owner');
    await form.locator('input[type="email"]').fill('demoowner@cubestack.in');
    await form.locator('input[type="tel"]').fill('9876543210');
    await form.locator('input[type="password"]').fill('8^Ek=JZ0@|rN');

    console.log('Form inputs filled. Submitting...');
    const submitBtn = form.locator('button[type="submit"]');
    
    // Listen for responses
    page.on('response', resp => {
        if (resp.url().includes('garage-owners') || resp.url().includes('owners')) {
            console.log('Response from server:', resp.url(), resp.status());
        }
    });

    await submitBtn.click();
    await page.waitForTimeout(4000);

    console.log('After submit modal text:\n', await modal.innerText());

    await browser.close();
}

main().catch(console.error);
