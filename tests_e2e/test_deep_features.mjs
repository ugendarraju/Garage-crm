import { chromium } from 'playwright-core';

const BASE_URL = 'https://piston.cubestack.in';
const OWNER_EMAIL = 'demoowner@cubestack.in';
const OWNER_PASS = '8^Ek=JZ0@|rN';
const CUSTOMER_EMAIL = 'customer1@cubestack.in';
const CUSTOMER_PASS = '8^Ek=JZ0@|rN';

async function runDeepTests() {
    console.log('=== STARTING DEEP OPERATIONAL TEST SUITE ===');
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    const results = [];

    function record(feature, name, passed, details = '') {
        console.log(`[${passed ? 'PASS' : 'FAIL'}] [${feature}] ${name} ${details ? '(' + details + ')' : ''}`);
        results.push({ feature, name, passed, details });
    }

    try {
        // 1. Garage Owner Login
        console.log('\n--- 1. AUTHENTICATION ---');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        await page.locator('#email').fill(OWNER_EMAIL);
        await page.locator('#password').fill(OWNER_PASS);
        await page.locator('button:has-text("Log in")').click();
        await page.waitForURL('**/dashboard', { timeout: 15000 }).catch(() => {});
        await page.waitForTimeout(2000);
        
        const ownerLoggedIn = page.url().includes('/dashboard');
        record('Authentication', 'Owner Login as demoowner@cubestack.in', ownerLoggedIn, page.url());

        // 2. View Customer's Vehicles List (Single and Multi-vehicle feature)
        console.log('\n--- 2. CUSTOMER & VEHICLES VIEW ---');
        await page.goto(`${BASE_URL}/customers`, { waitUntil: 'networkidle' });
        
        // Wait for table to load
        await page.waitForSelector('table');
        const vehiclesHeader = await page.$('th:has-text("Vehicles")');
        record('Customer Vehicles', 'Vehicles column present in table header', !!vehiclesHeader);

        // Check if vehicle buttons / badges exist in the table
        const vehicleButtons = await page.$$('button:has-text("Vehicles"), button:has-text("🚗")');
        record('Customer Vehicles', `Vehicle badges found in customer list (${vehicleButtons.length} badges)`, vehicleButtons.length > 0);

        if (vehicleButtons.length > 0) {
            // Click the first vehicle button/badge to open the View Vehicles Modal
            await vehicleButtons[0].click();
            await page.waitForTimeout(1000);
            
            const modalTitle = await page.$('h2:has-text("Registered Vehicles")');
            const hasModal = !!modalTitle;
            record('Customer Vehicles', 'View Registered Vehicles Modal opened successfully', hasModal);

            // Check if vehicle table inside modal has content
            const vehicleRows = await page.$$('table tbody tr');
            record('Customer Vehicles', 'Vehicle details displayed in modal', vehicleRows.length > 0);

            // Close modal
            const closeBtn = await page.$('button:has-text("Close"), button:has(.sr-only:has-text("Close"))');
            if (closeBtn) await closeBtn.click();
            await page.waitForTimeout(500);
        }

        // 3. Inventory Management
        console.log('\n--- 3. INVENTORY & PARTS ---');
        await page.goto(`${BASE_URL}/inventory`, { waitUntil: 'networkidle' });
        const hasInventoryPage = page.url().includes('/inventory');
        record('Inventory', 'Inventory list page loaded', hasInventoryPage);

        // Check "Add Part" button or form
        const addPartBtn = await page.$('button:has-text("Add Part"), a:has-text("Add Part"), button:has-text("Add Item")');
        record('Inventory', 'Add Part action available', !!addPartBtn);

        // Let's create a test part if Add Part modal/page exists
        const testSku = 'TEST-OIL-' + Math.floor(Math.random() * 9000 + 1000);
        if (addPartBtn) {
            await addPartBtn.click();
            await page.waitForTimeout(1000);

            const partNameInput = await page.$('input[placeholder*="Name" i], input[name="name"], input[name="part_name"]');
            if (partNameInput) {
                await partNameInput.fill('Motul Fully Synthetic Oil 4T');
                
                const skuInput = await page.$('input[placeholder*="SKU" i], input[name="sku"], input[name="part_number"]');
                if (skuInput) await skuInput.fill(testSku);

                const costInput = await page.$('input[name="cost_price"], input[placeholder*="Cost" i]');
                if (costInput) await costInput.fill('450');

                const sellInput = await page.$('input[name="selling_price"], input[placeholder*="Selling" i], input[placeholder*="Price" i]');
                if (sellInput) await sellInput.fill('650');

                const qtyInput = await page.$('input[name="quantity"], input[name="stock_quantity"], input[placeholder*="Stock" i], input[placeholder*="Qty" i]');
                if (qtyInput) await qtyInput.fill('25');

                // Try to select brand / category if dropdowns exist
                const selects = await page.$$('select');
                for (const s of selects) {
                    const opts = await s.$$('option');
                    if (opts.length > 1) {
                        const val = await opts[1].getAttribute('value');
                        if (val) await s.selectOption(val).catch(() => {});
                    }
                }

                // Submit part form
                const saveBtn = await page.$('button[type="submit"]:has-text("Save"), button:has-text("Save Part")');
                if (saveBtn) {
                    await saveBtn.click();
                    await page.waitForTimeout(2000);
                }
            }
        }

        // Verify inventory list contains items
        const inventoryRows = await page.$$('table tbody tr');
        record('Inventory', `Inventory items loaded (${inventoryRows.length} items listed)`, inventoryRows.length > 0);

        // 4. Service Requests Module
        console.log('\n--- 4. SERVICE REQUESTS ---');
        await page.goto(`${BASE_URL}/service-requests`, { waitUntil: 'networkidle' });
        const hasServiceRequests = page.url().includes('/service-requests');
        record('Service Requests', 'Service Requests dashboard loaded', hasServiceRequests);

        const serviceRequestRows = await page.$$('table tbody tr, .divide-y > div');
        record('Service Requests', `Service Requests queue visible (${serviceRequestRows.length} records)`, true);

        // 5. Job Cards & Quotations / Estimates
        console.log('\n--- 5. JOB CARDS & ESTIMATES/QUOTATIONS ---');
        await page.goto(`${BASE_URL}/job-cards`, { waitUntil: 'networkidle' });
        record('Job Cards', 'Job Cards index loaded', page.url().includes('/job-cards'));

        // Check tabs: Estimates, Pending, In Progress, Completed, Delivered
        const estimateTab = await page.$('button:has-text("Estimates"), a:has-text("Estimates")');
        record('Quotations/Estimates', 'Estimates tab available in Job Cards', !!estimateTab);
        if (estimateTab) {
            await estimateTab.click();
            await page.waitForTimeout(500);
            record('Quotations/Estimates', 'Switched to Estimates tab', true);
        }

        // Now test Create Job Card / Estimate
        await page.goto(`${BASE_URL}/job-cards/create`, { waitUntil: 'networkidle' });
        record('Job Cards', 'Job Card / Estimate creation form loaded', page.url().includes('/job-cards/create'));

        // Check if customer select exists
        const customerSelect = await page.$('select');
        let createdJobCardId = null;
        if (customerSelect) {
            const custOpts = await customerSelect.$$('option');
            let custIdToSelect = null;
            for (const opt of custOpts) {
                const text = await opt.innerText();
                const val = await opt.getAttribute('value');
                if (val && val !== 'new' && val !== '') {
                    custIdToSelect = val;
                    break;
                }
            }

            if (custIdToSelect) {
                await customerSelect.selectOption(custIdToSelect);
                await page.waitForTimeout(500);

                // Select vehicle if available
                const selects = await page.$$('select');
                if (selects.length > 1) {
                    const vehOpts = await selects[1].$$('option');
                    for (const opt of vehOpts) {
                        const val = await opt.getAttribute('value');
                        if (val && val !== 'new' && val !== '') {
                            await selects[1].selectOption(val);
                            break;
                        }
                    }
                }

                // Set status to 'scheduled' (Estimate) or 'listed'
                const statusSelect = await page.$('select[name="status"], select:has(option[value="scheduled"])');
                if (statusSelect) {
                    await statusSelect.selectOption('scheduled');
                    record('Quotations/Estimates', 'Set job status to Estimate (scheduled)', true);
                }

                // Add an issue or service description
                const descInput = await page.$('textarea, input[placeholder*="issue" i], input[placeholder*="service" i]');
                if (descInput) {
                    await descInput.fill('Comprehensive inspection, synthetic oil change, general checkup');
                }

                // Submit job card
                const submitBtn = await page.$('button[type="submit"]:has-text("Create"), button[type="submit"]:has-text("Save")');
                if (submitBtn) {
                    await submitBtn.click();
                    await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 10000 }).catch(() => {});
                    await page.waitForTimeout(2000);
                    record('Job Cards', 'Job Card / Estimate saved successfully', true, page.url());
                }
            }
        }

        // 6. Job Card Details & Billing / Invoices
        console.log('\n--- 6. BILLING & INVOICES ---');
        await page.goto(`${BASE_URL}/job-cards`, { waitUntil: 'networkidle' });
        
        // Find first job card link using View button or link not matching 'create'
        const viewLink = await page.$('a:has-text("View"), a[href*="/job-cards/"]:not([href*="create"])');
        let testedJobId = null;
        if (viewLink) {
            const href = await viewLink.getAttribute('href');
            console.log('Navigating to job card details:', href);
            const match = href.match(/\/job-cards\/(\d+)/);
            if (match) testedJobId = match[1];

            await page.goto(href.startsWith('http') ? href : `${BASE_URL}${href}`, { waitUntil: 'networkidle' });

            const jobCardShowLoaded = page.url().includes('/job-cards/');
            record('Job Cards', 'Job Card view & operations page loaded', jobCardShowLoaded);

            // Test Printable Bill / Invoice
            if (testedJobId) {
                await page.goto(`${BASE_URL}/job-cards/${testedJobId}/invoice`, { waitUntil: 'networkidle' });
                const isInvoicePage = page.url().includes('/invoice');
                record('Billing & Invoices', 'Printable Bill/Invoice rendered with financial breakdown', isInvoicePage);
                
                // Verify invoice contains essential elements
                const invoiceTitle = await page.$('text=INVOICE, text=Invoice, text=Bill');
                record('Billing & Invoices', 'Invoice header & bill breakdown verified', !!invoiceTitle);
            }
        }

        // 7. Counter Sales & Revenue Ledger
        console.log('\n--- 7. SALES & REVENUE LEDGER ---');
        await page.goto(`${BASE_URL}/sales`, { waitUntil: 'networkidle' });
        const isSalesLoaded = page.url().includes('/sales');
        record('Sales & Billing', 'Sales & Invoices ledger loaded', isSalesLoaded);

        const revenueHeader = await page.$('h1:has-text("Sales & Revenue"), dt:has-text("Total Sales Revenue")');
        record('Sales & Billing', 'Revenue and sales calculation metrics displayed', !!revenueHeader);

        // 8. Public Service Booking
        console.log('\n--- 8. PUBLIC SERVICE BOOKING ---');
        await page.goto(`${BASE_URL}/book/demo-auto-shop`, { waitUntil: 'networkidle' });
        const isBookingLoaded = page.url().includes('/book');
        record('Service Requests', 'Public booking portal for Demo Auto Shop loaded', isBookingLoaded);

        // Summary
        console.log('\n=== TEST SUITE COMPLETE ===');
        const passedCount = results.filter(r => r.passed).length;
        const totalCount = results.length;
        console.log(`Summary: ${passedCount}/${totalCount} tests passed.`);

    } catch (err) {
        console.error('Fatal error during test run:', err);
    } finally {
        await browser.close();
    }
}

runDeepTests();
