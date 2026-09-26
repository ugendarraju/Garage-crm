import { chromium } from 'playwright-core';

async function checkJobCards() {
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

    await page.goto('https://piston.cubestack.in/job-cards');
    await page.waitForTimeout(2000);

    const data = await page.evaluate(() => {
        const el = document.getElementById('app');
        if (!el || !el.dataset.page) return null;
        const pageObj = JSON.parse(el.dataset.page);
        return {
            propsKeys: Object.keys(pageObj.props),
            jobCards: pageObj.props.jobCards
        };
    });
    console.log('Props keys:', data?.propsKeys);
    console.log('Job cards count:', data?.jobCards?.length);
    console.log('Job cards:', JSON.stringify(data?.jobCards, null, 2));

    await browser.close();
}

checkJobCards();
