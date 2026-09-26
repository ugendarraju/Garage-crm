import { chromium } from 'playwright-core';

async function check500Error() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    page.on('response', async resp => {
        if (resp.status() >= 400) {
            console.log('ERROR RESPONSE:', resp.status(), resp.url());
            try {
                const text = await resp.text();
                console.log('ERROR BODY:\n', text.substring(0, 2000));
            } catch (e) {
                console.log('Could not get response body:', e.message);
            }
        }
    });

    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    await page.locator('button:has-text("Add Customer")').click();
    await page.waitForTimeout(1000);

    await page.locator('input[placeholder="e.g. Rahul Sharma"]').fill('Customer One');
    await page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first().fill('9876500001');
    await page.locator('input[placeholder="customer@example.com"]').fill('customer1@cubestack.in');

    const submitBtn = page.locator('button:has-text("Save Customer")');
    await submitBtn.click();

    await page.waitForTimeout(5000);

    await browser.close();
}

check500Error().catch(console.error);
