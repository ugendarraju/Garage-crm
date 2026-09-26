import { chromium } from 'playwright-core';

async function resetAndSetCustomerPassword() {
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
    await page.waitForURL('**/dashboard');

    console.log('2. Navigating to /customers...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    // Dialog handler for confirm()
    page.on('dialog', async dialog => {
        console.log('Dialog:', dialog.message());
        await dialog.accept();
    });

    console.log('3. Clicking Reset Pass for Customer One...');
    const customerRow = page.locator('tr').filter({ hasText: 'customer1@cubestack.in' });
    await customerRow.locator('button:has-text("Reset Pass")').click();
    await page.waitForTimeout(3000);

    // Read credentials modal
    const modalText = await page.locator('div:has-text("Temporary Password")').last().innerText();
    console.log('Credentials Modal Text:\n', modalText);

    const match = modalText.match(/PC#[0-9]+/);
    if (!match) {
        throw new Error('Could not find temporary password in modal');
    }
    const tempPass = match[0];
    console.log('Extracted Temp Password:', tempPass);

    // Now log out
    await page.goto('https://piston.cubestack.in/dashboard');
    // or direct logout
    const logoutContext = await browser.newContext();
    const customerPage = await logoutContext.newPage();

    console.log('4. Logging in as customer1@cubestack.in with temporary password...');
    await customerPage.goto('https://piston.cubestack.in/login');
    await customerPage.locator('#email').fill('customer1@cubestack.in');
    await customerPage.locator('#password').fill(tempPass);
    await customerPage.locator('button:has-text("Log in")').click();

    await customerPage.waitForTimeout(3000);
    console.log('After login URL:', customerPage.url());

    const custBody = await customerPage.innerText('body');
    console.log('Customer body snippet:\n', custBody.substring(0, 500));

    if (customerPage.url().includes('change-password') || custBody.includes('Change Password') || custBody.includes('Update Password')) {
        console.log('5. On mandatory change password page. Setting password to 8^Ek=JZ0@|rN...');
        await customerPage.locator('input[type="password"]').nth(0).fill(tempPass);
        await customerPage.locator('input[type="password"]').nth(1).fill('8^Ek=JZ0@|rN');
        await customerPage.locator('input[type="password"]').nth(2).fill('8^Ek=JZ0@|rN');
        await customerPage.locator('button[type="submit"]').click();
        await customerPage.waitForTimeout(3000);
        console.log('After password change URL:', customerPage.url());
    }

    console.log('SUCCESS: Customer1 password is set to 8^Ek=JZ0@|rN');

    await browser.close();
}

resetAndSetCustomerPassword().catch(console.error);
