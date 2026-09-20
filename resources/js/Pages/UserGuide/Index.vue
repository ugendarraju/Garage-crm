<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role || 'owner');

const searchQuery = ref('');
const selectedCategory = ref(userRole.value === 'customer' ? 'for_customers' : 'all');
const openItem = ref(null);

const categories = [
    { id: 'all', name: 'All Guides' },
    { id: 'for_customers', name: '📱 For Vehicle Owners (Customers)' },
    { id: 'setup', name: '🚀 Day 1 Setup' },
    { id: 'job_cards', name: '🛠️ Job Cards' },
    { id: 'billing', name: '🧾 Invoices & Payments' },
    { id: 'customers', name: '👥 Workshop Customers' },
    { id: 'inventory', name: '📦 Inventory & Parts' },
    { id: 'staff', name: '🧑‍🔧 Staff & Mechanics' },
    { id: 'bookings', name: '📅 Online Bookings' },
    { id: 'ai', name: '🤖 AI Assistant' },
];

const quickSearchTags = [
    'Customer portal',
    'Book online',
    'Track vehicle',
    'UPI QR code',
    'Add customer',
    'Create job card',
    'Print invoice',
    'Bulk import parts',
    'Add mechanic',
    'Upload payment proof',
];

const guides = [
    {
        id: 'add-customer',
        category: 'customers',
        title: 'How to Add a New Customer and Vehicle',
        summary: 'Register customer contact details and connect their car or motorcycle to their profile.',
        keywords: ['add customer', 'new customer', 'create customer', 'register customer', 'customer phone', 'add vehicle', 'vehicle registration', 'number plate'],
        actionRoute: 'customers.index',
        actionText: 'Go to Customers',
        steps: [
            'Method 1 (From Customers Menu): Go to **Customers** in the sidebar → Click **+ Add Customer** → Enter Full Name, Mobile Number, Email, and Address.',
            'Method 2 (On-the-Fly during Job Card creation): When opening a new Job Card, click the **Customer** dropdown and choose **+ Add New Customer** to add them instantly without leaving the screen.',
            'Adding a Vehicle: Select the customer → Click **+ Add Vehicle** → Enter Registration Number (e.g. DL01AB1234), Brand (e.g. Hyundai), Model (e.g. i20), and Year.',
            'The customer is immediately saved to your database, and an online tracking login is generated for them.'
        ],
        tip: 'Whenever you enter a customer phone number, they can use it to log in to the Customer Portal and check their vehicle status live!'
    },
    {
        id: 'create-job-card',
        category: 'job_cards',
        title: 'How to Create a Job Card (Full Step-by-Step)',
        summary: 'Record customer complaints, vehicle intake status, assign mechanics, and quote parts & labor.',
        keywords: ['create job card', 'new job card', 'jobcard', 'estimate', 'service intake', 'add job', 'mechanic assignment', 'fuel range'],
        actionRoute: 'job-cards.create',
        actionText: 'Open Job Card Workspace',
        steps: [
            'Click **Job Cards** in the sidebar → Click **+ Create New Jobcard**.',
            'Select the **Customer** and **Vehicle** from the dropdowns (or add new ones directly).',
            'Select the assigned **Mechanics** (hold Ctrl/Cmd to select multiple technicians).',
            'Set **Job Date**, **Expected Delivery Date**, and **Priority** (Low, Medium, High).',
            'Record **Current Odometer (KM)** and select the **Fuel / Range Level** (Empty, 1/4, 1/2, 3/4, Full) to prevent customer disputes.',
            'Under **Customer Issues**, click **+ Add Issue** to record specific complaints (e.g., "Brake noise", "AC cooling low").',
            'Under **Services & Parts**, click **+ Add Item** to add labor charges or spare parts with their GST tax rate.',
            'Apply any discount or coupon code, then click **Create Job Card**.'
        ],
        tip: 'Always note the Fuel Level and Odometer KM upon vehicle check-in to ensure transparency with vehicle owners.'
    },
    {
        id: 'setup-branding-qr',
        category: 'setup',
        title: 'How to Upload Workshop Logo, GSTIN, and UPI QR Code',
        summary: 'Personalize your workshop profile and generate professional invoices with your QR code.',
        keywords: ['upload logo', 'qr code', 'upi qr', 'gst number', 'bank details', 'settings', 'workshop address', 'invoice logo'],
        actionRoute: 'settings.edit',
        actionText: 'Open Garage Settings',
        steps: [
            'Click **Settings** in the left sidebar menu.',
            'Fill in your **Garage Name**, **Tagline**, **Full Address**, and **GST Number**.',
            'Under **Branding**, click **Choose File** to upload your workshop logo (PNG, JPG, SVG).',
            'Use the **Header Logo Display Size** slider to adjust how large your logo appears on invoices.',
            'Under **Payment Details**, enter your **Bank Details** and **UPI ID**.',
            'Upload a screenshot of your **PhonePe / Google Pay / Paytm QR Code** under **Payment QR Code**.',
            'Click **Save Settings**. Your logo and UPI QR code will now appear automatically on all customer bills!'
        ],
        tip: 'Having your UPI QR code printed on the invoice allows customers to scan and pay from their phone in seconds.'
    },
    {
        id: 'print-invoice',
        category: 'billing',
        title: 'How to Generate and Print GST Tax Invoices',
        summary: 'Generate print-ready, itemized tax invoices with vehicle details, GST breakdown, and payment QR code.',
        keywords: ['print invoice', 'gst invoice', 'download invoice', 'bill', 'tax invoice', 'receipt', 'customer bill'],
        actionRoute: 'job-cards.index',
        actionText: 'View Job Cards',
        steps: [
            'Go to **Job Cards** in the sidebar.',
            'Find the vehicle job card that is ready for delivery.',
            'Click the **Download / Invoice** button (or click into the Job Card and click "View Invoice").',
            'A clean, professional GST Tax Invoice will open with your workshop logo, customer info, registration number, itemized labor & parts, GST summary, and your scan-to-pay QR code.',
            'Press **Ctrl + P** (or Cmd + P on Mac) to print directly to your thermal/A4 printer or save as PDF.'
        ],
        tip: 'The invoice automatically displays separate CGST & SGST or IGST breakdowns for easy monthly GST filing.'
    },
    {
        id: 'record-payment',
        category: 'billing',
        title: 'How to Record Payments and Outstanding Balances',
        summary: 'Track cash, card, and UPI payments, upload payment verification screenshots, and settle balances.',
        keywords: ['payment', 'amount paid', 'balance due', 'payment screenshot', 'cash', 'upi payment', 'partial payment'],
        actionRoute: 'job-cards.index',
        actionText: 'Go to Job Cards',
        steps: [
            'Open the Job Card from **Job Cards** → Click **View** or **Edit**.',
            'Locate the **Payment Status & Settlement** section.',
            'Enter the **Amount Paid** by the customer (supports partial payments; remaining balance is highlighted in red).',
            'Select the payment method: `Cash`, `UPI`, or `Card`.',
            'If paid via UPI or online transfer, you can upload the customer\'s **Payment Screenshot** for your records.',
            'Mark payment status as `Paid` once full payment is collected.'
        ],
        tip: 'The system automatically flags unpaid or partially paid jobs so you never lose track of outstanding client dues.'
    },
    {
        id: 'inventory-management',
        category: 'inventory',
        title: 'How to Add Spare Parts and Monitor Low Stock Alerts',
        summary: 'Manage your parts catalog, track cost vs selling prices, and receive low-stock notifications.',
        keywords: ['inventory', 'spare parts', 'stock', 'add part', 'part number', 'low stock', 'engine oil', 'filter'],
        actionRoute: 'inventory.index',
        actionText: 'Open Inventory',
        steps: [
            'Click **Inventory** in the sidebar.',
            'Click **+ Add Item**.',
            'Enter the **Part Name** (e.g., Mobil 1 5W40 Engine Oil) and **Part Number / SKU**.',
            'Select or create the **Category** (e.g., Lubricants, Brake Parts) and **Brand** (e.g., Bosch, Mobil).',
            'Set **Quantity on Hand**, **Minimum Stock Level** (e.g., 2 units), **Purchase Cost**, and **Selling Price**.',
            'Click **Save**.',
            'When stock drops to or below the minimum level, it will be highlighted in orange with a low-stock alert.'
        ],
        tip: 'You can use the **Bulk Import** button on the Inventory page to upload hundreds of parts at once using an Excel/CSV file.'
    },
    {
        id: 'purchase-invoices',
        category: 'inventory',
        title: 'How to Log Supplier Purchases (Auto Inventory Restock)',
        summary: 'Record incoming supplier bills and automatically update your shelf stock quantities.',
        keywords: ['purchase', 'supplier', 'vendor', 'purchase invoice', 'restock', 'distributor bill', 'stock in'],
        actionRoute: 'purchases.index',
        actionText: 'Go to Purchases',
        steps: [
            'Go to **Purchases** in the sidebar → Click **+ New Purchase**.',
            'Select or create your **Supplier** (e.g., Bosch Auto Spares).',
            'Enter Supplier Invoice Number and Purchase Date.',
            'Add the items purchased, quantities received, unit cost, and tax.',
            'Upload a photo/scan of the physical bill for accounting records.',
            'Set the invoice status to **Received**.',
            '**Result**: The system automatically adds the received quantities into your inventory without any manual data entry!'
        ],
        tip: 'Always attach photos of paper supplier bills so you have a digital backup for GST reconciliation.'
    },
    {
        id: 'add-mechanic-staff',
        category: 'staff',
        title: 'How to Add Mechanics and Workshop Staff Members',
        summary: 'Create login accounts for your technicians, service advisors, and front-desk employees.',
        keywords: ['add mechanic', 'staff', 'technician', 'user management', 'employee', 'role', 'team'],
        actionRoute: 'staff.index',
        actionText: 'Manage Staff',
        steps: [
            'Go to **User Management** in the sidebar.',
            'Click **+ Add Staff Member**.',
            'Enter their Full Name, Email, Phone Number, and a Password.',
            'Select their **Role**: `mechanic` (for repair technicians) or `staff` (for front-desk / managers).',
            'Click **Save**.',
            'The mechanic will now appear in the mechanic assignment dropdown when creating or updating job cards.'
        ],
        tip: 'If an employee leaves your workshop, click "Disable" to remove their access immediately while preserving their past repair logs.'
    },
    {
        id: 'online-service-bookings',
        category: 'bookings',
        title: 'How to Accept Online Bookings (Service Requests)',
        summary: 'Manage online vehicle appointment requests booked by customers from your public booking portal.',
        keywords: ['online booking', 'service requests', 'appointment', 'book service', 'approve request', 'public booking'],
        actionRoute: 'service-requests.index',
        actionText: 'View Service Requests',
        steps: [
            'Customers visit your public booking page (`/book`) and submit their vehicle details, requested date, and issue description.',
            'New requests appear immediately under **Service Requests** in your sidebar.',
            'Click **Service Requests** to view pending requests.',
            'Review the customer\'s preferred date and vehicle model.',
            'Click **Approve**: The system automatically converts the booking into a new Job Card with customer and vehicle details pre-filled!',
            'If you are fully booked, click **Decline** and specify a reason.'
        ],
        tip: 'Share your booking link (`https://cubestack.in/mow/book`) on your WhatsApp Business profile and Google Maps listing to attract online customers!'
    },
    {
        id: 'customer-portal',
        category: 'customers',
        title: 'How Customers Can Track Their Repair Status Online',
        summary: 'Provide your clients with real-time status updates, estimate reviews, and online payment proof uploads.',
        keywords: ['customer portal', 'track vehicle', 'client tracking', 'customer login', 'live status', 'repair progress'],
        actionRoute: 'customers.index',
        actionText: 'View Customers',
        steps: [
            'Whenever a customer is added to your CRM, an account is created using their phone number or email.',
            'Customers can log in at your CRM address using their registered phone/email.',
            'In their **Customer Dashboard**, they can see: Active Job Cards, Estimated Delivery Date, Work Status (Estimate, In Progress, Completed), and Itemized Cost.',
            'Customers can also upload their UPI payment screenshot directly from their phone when collecting the car.'
        ],
        tip: 'If a customer forgets their password, go to **Customers** in your sidebar and click **Reset Password** to send or assign them a new password.'
    },
    {
        id: 'vehicle-photos-inspection',
        category: 'job_cards',
        title: 'How to Upload Vehicle Inspection Photos (Before & After)',
        summary: 'Protect your workshop from disputes by documenting dents, scratches, and fuel level with photos.',
        keywords: ['inspection photos', 'photos', 'camera', 'dents', 'scratches', 'media gallery', 'before after'],
        actionRoute: 'job-cards.index',
        actionText: 'Go to Job Cards',
        steps: [
            'Open the vehicle Job Card (Create or View mode).',
            'Look for the **Media Gallery / Inspection Photos** section.',
            'Click **Choose / Camera** to select or capture photos of the vehicle.',
            'Select **Type**: `Before` (upon vehicle intake) or `After` (upon completion of repair/detailing).',
            'Add optional notes (e.g. "Existing scratch on left rear door").',
            'Photos are stored securely with the job card and can be viewed anytime by you or the customer.'
        ],
        tip: 'Always take a photo of the vehicle dashboard (fuel meter and odometer reading) before work starts.'
    },
    {
        id: 'counter-sales',
        category: 'billing',
        title: 'How to Do Direct Counter Sales (Over-The-Counter Billing)',
        summary: 'Quickly bill walk-in customers buying oils, filters, or accessories without opening a job card.',
        keywords: ['counter sales', 'sales', 'direct billing', 'retail sale', 'over the counter', 'spare parts sale'],
        actionRoute: 'sales.index',
        actionText: 'Open Counter Sales',
        steps: [
            'Click **Sales** in the sidebar.',
            'Click **+ New Sale**.',
            'Select parts from your inventory, enter quantity sold, and customer name (or Walk-in).',
            'Apply tax rate (GST) and choose payment method.',
            'Click **Complete Sale**.',
            'Print instant counter cash memo; sold quantities are automatically deducted from inventory.'
        ],
        tip: 'Use Counter Sales for quick sales of wiper blades, coolant cans, or battery replacements where vehicle check-in is not required.'
    },
    {
        id: 'discount-coupons',
        category: 'billing',
        title: 'How to Create and Apply Promotional Discount Coupons',
        summary: 'Run seasonal campaigns (e.g. Monsoon500) to attract repeat customers.',
        keywords: ['coupon', 'discount code', 'promotional code', 'percentage discount', 'festive offer'],
        actionRoute: 'coupons.index',
        actionText: 'Manage Coupons',
        steps: [
            'Click **Coupons** in the sidebar → Click **+ Create Coupon**.',
            'Enter a Code (e.g. `MONSOON500`, `FIRSTSERVICE`).',
            'Choose Type: `Fixed Amount` (e.g. ₹500 off) or `Percentage` (e.g. 10% off).',
            'Set Minimum Spend requirement and expiration date.',
            'Click **Save**.',
            'When creating a Job Card, enter this code in the **Coupon Code** field and click **Apply** to automatically discount the bill.'
        ],
        tip: 'Coupons with a minimum spend requirement (e.g. "₹500 off on bills above ₹3,000") are great for increasing average bill size!'
    },
    {
        id: 'ai-assistant',
        category: 'ai',
        title: 'How to Use the Garage AI Voice & Chat Assistant',
        summary: 'Speed up daily actions by speaking or typing natural commands to the built-in AI assistant.',
        keywords: ['ai assistant', 'voice command', 'microphone', 'speech to text', 'ai tools', 'bot'],
        actionRoute: 'dashboard',
        actionText: 'Go to Dashboard',
        steps: [
            'Click the floating circular lightning button at the bottom-right of any CRM screen.',
            'The **Garage AI Assistant** widget opens.',
            'Click the **Microphone** icon to speak, or type your command into the text box.',
            'Examples of what you can ask: \n  • *"Add 1 Oil Filter to vehicle MH02CD5678"*\n  • *"Check stock of 5W30 engine oil"*\n  • *"Add Yamaha R15 models to database"*',
            'The AI assistant will execute the task directly on your database and confirm the result!'
        ],
        tip: 'Make sure your microphone permissions are allowed in your browser to use voice commands.'
    },
    {
        id: 'day-1-checklist',
        category: 'setup',
        title: 'Day 1 Workshop Setup Checklist',
        summary: 'A fast 4-step checklist to get your workshop fully operational in under 10 minutes.',
        keywords: ['day 1', 'checklist', 'getting started', 'initial setup', 'new garage', 'quick start'],
        actionRoute: 'settings.edit',
        actionText: 'Start Setup',
        steps: [
            'Step 1: Go to **Settings** → Fill workshop name, address, GSTIN, and upload your **Logo** and **UPI QR Code**.',
            'Step 2: Go to **User Management** → Add your mechanics and staff accounts.',
            'Step 3: Go to **Inventory** → Add your top 10 most common parts (engine oils, oil filters, brake pads, coolant) or use Bulk CSV Import.',
            'Step 4: Go to **Service Types** → Set up your standard services (General Service, Water Wash, AC Service).',
            'You are now ready to create job cards and issue professional invoices!'
        ],
        tip: 'Bookmark your login page on your mobile phone and shop tablet for quick access throughout the day.'
    },
    // Dedicated Guides for Vehicle Owners (Customers)
    {
        id: 'customer-login-track',
        category: 'for_customers',
        title: 'Customer Guide: How to Log In & Track Your Vehicle Repair Live',
        summary: 'Check real-time repair progress, assigned technicians, estimate costs, and expected delivery date.',
        keywords: ['customer login', 'track vehicle', 'live repair status', 'repair progress', 'customer portal', 'check vehicle status', 'mechanic assigned', 'expected delivery'],
        actionRoute: 'dashboard',
        actionText: 'Open Customer Dashboard',
        steps: [
            'Open your workshop\'s CRM website on your phone or laptop browser.',
            'Log in using your registered mobile number or email address (your workshop creates this when you check in your vehicle).',
            'On your **My Dashboard**, you will immediately see: Active Jobs, Assigned Vehicle, and Total Lifetime Spent.',
            'Click on any Job Card Number (e.g., `JC-1002`) to view the real-time status: `Scheduled` ➔ `Working` ➔ `Completed` ➔ `Delivered`.',
            'View the itemized labor and parts breakdown, technician notes, and total bill estimate before pickup.'
        ],
        tip: 'Bookmark the login page on your smartphone home screen for 1-tap live repair tracking anytime!'
    },
    {
        id: 'customer-online-booking',
        category: 'for_customers',
        title: 'Customer Guide: How to Book a Service Appointment Online',
        summary: 'Schedule vehicle maintenance, select requested services, choose preferred date/time, and avoid waiting queues.',
        keywords: ['book service', 'online booking', 'service appointment', 'schedule repair', 'book appointment', 'raise request', 'car service', 'bike service'],
        actionRoute: 'service-requests.create',
        actionText: 'Book Service Online',
        steps: [
            'Click **Raise Service Request** from your dashboard (or visit the workshop\'s public `/book` link).',
            'Select or enter your vehicle details (Make, Model, Registration Number).',
            'Choose the required service package (e.g., General Service, Oil Change, Water Wash, Brake Overhaul).',
            'Select your preferred service date and arrival time slot.',
            'Describe any specific problems (e.g., "Squeaking sound from front left wheel when braking").',
            'Submit your booking. The workshop will review and approve your slot immediately.'
        ],
        tip: 'Booking your appointment 24 hours in advance ensures the workshop reserves dedicated technician bays and required filters.'
    },
    {
        id: 'customer-upi-payment',
        category: 'for_customers',
        title: 'Customer Guide: How to Pay Invoices via UPI QR Code & Upload Payment Proof',
        summary: 'Pay your bill directly from Google Pay, PhonePe, or Paytm and upload payment screenshots for instant gate pass clearance.',
        keywords: ['pay invoice', 'upi payment', 'upi qr code', 'phonepe', 'google pay', 'paytm', 'upload screenshot', 'payment proof', 'settle bill'],
        actionRoute: 'dashboard',
        actionText: 'View My Invoices',
        steps: [
            'Open your completed Job Card from your Customer Dashboard.',
            'Under the payment section or on the tax invoice, you will see the workshop\'s official **UPI ID** and **Verified UPI QR Code**.',
            'Open your preferred payment app (Google Pay, PhonePe, Paytm, BHIM) and scan the QR code.',
            'Complete the transfer for the exact invoice amount.',
            'Take a screenshot of the payment successful screen on your phone.',
            'Click **Upload Payment Screenshot** on your job card screen and select the image.',
            'The workshop manager instantly verifies your transaction and issues your vehicle delivery clearance.'
        ],
        tip: 'Uploading the screenshot saves you time at the cashier counter so you can pick up your keys without waiting.'
    },
    {
        id: 'customer-manage-vehicles',
        category: 'for_customers',
        title: 'Customer Guide: How to Add & Manage Your Vehicles',
        summary: 'Keep complete service history, oil change records, and invoices organized for all your family or commercial vehicles.',
        keywords: ['add vehicle', 'register vehicle', 'my cars', 'my bikes', 'vehicle profile', 'service records', 'registration number'],
        actionRoute: 'dashboard',
        actionText: 'Manage My Vehicles',
        steps: [
            'Log in to your Customer Dashboard.',
            'In the **My Vehicles** card, click **+ Add New**.',
            'Select your vehicle **Brand** (e.g., Maruti Suzuki, Hyundai, Honda, Yamaha).',
            'Select the specific **Model** (e.g., Swift, Creta, Activa, City).',
            'Enter your vehicle **Registration Number** (e.g., KA01AB1234) and year of manufacture.',
            'Click **Add Vehicle**.',
            'Your vehicle is now linked to your account, and all future repair orders will be organized under this profile.'
        ],
        tip: 'You can add multiple cars and 2-wheelers under one single phone number for whole-family vehicle tracking.'
    },
    {
        id: 'customer-profile-password',
        category: 'for_customers',
        title: 'Customer Guide: How to Update Your Profile & Reset Password',
        summary: 'Update your contact information, change your password, or recover access if you get locked out.',
        keywords: ['reset password', 'forgot password', 'change password', 'customer profile', 'update phone', 'edit profile'],
        actionRoute: 'profile.edit',
        actionText: 'My Profile Settings',
        steps: [
            'Click on your name in the top right navigation bar → Select **Profile**.',
            'Update your Name, Email Address, or Phone Number.',
            'Under **Update Password**, enter your current password followed by your new password, then click **Save**.',
            'Forgot your password? Simply inform the workshop front desk or call them; the workshop manager can reset your password with 1 click from their Customer CRM console.'
        ],
        tip: 'Keep your mobile number updated so you receive automatic service updates and bill notifications.'
    },
    // Advanced Workshop Guides
    {
        id: 'inventory-bulk-import',
        category: 'inventory',
        title: 'How to Bulk Import Spare Parts & Inventory via Excel / CSV',
        summary: 'Upload hundreds of spare parts, SKU codes, minimum stock limits, and prices in seconds.',
        keywords: ['bulk import', 'csv import', 'excel import', 'upload parts', 'import inventory', 'parts spreadsheet'],
        actionRoute: 'inventory.index',
        actionText: 'Go to Inventory',
        steps: [
            'Go to **Inventory** in the left sidebar menu.',
            'Click the **Bulk Import** button at the top of the table.',
            'Download the sample CSV template to see the required column format (Part Name, Part Number, Category, Brand, Purchase Price, Selling Price, Quantity, Min Stock).',
            'Fill in your supplier or workshop parts list in Excel and export as CSV.',
            'Click **Choose File**, select your `.csv` spreadsheet, and click **Import Parts**.',
            'The system will parse all rows and create categories, brands, and stock items automatically.'
        ],
        tip: 'Bulk import is especially helpful when migrating from an old software or setting up an existing workshop inventory on Day 1.'
    },
    {
        id: 'inventory-export-reports',
        category: 'inventory',
        title: 'How to Export Inventory & Stock Valuation to Excel / CSV',
        summary: 'Download complete stock spreadsheets for monthly audits, accountant reviews, and tax reporting.',
        keywords: ['export inventory', 'download stock', 'inventory excel', 'stock valuation', 'inventory report', 'stock audit'],
        actionRoute: 'inventory.index',
        actionText: 'Export Inventory',
        steps: [
            'Click **Inventory** in the sidebar.',
            'Click the **Export CSV** button in the header.',
            'Your browser will immediately download a `.csv` spreadsheet containing every part name, SKU number, quantity on hand, unit cost, selling price, and total stock valuation.',
            'Open the file in Microsoft Excel or Google Sheets for physical stock verification or sharing with your accountant.'
        ],
        tip: 'Perform a monthly physical stock count and compare with your exported sheet to catch discrepancies early.'
    },
    {
        id: 'vehicle-database-management',
        category: 'setup',
        title: 'How to Manage Vehicle Brands & Models Database',
        summary: 'Add custom vehicle brands and model variants so mechanics and advisors can easily select them.',
        keywords: ['vehicle database', 'vehicle brands', 'vehicle models', 'add brand', 'add model', 'car makes', 'bike models'],
        actionRoute: 'vehicle-brands.index',
        actionText: 'Manage Vehicle Database',
        steps: [
            'Go to **Vehicle Database** (or Brands / Models) in the sidebar.',
            'To add a new manufacturer (e.g. MG Motor, Royal Enfield, Ather, Ola), click **+ Add Brand**.',
            'To add specific model variants (e.g. Classic 350, Hunter 350), select the brand and click **+ Add Model**.',
            'These brands and models will now appear automatically in customer registration, job card creation, and online booking dropdowns.'
        ],
        tip: 'You can also create brand-new models directly inside the Job Card screen without having to navigate to this page.'
    }
];

const filteredGuides = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    const cat = selectedCategory.value;

    return guides.filter((item) => {
        // Category filter
        if (cat !== 'all' && item.category !== cat) {
            return false;
        }

        // Search query filter
        if (!q) return true;

        const titleMatch = item.title.toLowerCase().includes(q);
        const summaryMatch = item.summary.toLowerCase().includes(q);
        const keywordMatch = item.keywords.some((k) => k.toLowerCase().includes(q));
        const stepMatch = item.steps.some((s) => s.toLowerCase().includes(q));

        return titleMatch || summaryMatch || keywordMatch || stepMatch;
    });
});

const toggleItem = (id) => {
    openItem.value = openItem.value === id ? null : id;
};

const applySearchTag = (tag) => {
    searchQuery.value = tag;
    selectedCategory.value = 'all';
};

const printGuide = () => {
    window.print();
};
</script>

<template>
    <Head title="User Guide & Help Center" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto py-4 sm:py-8 px-2 sm:px-4">
            
            <!-- Hero Search Header -->
            <div class="bg-gradient-to-r from-primary-800 via-primary-700 to-gray-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl mb-8 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                    <svg class="w-80 h-80 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>

                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-3 text-primary-200">
                        📖 Workshop Knowledge Base
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-black tracking-tight mb-2">How can we help your garage today?</h1>
                    <p class="text-primary-100 text-xs sm:text-sm mb-6">Search step-by-step guides for creating job cards, adding customers, printing invoices, tracking stock, and more.</p>

                    <!-- Search Input -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Type a task or question (e.g. 'add customer', 'gst invoice', 'qr code', 'stock')..."
                            class="w-full rounded-2xl border-0 py-3.5 pl-12 pr-10 text-gray-900 shadow-2xl placeholder:text-gray-400 focus:ring-4 focus:ring-primary-400 text-sm sm:text-base font-medium"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Quick Search Tags -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-4 text-xs">
                        <span class="text-primary-200 font-semibold mr-1">Popular:</span>
                        <button
                            v-for="tag in quickSearchTags"
                            :key="tag"
                            @click="applySearchTag(tag)"
                            class="bg-white/15 hover:bg-white/25 text-white px-2.5 py-1 rounded-lg transition text-xs font-medium"
                        >
                            {{ tag }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Category Tabs (Full-width row, fully visible) -->
            <div class="mb-4">
                <div class="flex items-center sm:flex-wrap gap-2 overflow-x-auto pb-2 sm:pb-0 scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="selectedCategory = cat.id"
                        :class="[
                            selectedCategory === cat.id
                                ? 'bg-primary-700 text-white shadow-md ring-2 ring-primary-700/20'
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300',
                            'px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all'
                        ]"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Sub Toolbar with Results Count & Moved Print Button -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6 bg-gray-50/80 border border-gray-200/80 rounded-2xl p-3 sm:px-4 sm:py-2.5">
                <div class="text-xs sm:text-sm font-semibold text-gray-600 flex items-center gap-2">
                    <span v-if="searchQuery">
                        Showing {{ filteredGuides.length }} guide{{ filteredGuides.length === 1 ? '' : 's' }} for <strong class="text-primary-700">"{{ searchQuery }}"</strong>
                    </span>
                    <span v-else>
                        Showing <strong>{{ filteredGuides.length }}</strong> guides in 
                        <strong class="text-primary-700">{{ categories.find(c => c.id === selectedCategory)?.name || 'All' }}</strong>
                    </span>
                    <button v-if="searchQuery" @click="searchQuery = ''" class="ml-2 text-xs text-primary-600 hover:underline font-bold">
                        Clear
                    </button>
                </div>

                <!-- Print / Save as PDF button moved down here -->
                <button
                    @click="printGuide"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition shrink-0 print:hidden"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print / Save as PDF</span>
                </button>
            </div>

            <!-- Guides List -->
            <div class="space-y-4">
                <!-- Empty Search State -->
                <div v-if="filteredGuides.length === 0" class="bg-white rounded-2xl border border-gray-200 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">No guides found for "{{ searchQuery }}"</h3>
                    <p class="text-sm text-gray-500 max-w-sm mx-auto mb-4">Try searching for keywords like "customer", "job card", "invoice", "mechanic", or "inventory".</p>
                    <button
                        @click="searchQuery = ''; selectedCategory = 'all'"
                        class="px-4 py-2 bg-primary-700 text-white rounded-xl text-xs font-bold hover:bg-primary-600 transition"
                    >
                        View All Guides
                    </button>
                </div>

                <!-- Guide Cards -->
                <div
                    v-for="guide in filteredGuides"
                    :key="guide.id"
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-200 hover:border-gray-300"
                >
                    <!-- Card Header / Question -->
                    <div
                        @click="toggleItem(guide.id)"
                        class="p-5 sm:p-6 flex items-start justify-between gap-4 cursor-pointer select-none bg-white hover:bg-gray-50/70 transition-colors"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-100">
                                    {{ guide.category.replace('_', ' ') }}
                                </span>
                            </div>
                            <h2 class="text-base sm:text-lg font-bold text-gray-900">{{ guide.title }}</h2>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ guide.summary }}</p>
                        </div>

                        <!-- Expand / Collapse chevron -->
                        <div class="shrink-0 pt-1">
                            <div
                                :class="[
                                    openItem === guide.id ? 'bg-primary-100 text-primary-700 rotate-180' : 'bg-gray-100 text-gray-500',
                                    'w-8 h-8 rounded-full flex items-center justify-center transition-transform duration-200'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body / Step-by-step instructions -->
                    <div
                        v-show="openItem === guide.id || searchQuery.length > 2"
                        class="border-t border-gray-100 bg-gray-50/40 p-5 sm:p-6 space-y-4"
                    >
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Step-by-Step Instructions:</h4>
                        
                        <ol class="space-y-2.5 text-sm text-gray-700">
                            <li
                                v-for="(step, sIdx) in guide.steps"
                                :key="sIdx"
                                class="flex items-start gap-3 bg-white p-3 rounded-xl border border-gray-200 shadow-sm"
                            >
                                <span class="w-6 h-6 rounded-full bg-primary-600 text-white font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                                    {{ sIdx + 1 }}
                                </span>
                                <span class="leading-relaxed font-medium" v-html="step.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')"></span>
                            </li>
                        </ol>

                        <!-- Pro Tip -->
                        <div v-if="guide.tip" class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-start gap-3 text-amber-900 text-xs">
                            <span class="text-base shrink-0">💡</span>
                            <div>
                                <strong class="font-bold">Pro Tip:</strong> {{ guide.tip }}
                            </div>
                        </div>

                        <!-- Direct Shortcut Button -->
                        <div v-if="guide.actionRoute" class="pt-2 flex justify-end">
                            <Link
                                :href="route(guide.actionRoute)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-xs font-bold shadow-sm transition"
                            >
                                <span>{{ guide.actionText }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Support Box -->
            <div class="mt-12 bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 text-center shadow-sm">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Need help with something else?</h3>
                <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto mb-5">You can speak directly to your built-in AI Voice Assistant by clicking the lightning button in the bottom right corner.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <button
                        @click="printGuide"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs sm:text-sm font-bold rounded-xl shadow-sm transition print:hidden"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Full Guide (PDF)
                    </button>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-700 hover:bg-primary-600 text-white text-xs sm:text-sm font-bold rounded-xl shadow-sm transition"
                    >
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
