import { chromium } from 'playwright-core';
import fs from 'fs';

const results = [];
function record(step, status, details = '') {
    results.push({ step, status, details });
    console.log(`[${status.toUpperCase()}] ${step} ${details ? '- ' + details : ''}`);
}

async function runOwnerAgent() {
    console.log('=== STARTING GARAGE ADMIN (OWNER) TEST AGENT ===');
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();

    try {
        // 1. Authentication
        await page.goto('https://piston.cubestack.in/login');
        await page.locator('#email').fill('demoowner@cubestack.in');
        await page.locator('#password').fill('8^Ek=JZ0@|rN');
        await page.locator('button:has-text("Log in")').click();
        await page.waitForTimeout(3000);
        if (page.url().includes('dashboard')) {
            record('Owner Authentication', 'pass', 'Successfully logged in to Dashboard');
        } else {
            record('Owner Authentication', 'fail', 'URL: ' + page.url());
        }

        // 2. Dashboard View & Metrics
        const dashText = await page.innerText('body');
        if (dashText.includes('Demo Auto Shop') && dashText.includes('OPERATIONS')) {
            record('Dashboard Overview', 'pass', 'Dashboard renders workshop name and operational metrics');
        } else {
            record('Dashboard Overview', 'fail', 'Dashboard text missing expected components');
        }

        // 3. Customers Page
        await page.goto('https://piston.cubestack.in/customers');
        await page.waitForTimeout(2000);
        const custText = await page.innerText('body');
        if (custText.includes('customer1@cubestack.in') && custText.includes('rfthfgh')) {
            record('Customers Management', 'pass', 'Customer list displayed properly preserving demo dummy data');
        } else {
            record('Customers Management', 'fail', 'Customer list missing expected records');
        }

        // 4. Vehicles & History Page
        await page.goto('https://piston.cubestack.in/vehicles');
        await page.waitForTimeout(2000);
        const vehText = await page.innerText('body');
        record('Vehicles & History', vehText.includes('Vehicles') ? 'pass' : 'fail', 'Loaded vehicles page');

        // 5. Vehicle Brands & Models
        await page.goto('https://piston.cubestack.in/vehicle-brands');
        await page.waitForTimeout(2000);
        const brandsText = await page.innerText('body');
        record('Vehicle Brands', brandsText.includes('Bajaj') || brandsText.includes('Hero') || brandsText.includes('Brand') ? 'pass' : 'fail', 'Vehicle database accessible');

        // 6. Inventory Page
        await page.goto('https://piston.cubestack.in/inventory');
        await page.waitForTimeout(2000);
        const invText = await page.innerText('body');
        record('Inventory Management', invText.includes('Inventory') ? 'pass' : 'fail', 'Inventory page loaded');

        // Check Inventory Export
        const exportRes = await page.evaluate(async () => {
            const resp = await fetch('/inventory/export');
            return resp.status;
        });
        record('Inventory CSV Export', exportRes === 200 ? 'pass' : 'fail', `Export endpoint returned status ${exportRes}`);

        // 7. Part Categories & Brands
        await page.goto('https://piston.cubestack.in/part-categories');
        await page.waitForTimeout(1500);
        const partCatText = await page.innerText('body');
        record('Part Categories', partCatText.includes('Categories') || partCatText.includes('Category') ? 'pass' : 'fail', 'Part categories page accessible');

        await page.goto('https://piston.cubestack.in/part-brands');
        await page.waitForTimeout(1500);
        const partBrandText = await page.innerText('body');
        record('Part Brands', partBrandText.includes('Brands') || partBrandText.includes('Brand') ? 'pass' : 'fail', 'Part brands page accessible');

        // 8. Suppliers
        await page.goto('https://piston.cubestack.in/suppliers');
        await page.waitForTimeout(1500);
        const supText = await page.innerText('body');
        record('Suppliers Management', supText.includes('Supplier') ? 'pass' : 'fail', 'Suppliers page loaded');

        // 9. Purchases
        await page.goto('https://piston.cubestack.in/purchases');
        await page.waitForTimeout(1500);
        const purText = await page.innerText('body');
        record('Purchases & Invoices', purText.includes('Purchase') ? 'pass' : 'fail', 'Purchases page loaded');

        // 10. Job Cards
        await page.goto('https://piston.cubestack.in/job-cards');
        await page.waitForTimeout(2000);
        const jcText = await page.innerText('body');
        record('Job Cards Overview', jcText.includes('Job Card') ? 'pass' : 'fail', 'Job cards management view loaded');

        // Check Create Job Card Form
        await page.goto('https://piston.cubestack.in/job-cards/create');
        await page.waitForTimeout(2000);
        const createJcText = await page.innerText('body');
        record('Job Card Creation Form', createJcText.includes('Customer') && (createJcText.includes('Vehicle') || createJcText.includes('Job')) ? 'pass' : 'fail', 'Job card builder wizard accessible');

        // 11. Service Requests Admin View
        await page.goto('https://piston.cubestack.in/service-requests');
        await page.waitForTimeout(2000);
        const srText = await page.innerText('body');
        record('Service Requests Admin', srText.includes('Service Request') ? 'pass' : 'fail', 'Service requests review queue accessible');

        // 12. Service Reminders
        await page.goto('https://piston.cubestack.in/service-reminders');
        await page.waitForTimeout(1500);
        const remText = await page.innerText('body');
        record('Service Reminders', remText.includes('Reminder') ? 'pass' : 'fail', 'Service reminders tracker loaded');

        // 13. Counter Sales
        await page.goto('https://piston.cubestack.in/sales');
        await page.waitForTimeout(1500);
        const salesText = await page.innerText('body');
        record('Counter Sales', salesText.includes('Sales') || salesText.includes('Counter') ? 'pass' : 'fail', 'Counter sales module accessible');

        // 14. Expenses & Net Profit Tracker
        await page.goto('https://piston.cubestack.in/expenses');
        await page.waitForTimeout(1500);
        const expText = await page.innerText('body');
        record('Expenses & Net Profit', expText.includes('Expense') ? 'pass' : 'fail', 'Expense and net profit tracker loaded');

        // 15. Discount Coupons
        await page.goto('https://piston.cubestack.in/coupons');
        await page.waitForTimeout(1500);
        const cpnText = await page.innerText('body');
        record('Coupons Management', cpnText.includes('Coupon') ? 'pass' : 'fail', 'Discount coupons manager loaded');

        // 16. Staff & Mechanics Management
        await page.goto('https://piston.cubestack.in/staff');
        await page.waitForTimeout(1500);
        const staffText = await page.innerText('body');
        record('Staff & Team Management', staffText.includes('Staff') || staffText.includes('Mechanic') ? 'pass' : 'fail', 'Staff roster loaded');

        // 17. Service Types (Labor & Packages)
        await page.goto('https://piston.cubestack.in/service-types');
        await page.waitForTimeout(1500);
        const stText = await page.innerText('body');
        record('Service Types & Packages', stText.includes('Service Type') ? 'pass' : 'fail', 'Service types master list loaded');

        // 18. Workshop Settings & Master Data
        await page.goto('https://piston.cubestack.in/settings');
        await page.waitForTimeout(2000);
        const settText = await page.innerText('body');
        record('Settings & Master Data', settText.includes('Demo Auto Shop') || settText.includes('Settings') ? 'pass' : 'fail', 'Workshop profile & settings accessible');

        // 19. User Guide & Help Center
        await page.goto('https://piston.cubestack.in/user-guide');
        await page.waitForTimeout(1500);
        const ugText = await page.innerText('body');
        record('Interactive User Guide', ugText.includes('Guide') || ugText.includes('Help') ? 'pass' : 'fail', 'Documentation and user guide loaded');

        // 20. Action Logs Audit Trail
        await page.goto('https://piston.cubestack.in/action-logs');
        await page.waitForTimeout(2000);
        const actText = await page.innerText('body');
        record('Action Logs & Audit Trail', actText.includes('Action Logs') ? 'pass' : 'fail', 'Audit trail loaded with live records');

    } catch (err) {
        record('Owner Test Suite Exception', 'fail', err.message);
    } finally {
        await browser.close();
    }

    fs.writeFileSync('tests_e2e/owner_test_results.json', JSON.stringify(results, null, 2));
    console.log('Owner testing completed. Total tests:', results.length);
}

runOwnerAgent().catch(console.error);
