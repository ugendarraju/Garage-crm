import { chromium } from 'playwright-core';

async function testSuperAdminAddCustomer() {
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

    console.log('1. Login as Super Admin...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('admin@garage.com');
    await page.locator('#password').fill('password');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/garages');

    console.log('2. Manage Demo Auto Shop...');
    const demoRow = page.locator('tr').filter({ hasText: 'Demo Auto Shop' });
    await demoRow.locator('button:has-text("Manage Garage")').click();
    await page.waitForURL('**/dashboard');
    console.log('Now managing Demo Auto Shop! URL:', page.url());

    console.log('3. Navigating to /customers...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    console.log('4. Clicking Add Customer...');
    await page.locator('button:has-text("Add Customer")').click();
    await page.waitForTimeout(1000);

    await page.locator('input[placeholder="e.g. Rahul Sharma"]').fill('Customer One');
    await page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first().fill('9876500001');
    await page.locator('input[placeholder="customer@example.com"]').fill('customer1@cubestack.in');

    console.log('5. Clicking Save Customer...');
    await page.locator('button:has-text("Save Customer")').click();
    await page.waitForTimeout(4000);

    const bodyText = await page.innerText('body');
    const match = bodyText.match(/(PC#[0-9]+)/);
    if (match) {
        console.log('SUCCESS! Found temp password:', match[1]);
    } else {
        console.log('Snippet of body:', bodyText.substring(0, 600));
    }

    await browser.close();
}

testSuperAdminAddCustomer().catch(console.error);
