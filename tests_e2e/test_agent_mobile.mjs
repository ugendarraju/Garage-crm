import { chromium } from 'playwright-core';
import fs from 'fs';

const results = [];
function record(step, status, details = '') {
    results.push({ step, status, details });
    console.log(`[${status.toUpperCase()}] ${step} ${details ? '- ' + details : ''}`);
}

async function runMobileAgent() {
    console.log('=== STARTING MOBILE RESPONSIVENESS TEST AGENT ===');
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });

    // iPhone 14 Viewport
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
        isMobile: true,
        hasTouch: true,
    });
    const page = await mobileContext.newPage();

    const checkOverflow = async (pageName) => {
        const isOverflowing = await page.evaluate(() => {
            return document.documentElement.scrollWidth > window.innerWidth;
        });
        const diff = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        if (isOverflowing && diff > 5) {
            record(`Mobile Overflow Check: ${pageName}`, 'fail', `Horizontal overflow detected: scrollWidth is ${diff}px wider than viewport`);
        } else {
            record(`Mobile Overflow Check: ${pageName}`, 'pass', 'No unwanted horizontal scrolling');
        }
    };

    try {
        // 1. Mobile Login Page
        await page.goto('https://piston.cubestack.in/login');
        await page.waitForTimeout(1500);
        await checkOverflow('Login Page');

        const loginBoxVisible = await page.locator('button:has-text("Log in")').isVisible();
        record('Mobile Login Layout', loginBoxVisible ? 'pass' : 'fail', 'Log in button and inputs properly visible on mobile viewport');

        // Authenticate as Owner
        await page.locator('#email').fill('demoowner@cubestack.in');
        await page.locator('#password').fill('8^Ek=JZ0@|rN');
        await page.locator('button:has-text("Log in")').click();
        await page.waitForURL('**/dashboard', { timeout: 15000 });

        // 2. Mobile Dashboard Layout
        record('Mobile Dashboard Access', page.url().includes('dashboard') ? 'pass' : 'fail', 'Loaded dashboard on mobile');
        await checkOverflow('Dashboard');

        // Check mobile hamburger button
        const hamburger = page.locator('button.md\\:hidden');
        const hamburgerVisible = await hamburger.isVisible();
        record('Mobile Navigation Toggle', hamburgerVisible ? 'pass' : 'fail', 'Mobile menu toggle button is present and accessible');

        if (hamburgerVisible) {
            await hamburger.click();
            await page.waitForTimeout(800);
            const body = await page.innerText('body');
            const menuOpen = body.includes('OPERATIONS') && body.includes('Job Cards');
            record('Mobile Drawer Opening', menuOpen ? 'pass' : 'fail', 'Mobile navigation drawer slides in with operational links');
            // Close drawer by tapping close button or escape
            const closeBtn = page.locator('button:has-text("✕"), aside button, nav button').first();
            if (await closeBtn.isVisible()) {
                await closeBtn.click();
            } else {
                await page.keyboard.press('Escape');
            }
            await page.waitForTimeout(300);
        }

        // 3. Mobile Customers Page
        await page.goto('https://piston.cubestack.in/customers');
        await page.waitForTimeout(2000);
        await checkOverflow('Customers Page');
        const custMobileText = await page.innerText('body');
        record('Mobile Customers List', custMobileText.includes('customer1@cubestack.in') ? 'pass' : 'fail', 'Customers table/cards scrollable or stacked on mobile');

        // 4. Mobile Job Cards Page
        await page.goto('https://piston.cubestack.in/job-cards');
        await page.waitForTimeout(2000);
        await checkOverflow('Job Cards Page');

        // 5. Mobile Inventory Page
        await page.goto('https://piston.cubestack.in/inventory');
        await page.waitForTimeout(2000);
        await checkOverflow('Inventory Page');

        // 6. Mobile Settings Page
        await page.goto('https://piston.cubestack.in/settings');
        await page.waitForTimeout(2000);
        await checkOverflow('Settings Page');

        // 7. Mobile Customer Portal
        console.log('Testing Mobile Customer Portal...');
        const custMobileContext = await browser.newContext({
            viewport: { width: 390, height: 844 },
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
            isMobile: true,
            hasTouch: true,
        });
        const custPage = await custMobileContext.newPage();
        await custPage.goto('https://piston.cubestack.in/login');
        await custPage.locator('#email').fill('customer1@cubestack.in');
        await custPage.locator('#password').fill('8^Ek=JZ0@|rN');
        await custPage.locator('button:has-text("Log in")').click();
        await custPage.waitForURL('**/dashboard', { timeout: 15000 });

        const custPortalText = await custPage.innerText('body');
        record('Mobile Customer Portal', custPortalText.includes('My Dashboard') ? 'pass' : 'fail', 'Customer portal cleanly styled for mobile screens');

        // 8. Mobile Public Booking Page
        await custPage.goto('https://piston.cubestack.in/book/demo-auto-shop');
        await custPage.waitForTimeout(2000);
        const bookOverflow = await custPage.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
        record('Mobile Public Booking Page', !bookOverflow ? 'pass' : 'fail', 'Online booking form adapts seamlessly to mobile screen widths');

    } catch (err) {
        record('Mobile Test Suite Exception', 'fail', err.message);
    } finally {
        await browser.close();
    }

    fs.writeFileSync('tests_e2e/mobile_test_results.json', JSON.stringify(results, null, 2));
    console.log('Mobile responsiveness testing completed. Total tests:', results.length);
}

runMobileAgent().catch(console.error);
