import { chromium } from 'playwright-core';

async function setupCustomer() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    page.on('response', resp => {
        if (resp.url().includes('/customers')) {
            console.log('Customer request:', resp.request().method(), resp.url(), resp.status());
        }
    });

    console.log('1. Logging in as Demo Owner...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    console.log('2. Navigating to /customers...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    console.log('3. Clicking Add Customer...');
    await page.locator('button:has-text("Add Customer")').click();
    await page.waitForTimeout(1000);

    console.log('4. Filling form fields...');
    await page.locator('input[placeholder="e.g. Rahul Sharma"]').fill('Customer One');
    await page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first().fill('9876543211');
    await page.locator('input[placeholder="customer@example.com"]').fill('customer1@cubestack.in');

    console.log('5. Clicking Save Customer...');
    await page.locator('button:has-text("Save Customer")').click();

    await page.waitForTimeout(4000);

    const bodyText = await page.innerText('body');
    console.log('Body after save:\n', bodyText.substring(0, 1000));

    // Check credentials modal
    const modalText = await page.locator('div:has-text("Login Credentials")').allInnerTexts();
    console.log('Credentials modal:\n', modalText);

    await browser.close();
}

setupCustomer().catch(console.error);
