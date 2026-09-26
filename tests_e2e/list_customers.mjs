import { chromium } from 'playwright-core';

async function listCustomers() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForTimeout(3000);
    await page.goto('https://piston.cubestack.in/customers');
    await page.waitForTimeout(2000);

    const c1 = await page.evaluate(() => {
        const data = JSON.parse(document.getElementById('app').dataset.page);
        const cust = data.props.customers.find(c => c.user && c.user.email === 'customer1@cubestack.in');
        return cust ? { id: cust.id, user_id: cust.user_id, phone: cust.user.phone } : null;
    });
    console.log('Customer1 record:', c1);

    if (c1) {
        // Submit reset password via Inertia / fetch
        const res = await page.evaluate(async (id) => {
            const resp = await fetch(`/customers/${id}/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
                },
                body: JSON.stringify({ password: '8^Ek=JZ0@|rN' })
            });
            return { status: resp.status };
        }, c1.id);
        console.log('Reset response:', res);
    }

    await browser.close();
}

listCustomers().catch(console.error);
