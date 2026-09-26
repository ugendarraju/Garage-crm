import { chromium } from 'playwright-core';

async function createCustomer1() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();

    console.log('1. Logging in as Demo Owner...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    console.log('2. Navigating to /customers...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    const bodyText = await page.innerText('body');
    if (bodyText.includes('customer1@cubestack.in')) {
        console.log('customer1@cubestack.in already exists!');
    } else {
        console.log('3. Clicking Add Customer...');
        await page.locator('button:has-text("Add Customer")').click();
        await page.waitForTimeout(1000);

        await page.locator('input[placeholder="e.g. Rahul Sharma"]').fill('Customer One');
        await page.locator('input[placeholder="e.g. 9876543210 or +919876543210"]').first().fill('9876500001');
        await page.locator('input[placeholder="customer@example.com"]').fill('customer1@cubestack.in');

        // Add a vehicle for customer1
        await page.locator('button:has-text("+ Add Vehicle")').click();
        await page.waitForTimeout(500);

        await page.locator('input.uppercase').fill('KA01AB1234');
        // Select brand
        const brandSelect = page.locator('select').filter({ hasText: 'Select Brand' });
        await brandSelect.selectOption({ label: 'Honda' });
        await page.waitForTimeout(500);
        // Select model
        const modelSelect = page.locator('select').filter({ hasText: 'Select Model' });
        await modelSelect.selectOption({ label: 'Activa 6G' });

        console.log('4. Clicking Save Customer...');
        await page.locator('button:has-text("Save Customer")').click();
        await page.waitForTimeout(4000);

        console.log('5. Customer save completed!');
    }

    // Now let's extract credentials modal if open
    const allBody = await page.innerText('body');
    const match = allBody.match(/(PC#[0-9]+)/);
    if (match) {
        console.log('TEMPORARY PASSWORD FOR customer1:', match[1]);
    } else {
        console.log('No temp pass matched, checking credentials modal:');
    }

    await browser.close();
}

createCustomer1().catch(console.error);
