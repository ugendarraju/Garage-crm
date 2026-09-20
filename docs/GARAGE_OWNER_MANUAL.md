# 🚗 Garage CRM — Garage Owner Onboarding & Operating Manual

Welcome to your **Garage Management System (CRM)**! This guide is designed to walk you through every feature and workflow in your workshop CRM—from your first day of setup to managing daily vehicle servicing, inventory, mechanic assignments, and billing.

---

## 📑 Table of Contents
1. [Day 1: Quick Setup Checklist](#1-day-1-quick-setup-checklist)
2. [Workshop Branding & Invoice Settings](#2-workshop-branding--invoice-settings)
3. [Staff & Mechanic Management](#3-staff--mechanic-management)
4. [Service Catalog & Labor Types](#4-service-catalog--labor-types)
5. [Inventory & Spare Parts Management](#5-inventory--spare-parts-management)
6. [Suppliers & Purchase Invoices](#6-suppliers--purchase-invoices)
7. [Job Cards Masterclass (Core Workflow)](#7-job-cards-masterclass-core-workflow)
8. [Generating GST Tax Invoices & QR Payments](#8-generating-gst-tax-invoices--qr-payments)
9. [Online Customer Bookings (Service Requests)](#9-online-customer-bookings-service-requests)
10. [Customer CRM & Client Self-Service Portal](#10-customer-crm--client-self-service-portal)
11. [Direct Counter Sales (OTC Billing)](#11-direct-counter-sales-otc-billing)
12. [Promotional Coupons & Discounts](#12-promotional-coupons--discounts)
13. [Vehicle Database (Brands & Models)](#13-vehicle-database-brands--models)
14. [Using the Garage AI Assistant](#14-using-the-garage-ai-assistant)
15. [Daily Workflow Summary & Best Practices](#15-daily-workflow-summary--best-practices)

---

## 1. Day 1: Quick Setup Checklist

Before you create your first job card, complete these 4 initial setup steps (takes about 5–10 minutes):

1. **Configure Garage Profile**: Go to **Settings** → Fill in your workshop name, address, GST number, bank details, and upload your logo and UPI payment QR code.
2. **Add Your Mechanics**: Go to **User Management** → Add your mechanics and workshop staff.
3. **Add Initial Spare Parts**: Go to **Inventory** → Add your most commonly used parts (engine oils, filters, brake pads, coolant) or use CSV bulk import.
4. **Define Service Types**: Go to **Service Types** → Add your standard services (General Service, Water Wash, Wheel Alignment, AC Service).

---

## 2. Workshop Branding & Invoice Settings

Your settings determine how your invoices look and how customers pay you.

### How to configure:
1. Click **Settings** in the left sidebar menu.
2. **General Information**:
   - **Garage Name**: Displayed on top of customer invoices and dashboard.
   - **Tagline**: E.g., *"Complete Car & Bike Care Experts"*.
   - **Full Address**: Physical location printed on the bill header.
   - **GST Number**: If registered under GST, enter your 15-digit GSTIN.
3. **Branding & Logo**:
   - Click **Choose File** under **Logo** to upload your workshop logo (PNG, JPG, or SVG).
   - Use the **Header Logo Display Size** slider (20px to 100px) to adjust how large your logo appears on invoices.
4. **Payment Details**:
   - **Bank Details**: Bank Name, Account Number, IFSC code for NEFT/IMPS transfers.
   - **UPI ID**: Your business UPI address (e.g. `workshop@okhdfcbank` or Google Pay number).
   - **Payment QR Code**: Upload a screenshot of your PhonePe/GPay/Paytm QR code. **This QR code is printed directly on your customer invoices so clients can scan and pay instantly.**
5. Click **Save Settings**.

---

## 3. Staff & Mechanic Management

Track which mechanics work on which vehicle and manage staff accounts.

### How to add a staff member:
1. Click **User Management** in the sidebar.
2. Click **+ Add Staff Member**.
3. Enter their:
   - Full Name
   - Email address (for login)
   - Phone number
   - Password
   - **Role**:
     - `mechanic`: Assigned to vehicle repairs and job cards.
     - `staff`: Front-desk, service advisors, or billing managers.
4. Click **Save**.

> **Tip**: You can toggle a staff member's status between **Active** and **Inactive** at any time without deleting their historical records.

---

## 4. Service Catalog & Labor Types

Create pre-saved labor and service categories to speed up billing.

1. Go to **Service Types** in the sidebar.
2. Click **+ Add Service Type**.
3. Enter the service name (e.g., *Full Synthetic Oil Service*, *Clutch Overhaul*, *Brake Pad Replacement*, *Computer Scanning*).
4. Enter standard rate / description.
5. Click **Save**.

---

## 5. Inventory & Spare Parts Management

Prevent stockouts and track profit margins on parts.

### A. Adding Items Manually:
1. Go to **Inventory** in the sidebar.
2. Click **+ Add Item**.
3. Fill in:
   - **Part Name**: E.g., *Castrol 5W30 Magnatec Engine Oil (3.5L)*.
   - **Part Number / SKU**: E.g., *CAS-5W30-3.5*.
   - **Category**: E.g., *Lubricants*, *Braking*, *Filters*.
   - **Brand**: E.g., *Castrol*, *Bosch*, *NGK*.
   - **Quantity on Hand**: Current shelf stock.
   - **Minimum Stock Level**: Get alert when stock drops below this number (e.g., 2).
   - **Purchase Price**: Cost price from your supplier.
   - **Selling Price**: Customer billing price.
4. Click **Save**.

### B. Bulk Import via Excel/CSV:
- Click **Bulk Import** on the Inventory screen to upload a spreadsheet of all your parts in one click.
- Click **Export** to download your current inventory into Excel anytime for auditing.

---

## 6. Suppliers & Purchase Invoices

When new stock arrives from your spare parts distributor, log the purchase to update your inventory automatically.

### How to record a purchase:
1. Go to **Purchases** in the sidebar → Click **+ New Purchase**.
2. Select or create the **Supplier** (e.g., *Metro Auto Spares*).
3. Enter Invoice Number and Purchase Date.
4. Add the items purchased with their quantity, cost price, and tax (GST).
5. **Attach Bill**: Upload a photo/PDF of the physical bill for accounting records.
6. Set status to **Received** → **Your inventory quantities will automatically increment!**

---

## 7. Job Cards Masterclass (Core Workflow)

The **Job Card** is the central heart of your garage operations.

```
[Service Request] ➔ [Estimate] ➔ [Pending/Listed] ➔ [In Progress] ➔ [Completed] ➔ [Delivered & Paid]
```

### Creating a New Job Card:
1. Click **Job Cards** in the sidebar → Click **+ Create New Jobcard** (top right).
2. **Customer & Vehicle Section**:
   - **Existing Customer**: Select customer from dropdown. Their registered vehicles will automatically appear in the vehicle dropdown!
   - **New Customer on the Spot**: Click `+ Add New Customer` directly in the dropdown, enter their name and phone.
   - **New Vehicle on the Spot**: Click `+ Add New Vehicle`, enter Registration Number (e.g. `DL01AB1234`), Brand (e.g. *Hyundai*), and Model (e.g. *i20*).
3. **Assign Mechanics**:
   - Select one or more mechanics assigned to this vehicle (Hold `Ctrl` on PC to select multiple).
4. **Job Dates & Priorities**:
   - **Job Date**: Defaults to today.
   - **Expected Delivery Date**: When the customer can expect their vehicle back.
   - **Priority**: *Low*, *Medium*, or *High*.
   - **Status**:
     - `Estimate`: Customer only wants a preliminary quote.
     - `Pending`: Approved, waiting for bay or parts.
     - `In Progress`: Mechanic actively working on the car.
   - **Next Service Reminder**: Pick a future date (e.g. 6 months / 10,000 km) for automated follow-up.
   - **Odometer (KM)**: Current vehicle odometer reading.
   - **Fuel / Range Level**: Select `Empty`, `1/4`, `1/2`, `3/4`, or `Full` (protects you from customer disputes).
5. **Customer Issues & Complaints**:
   - Click **+ Add Issue** to record customer complaints (e.g., *"Squeaking noise when braking"*, *"AC not cooling effectively"*).
6. **Vehicle Media / Inspection Photos**:
   - Upload inspection photos before work starts (existing scratches, dents, fuel gauge).
7. **Adding Services & Spare Parts**:
   - Click **+ Add Item**:
     - Choose **Type**: `Service` (labor) or `Part` (spare part).
     - Enter Description and Rate.
     - Toggle **Apply GST** and select tax bracket (`CGST + SGST 18%`, `28%`, etc.).
8. **Discounts & Coupons**:
   - Enter manual discount amount OR enter a promotional coupon code.
9. Click **Create Job Card**.

---

## 8. Generating GST Tax Invoices & QR Payments

Once repairs are finished:
1. Open the Job Card → Update status to **Completed** or **Delivered**.
2. Click **Download / View Invoice** (or click the printer icon).
3. A clean, professional **GST Tax Invoice** is rendered:
   - Your workshop logo & address at the top.
   - Customer details & vehicle registration plate.
   - Itemized parts and labor breakdown with GST subtotals.
   - Your **Bank Details** and **UPI Payment QR Code** printed at the bottom!
4. Customer scans the printed QR code using PhonePe, Google Pay, or Paytm and pays directly.
5. In the Job Card screen, enter the **Amount Paid** and payment method (`UPI`, `Cash`, or `Card`).
6. You can also upload a **Payment Screenshot** for verification.

---

## 9. Online Customer Bookings (Service Requests)

Customers can book services directly from your public booking portal (`/book`) on their mobile phones!

1. When a customer submits a booking online, it immediately appears in your **Service Requests** tab.
2. Click **Service Requests** in your sidebar.
3. Review the customer's request (preferred date, vehicle model, and complaints).
4. **Approve**: Clicking **Approve** converts the request into a live **Job Card** with customer and vehicle details pre-filled!
5. **Decline**: If your bays are full, decline with a short reason.

---

## 10. Customer CRM & Client Self-Service Portal

Provide a high-end dealership experience to your customers.

### How Customers Track Their Vehicles:
- Every customer created in your system has an account (their phone number/email).
- When a customer logs in to your CRM, they get a dedicated **Customer Portal**:
  - They see their active job cards in real time (**Estimate**, **In Progress**, **Ready for Delivery**).
  - They see the itemized cost breakdown and estimate.
  - They can upload payment transaction receipts directly from their phone!

### Managing Customers from Admin:
1. Go to **Customers** in the sidebar.
2. View full service history of every vehicle owned by the customer.
3. Click **Reset Password** if a customer forgets their login credentials.

---

## 11. Direct Counter Sales (OTC Billing)

If a walk-in customer just wants to buy engine oil, coolant, or accessories without servicing a vehicle:

1. Click **Sales** in the sidebar.
2. Add the items sold from your inventory.
3. Apply tax and payment method.
4. Print instant counter receipt.
5. Inventory stock is decremented in real time.

---

## 12. Promotional Coupons & Discounts

Attract repeat business and seasonal customers with digital coupon codes.

1. Go to **Coupons** in the sidebar → Click **+ Create Coupon**.
2. Enter:
   - **Code**: E.g., `MONSOON500`, `FIRSTSERVICE`, `DIWALI10`.
   - **Type**: `Fixed Amount` (e.g. ₹500 off) or `Percentage` (e.g. 10% off).
   - **Minimum Spend**: Minimum bill total required to qualify.
   - **Valid Dates**: Expiration date.
3. Click **Save**.
4. When building job cards, typing this code instantly calculates the discount!

---

## 13. Vehicle Database (Brands & Models)

Your CRM comes pre-populated with Indian and international automotive manufacturers (Maruti, Hyundai, Tata, Mahindra, Toyota, Honda, Royal Enfield, Yamaha, etc.).

### Adding new brands or models:
1. Go to **Vehicle Database** in the sidebar.
2. To add a new car/bike brand: Enter brand name under **Add Brand** → Click Save.
3. To add a model: Select the brand, enter model name (e.g. *Creta*, *Nexon*, *Seltos*, *Classic 350*), and click Save.

---

## 14. Using the Garage AI Assistant

Your CRM includes an intelligent AI assistant accessible via the floating icon in the bottom-right corner of your screen:

- **Voice & Typing Commands**: Speak into your microphone or type instructions.
- **Example Actions**:
  - *"Add 1 Oil Filter to vehicle DL01AB1234"*
  - *"Check how many brake pads are in stock"*
  - *"Add Yamaha R15 models to database"*
- The AI communicates directly with your database to speed up routine tasks!

---

## 15. Daily Workflow Summary & Best Practices

| Time of Day | Recommended Action |
|---|---|
| **Morning** | 1. Check **Service Requests** for new online bookings.<br>2. Review **Pending Job Cards** on the Dashboard and assign bays/mechanics. |
| **During the Day** | 1. Create Job Cards as vehicles arrive (note fuel level and odometer!).<br>2. Upload inspection photos for dents/scratches.<br>3. Move job status from `Pending` ➔ `In Progress`. |
| **Evening / Delivery** | 1. Finalize parts and labor items.<br>2. Mark job as `Completed`.<br>3. Print GST Tax Invoice with your **UPI QR Code**.<br>4. Collect payment and mark as `Delivered`.<br>5. Check **Inventory** for low stock warnings before closing. |

---

## 16. Customer Portal & Vehicle Owner User Guide (Shareable)

You can share these instructions with your workshop customers:

### How Customers Can Log In & Track Repairs
1. Visit your workshop's portal URL on your mobile phone browser.
2. Log in using your registered mobile number or email address.
3. On **My Dashboard**, view:
   - **Active Job Cards**: Real-time status (`Scheduled` ➔ `Working` ➔ `Completed` ➔ `Delivered`).
   - **Itemized Estimate**: Labor and spare parts breakdown with GST.
   - **Expected Completion Date & Time**.

### How Customers Can Book Appointments Online
1. Click **Raise Service Request** on your dashboard (or visit `/book`).
2. Pick your vehicle, required service packages (General Service, Oil Change, Water Wash, etc.), preferred date, and arrival time.
3. Add any notes about noises or issues.
4. Your booking is sent to the workshop for bay reservation.

### How Customers Pay via UPI QR Code
1. Open your completed Job Card or Tax Invoice.
2. Scan the workshop's verified **UPI QR Code** using Google Pay, PhonePe, or Paytm.
3. Pay the exact invoice amount.
4. Take a screenshot of the payment and click **Upload Payment Screenshot** on your Job Card screen.
5. Workshop front desk gets instant confirmation and releases your vehicle keys!

---

*This documentation is maintained in your CRM repository under `docs/GARAGE_OWNER_MANUAL.md`. Any new features added to the platform will be automatically documented here.*

