import { chromium } from 'playwright-core';

async function testMobileLogin() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const page = await browser.newPage({
        viewport: { width: 390, height: 844 },
        isMobile: true
    });

    console.log('Navigating to login...');
    await page.goto('https://piston.cubestack.in/login');
    await page.locator('#email').fill('demoowner@cubestack.in');
    await page.locator('#password').fill('8^Ek=JZ0@|rN');
    await page.locator('button:has-text("Log in")').click();

    await page.waitForTimeout(4000);
    console.log('URL after login:', page.url());

    const bodyText = await page.innerText('body');
    console.log('Body snippet:\n', bodyText.substring(0, 300));

    // Look for mobile navigation button in AuthenticatedLayout
    const buttons = await page.locator('button').all();
    console.log('Buttons count:', buttons.length);
    for (const b of buttons) {
        const text = await b.innerText();
        const html = await b.evaluate(el => el.outerHTML);
        if (html.includes('svg') || text.trim()) {
            console.log('Button:', text.replace(/\n+/g, ' '), '| Class:', await b.getAttribute('class'));
        }
    }

    await browser.close();
}

testMobileLogin().catch(console.error);
