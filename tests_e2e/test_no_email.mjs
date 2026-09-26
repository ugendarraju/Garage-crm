import { chromium } from 'playwright-core';

async function testAddNoEmail() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    page.on('response', resp => {
        if (resp.url().includes('customers')) {
            console.log('Customer request:', resp.request().method(), resp.url(), resp.status());
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

    await page.locator('input[placeholder="e.g. Rahul Sharma"]').fill('Test Customer NoEmail');
    await page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first().fill('9876500099');

    const submitBtn = page.locator('button:has-text("Save Customer")');
    await submitBtn.click();

    await page.waitForTimeout(4000);

    const bodyText = await page.innerText('body');
    const match = bodyText.match(/(PC#[0-9]+)/);
    if (match) {
        console.log('SUCCESS WITH NO EMAIL! Temp password:', match[1]);
    } else {
        console.log('Snippet of body:', bodyText.substring(0, 600));
    }

    await browser.close();
}

testAddNoEmail().catch(console.error);
