import { chromium } from 'playwright-core';

async function verifyInvoice() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage();
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();
    await page.waitForURL('**/dashboard');

    await page.goto('https://piston.cubestack.in/job-cards/1/invoice');
    await page.waitForTimeout(2000);

    const title = await page.locator('h1').innerText();
    const billedTo = await page.locator('p.text-xl').innerText();
    const invoiceNo = await page.locator('td.font-bold.text-right').first().innerText();
    const printBtn = await page.locator('button:has-text("Print Invoice")').isVisible();

    console.log('Invoice Header:', title);
    console.log('Billed To:', billedTo);
    console.log('Invoice No:', invoiceNo);
    console.log('Print Button Visible:', printBtn);

    await browser.close();
}

verifyInvoice();
