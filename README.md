# Garage CRM

Garage CRM is a comprehensive smart management system for automobile garages and workshops. It streamlines daily operations, from service requests and job cards to inventory management and sales tracking.

## Key Features

- **Role-Based Access Control**: Tailored dashboards for Garage Owners, Mechanics, and Customers.
- **Job Card Management**: Create, edit, and track job cards with detailed service tracking and mechanic assignments.
- **Customer Portal**: Customers can view their service requests, approve estimates, view invoices, and upload payment screenshots.
- **Inventory & Purchases**: Manage spare parts, track suppliers, and maintain purchase invoices.
- **Sales & Revenue Dashboard**: Real-time sales tracking, invoice management, and payment status toggles.
- **Dynamic Invoices**: Beautiful, print-ready invoices with dynamic QR codes and customizable garage details.
- **Coupon System**: Apply discounts and promotional codes directly to job cards.

## Tech Stack

- **Backend**: [Laravel 11](https://laravel.com)
- **Frontend**: [Vue 3](https://vuejs.org/) & [Inertia.js](https://inertiajs.com/)
- **Styling**: [Tailwind CSS](https://tailwindcss.com/)
- **Database**: SQLite (Configured for local development and rapid deployment)

## Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/ugendarraju/Garage-crm.git
   cd Garage-crm
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node dependencies:
   ```bash
   npm install
   ```

4. Set up your environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Run migrations and seeders to populate initial data:
   ```bash
   php artisan migrate:fresh --seed
   ```

6. Build the frontend assets:
   ```bash
   npm run build
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
