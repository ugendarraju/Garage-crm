import { chromium } from 'playwright-core';

async function testInspectionResponsiveness() {
    console.log('=== VERIFYING INSPECTION CHECKLIST RESPONSIVENESS ===');
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });

    // 1. Desktop Test (1280 x 800)
    console.log('\n--- 1. DESKTOP VIEWPORT (1280x800) ---');
    const desktopContext = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const desktopPage = await desktopContext.newPage();

    await desktopPage.goto('https://piston.cubestack.in/login', { waitUntil: 'networkidle' });
    await desktopPage.locator('#email').fill('demoowner@cubestack.in');
    await desktopPage.locator('#password').fill('8^Ek=JZ0@|rN');
    await desktopPage.locator('button:has-text("Log in")').click();
    await desktopPage.waitForURL('**/dashboard', { timeout: 15000 });

    await desktopPage.goto('https://piston.cubestack.in/job-cards/create', { waitUntil: 'networkidle' });
    await desktopPage.waitForTimeout(2000);

    // Scroll to Inspection checklist
    const checklistHeading = desktopPage.locator('h3:has-text("Vehicle Intake Inspection Checklist")');
    await checklistHeading.scrollIntoViewIfNeeded();
    await desktopPage.waitForTimeout(1000);

    // Inspect labels in checklist
    // Inspect labels in checklist
    const desktopLabels = await desktopPage.locator('h4:has-text("EXTERIOR & BODY") ~ div span.font-semibold, h4:has-text("Exterior") ~ div span').allInnerTexts();
    console.log('Desktop Item Labels count:', desktopLabels.length);
    console.log('Sample Labels on Desktop:', desktopLabels.slice(0, 5));

    // Check if any label is truncated to a single character
    const desktopTruncated = desktopLabels.filter(l => l.trim().length <= 2);
    console.log('Desktop Single-letter Truncated Labels:', desktopTruncated.length);

    // Screenshot desktop view
    await checklistHeading.locator('xpath=ancestor::div[contains(@class, "overflow-hidden")][1]').screenshot({ path: 'tests_e2e/inspection_desktop.png' });
    console.log('✓ Desktop screenshot saved: tests_e2e/inspection_desktop.png');

    // 2. Mobile Viewport Test (iPhone 375 x 812)
    console.log('\n--- 2. MOBILE VIEWPORT (375x812 - iPhone) ---');
    const mobileContext = await browser.newContext({
        viewport: { width: 375, height: 812 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        isMobile: true,
        hasTouch: true
    });
    const mobilePage = await mobileContext.newPage();

    await mobilePage.goto('https://piston.cubestack.in/login', { waitUntil: 'networkidle' });
    await mobilePage.locator('#email').fill('demoowner@cubestack.in');
    await mobilePage.locator('#password').fill('8^Ek=JZ0@|rN');
    await mobilePage.locator('button:has-text("Log in")').click();
    await mobilePage.waitForURL('**/dashboard', { timeout: 15000 });

    await mobilePage.goto('https://piston.cubestack.in/job-cards/create', { waitUntil: 'networkidle' });
    await mobilePage.waitForTimeout(2000);

    const mobileChecklistHeading = mobilePage.locator('h3:has-text("Vehicle Intake Inspection Checklist")');
    await mobileChecklistHeading.scrollIntoViewIfNeeded();
    await mobilePage.waitForTimeout(1000);

    const mobileLabels = await mobilePage.locator('h4:has-text("EXTERIOR & BODY") ~ div span.font-semibold, h4:has-text("Exterior") ~ div span').allInnerTexts();
    console.log('Mobile Item Labels count:', mobileLabels.length);
    console.log('Sample Labels on Mobile:', mobileLabels.slice(0, 5));

    const mobileTruncated = mobileLabels.filter(l => l.trim().length <= 2);
    console.log('Mobile Single-letter Truncated Labels:', mobileTruncated.length);

    // Screenshot mobile view
    await mobileChecklistHeading.locator('xpath=ancestor::div[contains(@class, "overflow-hidden")][1]').screenshot({ path: 'tests_e2e/inspection_mobile.png' });
    console.log('✓ Mobile screenshot saved: tests_e2e/inspection_mobile.png');

    await browser.close();
}

testInspectionResponsiveness();
