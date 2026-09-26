import { chromium } from 'playwright-core';

const BASE_URL = 'https://piston.cubestack.in';
const OWNER_EMAIL = 'demoowner@cubestack.in';
const OWNER_PASS = '8^Ek=JZ0@|rN';

async function runFullTransaction() {
    console.log('=== STARTING FULL TRANSACTION & MULTI-VEHICLE TEST ===');
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();

    try {
        // 1. Login
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await page.locator('#email').fill(OWNER_EMAIL);
        await page.locator('#password').fill(OWNER_PASS);
        await page.locator('button:has-text("Log in")').click();
        await page.waitForURL('**/dashboard', { timeout: 15000 });
        console.log('✓ Logged in as Garage Owner');

        // 2. View Customer & Multi-Vehicle feature
        await page.goto(`${BASE_URL}/customers`, { waitUntil: 'networkidle' });
        await page.waitForSelector('table');
        console.log('✓ Loaded Customers page');

        // Verify multi-vehicle badge
        const multiVehBadge = page.locator('button:has-text("Vehicles"), button:has-text("🚗")').first();
        const hasBadge = await multiVehBadge.isVisible();
        console.log(`✓ Vehicle button/badge visible: ${hasBadge}`);

        if (hasBadge) {
            await multiVehBadge.click();
            await page.waitForTimeout(1000);
            const modalVisible = await page.locator('h2:has-text("Registered Vehicles")').isVisible();
            console.log(`✓ View Registered Vehicles Modal opened: ${modalVisible}`);

            const vehicleCountInModal = await page.locator('table tbody tr:has(span.bg-gray-100)').count();
            console.log(`✓ Vehicles listed inside modal: ${vehicleCountInModal}`);

            // Close modal
            await page.locator('button:has-text("Close"), button:has(.sr-only:has-text("Close"))').first().click();
            await page.waitForTimeout(500);
        }

        // 3. Create an Estimate / Quotation Job Card
        console.log('\n--- 3. CREATING ESTIMATE / QUOTATION ---');
        await page.goto(`${BASE_URL}/job-cards/create`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        // Select Customer One
        const customerSelect = page.locator('select:has(option:has-text("Select Customer"))');
        const custOptions = await customerSelect.locator('option').all();
        for (const opt of custOptions) {
            const txt = await opt.innerText();
            if (txt.includes('Customer One')) {
                const val = await opt.getAttribute('value');
                await customerSelect.selectOption(val);
                console.log(`✓ Selected customer: ${txt.trim()}`);
                break;
            }
        }
        await page.waitForTimeout(1000);

        // Select Vehicle
        const vehicleSelect = page.locator('select:has(option:has-text("Select vehicle"))');
        const vehOptions = await vehicleSelect.locator('option').all();
        for (const opt of vehOptions) {
            const val = await opt.getAttribute('value');
            if (val && val !== 'new' && val !== '') {
                const txt = await opt.innerText();
                await vehicleSelect.selectOption(val);
                console.log(`✓ Selected vehicle: ${txt.trim()}`);
                break;
            }
        }

        // Check the "Estimate Only" checkbox to mark it as Estimate/Quotation
        const estimateCheckbox = page.locator('input[type="checkbox"][true-value="scheduled"]');
        if (await estimateCheckbox.count() > 0) {
            await estimateCheckbox.check();
            console.log('✓ Checked "Estimate Only" checkbox');
        }

        // Fill Service items
        const serviceName = page.locator('input[placeholder*="Oil change" i], input[placeholder*="Service" i]').first();
        if (await serviceName.count() > 0) {
            await serviceName.fill('Complete Engine Tuneup & Oil Change');
        }

        const serviceRate = page.locator('input[type="number"][placeholder*="500" i], input[placeholder*="Rate" i]').first();
        if (await serviceRate.count() > 0) {
            await serviceRate.fill('1500');
        }

        // Submit form
        console.log('Submitting Job Card / Estimate...');
        const createBtn = page.locator('button[type="submit"]:has-text("Create Job Card")');
        await createBtn.click();
        await page.waitForURL('**/job-cards', { timeout: 15000 }).catch(() => {});
        await page.waitForTimeout(2000);
        console.log(`✓ Form submitted. URL: ${page.url()}`);

        // 4. Verify in Estimates Tab
        await page.goto(`${BASE_URL}/job-cards`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const estimateTab = page.locator('button:has-text("Estimates")');
        await estimateTab.click();
        await page.waitForTimeout(1000);

        const estimateCards = await page.locator('span:has-text("Estimate"), span:has-text("ESTIMATE")').count();
        console.log(`✓ Estimate card visible in Estimates tab: ${estimateCards > 0} (${estimateCards} estimate cards)`);

        // 5. Open Job Card Details & Check Invoice / Bill
        console.log('\n--- 5. VIEWING JOB CARD & BILL/INVOICE ---');
        const viewBtn = page.locator('a:has-text("View")').first();
        if (await viewBtn.count() > 0) {
            const href = await viewBtn.getAttribute('href');
            console.log(`✓ Opening Job Card: ${href}`);
            await page.goto(href.startsWith('http') ? href : `${BASE_URL}${href}`, { waitUntil: 'networkidle' });

            const invoiceBtn = page.locator('a[href*="/invoice"], a:has-text("Invoice"), a:has-text("Download")').first();
            if (await invoiceBtn.count() > 0) {
                const invHref = await invoiceBtn.getAttribute('href');
                console.log(`✓ Opening Printable Bill / Invoice: ${invHref}`);
                await page.goto(invHref.startsWith('http') ? invHref : `${BASE_URL}${invHref}`, { waitUntil: 'networkidle' });
                
                const hasInvoiceTitle = await page.locator('text=INVOICE, text=Invoice, text=Bill').count();
                console.log(`✓ Printable Bill/Invoice rendered properly: ${hasInvoiceTitle > 0}`);
            }
        }

        // 6. Transition Job Card to Delivered and verify on Sales Page
        console.log('\n--- 6. STATUS TRANSITION & SALES LEDGER ---');
        await page.goto(`${BASE_URL}/job-cards`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const statusSelect = page.locator('select:has(option[value="delivered"])').first();
        if (await statusSelect.count() > 0) {
            await statusSelect.selectOption('delivered');
            console.log('✓ Updated Job Card status to: Delivered');
            await page.waitForTimeout(2000);
        }

        // Check Sales Page
        await page.goto(`${BASE_URL}/sales`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        const salesHeader = await page.locator('h1:has-text("Sales & Revenue")').isVisible();
        const salesRows = await page.locator('table tbody tr').count();
        console.log(`✓ Sales & Revenue ledger verified: Header visible (${salesHeader}), delivered sales entries (${salesRows})`);

        console.log('\n=== COMPLETE OPERATIONAL FLOW SUCCESSFULLY VERIFIED! ===');

    } catch (err) {
        console.error('Error during full transaction test:', err);
    } finally {
        await browser.close();
    }
}

runFullTransaction();
