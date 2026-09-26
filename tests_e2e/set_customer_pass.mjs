import { chromium } from 'playwright-core';

async function setCustomerPassword() {
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

    console.log('2. Navigating to /customers to find Customer One ID...');
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    const customerData = await page.evaluate(() => {
        const app = document.getElementById('app');
        const pageData = JSON.parse(app.dataset.page);
        const customers = pageData.props.customers || [];
        const c1 = customers.find(c => c.user && c.user.email === 'customer1@cubestack.in');
        return c1 ? { id: c1.id, name: c1.user.name, email: c1.user.email, phone: c1.user.phone } : null;
    });

    console.log('Customer One Data:', customerData);

    if (customerData) {
        console.log(`3. Sending POST /customers/${customerData.id}/reset-password with custom password...`);
        const res = await page.evaluate(async (id) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/customers/${id}/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json, text/plain, */*',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
                },
                body: JSON.stringify({ password: '8^Ek=JZ0@|rN' })
            });
            return { status: resp.status, redirected: resp.redirected, url: resp.url };
        }, customerData.id);
        console.log('Reset Password API response:', res);
    }

    console.log('4. Now testing login as customer1@cubestack.in with 8^Ek=JZ0@|rN...');
    const custContext = await browser.newContext();
    const custPage = await custContext.newPage();

    await custPage.goto('https://piston.cubestack.in/login');
    await custPage.locator('#email').fill('customer1@cubestack.in');
    await custPage.locator('#password').fill('8^Ek=JZ0@|rN');
    await custPage.locator('button:has-text("Log in")').click();
    await custPage.waitForTimeout(4000);

    console.log('Customer login URL:', custPage.url());
    const custBody = await custPage.innerText('body');
    console.log('Customer page snippet:\n', custBody.substring(0, 500));

    await browser.close();
}

setCustomerPassword().catch(console.error);
