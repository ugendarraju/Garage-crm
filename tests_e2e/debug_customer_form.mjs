import { chromium } from 'playwright-core';

async function setupCustomer() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('response', resp => {
        if (resp.url().includes('customers')) {
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

    const nameInput = page.locator('input[placeholder="e.g. Rahul Sharma"]');
    const phoneInput = page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first();
    const emailInput = page.locator('input[placeholder="customer@example.com"]');

    await nameInput.fill('Customer One');
    await phoneInput.fill('9876500001');
    await emailInput.fill('customer1@cubestack.in');

    console.log('Inputs filled. Submitting form by clicking Save Customer...');
    const submitBtn = page.locator('button:has-text("Save Customer")');
    await submitBtn.click();

    await page.waitForTimeout(4000);

    const bodyText = await page.innerText('body');
    const match = bodyText.match(/(PC#[0-9]+)/);
    if (match) {
        console.log('FOUND TEMP PASSWORD:', match[1]);
    } else {
        console.log('Snippet of body:', bodyText.substring(0, 1000));
    }

    await browser.close();
}

setupCustomer().catch(console.error);
