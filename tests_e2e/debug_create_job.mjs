import { chromium } from 'playwright-core';

async function testCreateJobCard() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();
    
    // Log console messages & errors
    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard');

    await page.goto('https://piston.cubestack.in/job-cards/create');
    await page.waitForTimeout(2000);

    // Get available options in customer and vehicle dropdowns
    const custOptions = await page.locator('select').first().locator('option').allInnerTexts();
    console.log('Customer options:', custOptions);

    // Select customer
    await page.locator('select').first().selectOption({ index: 1 });
    await page.waitForTimeout(1000);

    const vehOptions = await page.locator('select').nth(1).locator('option').allInnerTexts();
    console.log('Vehicle options:', vehOptions);

    if (vehOptions.length > 1) {
        await page.locator('select').nth(1).selectOption({ index: 1 });
    }

    // Check status options
    const statusSelect = page.locator('select:has(option[value="scheduled"])');
    if (await statusSelect.count() > 0) {
        await statusSelect.selectOption('scheduled');
        console.log('Selected status: scheduled (Estimate)');
    }

    // Fill service item
    const serviceName = page.locator('input[placeholder*="Oil change" i], input[placeholder*="Service" i]').first();
    if (await serviceName.count() > 0) {
        await serviceName.fill('Complete Engine Tuneup & Oil Change');
    }

    const serviceRate = page.locator('input[type="number"][placeholder*="500" i], input[placeholder*="Rate" i]').first();
    if (await serviceRate.count() > 0) {
        await serviceRate.fill('1200');
    }

    // Submit
    console.log('Submitting form...');
    const createBtn = page.locator('button[type="submit"]:has-text("Create Job Card")');
    await createBtn.click();
    await page.waitForTimeout(4000);

    console.log('Current URL after submit:', page.url());

    // Check if any error messages are visible
    const errors = await page.locator('.text-red-500, .text-red-600').allInnerTexts();
    if (errors.length > 0) {
        console.log('Validation errors on page:', errors);
    }

    await browser.close();
}

testCreateJobCard();
