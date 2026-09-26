import { chromium } from 'playwright-core';

async function checkProductionAssets() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('https://piston.cubestack.in/login');
    const scripts = await page.locator('script[src], link[href]').evaluateAll(els => {
        return els.map(e => e.src || e.href).filter(s => s.includes('/build/'));
    });
    console.log('Production build assets loaded:', scripts);

    await browser.close();
}

checkProductionAssets();
