import { chromium } from 'playwright-core';

async function setupCustomer() {
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
    console.log('Logged in as owner.');

    console.log('2. Navigating to /customers...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    const bodyText = await page.innerText('body');
    let tempPass = null;

    if (bodyText.includes('customer1@cubestack.in')) {
        console.log('customer1@cubestack.in already exists! Resetting password...');
        // Find row for customer1
        const customerRow = page.locator('tr').filter({ hasText: 'customer1@cubestack.in' });
        
        // Setup dialog handler for confirm
        page.on('dialog', async dialog => {
            console.log('Dialog:', dialog.message());
            await dialog.accept();
        });

        await customerRow.locator('button:has-text("Reset Pass")').click();
        await page.waitForTimeout(3000);

        // Check credentials modal
        const credModal = page.locator('div:has-text("Login Credentials")').last();
        if (await credModal.count() > 0) {
            const modalText = await credModal.innerText();
            console.log('Reset Credentials Modal:\n', modalText);
            const match = modalText.match(/Temporary Password:\s*([A-Za-z0-9#@!]+)/);
            if (match) {
                tempPass = match[1];
                console.log('Extracted temp password:', tempPass);
            }
        }
    } else {
        console.log('Adding new customer: customer1@cubestack.in...');
        await page.locator('button:has-text("Add Customer")').click();
        await page.waitForTimeout(1000);

        // Fill customer form
        await page.locator('input#name, input[placeholder="Customer Name"]').or(page.locator('form input[type="text"]').first()).fill('Customer One');
        
        // Find phone, email inputs
        const phoneInput = page.locator('input[type="tel"]').or(page.locator('input[placeholder*="Phone"], input[name="phone"]')).first();
        if (await phoneInput.count() > 0) {
            await phoneInput.fill('9876500001');
        } else {
            // Find inputs in modal
            const inputs = page.locator('.fixed.inset-0 form input');
            await inputs.nth(0).fill('Customer One');
            await inputs.nth(1).fill('9876500001');
            await inputs.nth(2).fill('customer1@cubestack.in');
        }

        const emailInput = page.locator('input[type="email"]').last();
        await emailInput.fill('customer1@cubestack.in');

        console.log('Submitting customer form...');
        await page.locator('form button[type="submit"], button:has-text("Save Customer"), button:has-text("Create Customer")').first().click();
        await page.waitForTimeout(4000);

        // Check if credentials modal popped up
        const pageText = await page.innerText('body');
        console.log('Page snippet after submit:\n', pageText.substring(0, 1000));
        const match = pageText.match(/PC#[0-9]+/);
        if (match) {
            tempPass = match[0];
            console.log('Found temporary password:', tempPass);
        }
    }

    if (!tempPass) {
        console.log('Checking all modals or text for temp password...');
        const allText = await page.innerText('body');
        const match = allText.match(/(PC#[0-9]+)/);
        if (match) {
            tempPass = match[1];
            console.log('Extracted tempPass:', tempPass);
        }
    }

    await browser.close();
    return tempPass;
}

setupCustomer().catch(console.error);
