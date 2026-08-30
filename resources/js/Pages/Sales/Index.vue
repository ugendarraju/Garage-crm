<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    sales: Object,
    totalRevenue: Number,
    filters: Object,
});

const search = ref(props.filters.search || '');
const month = ref(props.filters.month || '');

// Search & Filter functionality
let searchTimeout;
watch([search, month], ([newSearch, newMonth]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('sales.index'), { search: newSearch, month: newMonth }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Sales Tracking" />

    <AuthenticatedLayout>
        <div class="px-2">
            <!-- Header section -->
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Sales & Revenue</h1>
                <p class="mt-1 text-sm text-gray-500 font-medium">Track your delivered jobs and total revenue.</p>
            </div>

            <!-- Total Revenue Summary Card -->
            <div class="mb-8">
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-600 to-indigo-700 p-8 shadow-lg flex flex-col justify-between">
                    <div class="absolute right-0 top-0 opacity-10">
                        <svg class="h-48 w-48 -mr-8 -mt-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.64-2.25 1.64-1.74 0-2.1-.96-2.17-1.92H8.01c.09 1.7 1.36 2.85 2.89 3.2V19h2.34v-1.67c1.65-.3 2.85-1.36 2.85-2.93 0-2.36-2.02-3.08-3.78-3.54z"/>
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <dt class="text-sm font-bold text-primary-100 uppercase tracking-wider mb-2">Total Sales Revenue</dt>
                        <dd class="flex items-baseline gap-2">
                            <span class="text-5xl font-black text-white">₹{{ Number(totalRevenue || 0).toLocaleString('en-IN') }}</span>
                        </dd>
                        <p class="text-primary-200 text-sm mt-3 font-medium flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Calculated from all delivered job cards.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sales List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-900">Completed Sales</h2>
                    <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                        <div class="relative">
                            <input v-model="month" type="month" class="block w-full sm:w-48 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm shadow-sm transition-colors text-gray-700" />
                        </div>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="search" type="text" class="block w-full sm:w-72 rounded-lg border-gray-300 pl-10 focus:border-primary-500 focus:ring-primary-500 sm:text-sm shadow-sm transition-colors" placeholder="Search invoice, customer or vehicle..." />
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-4 font-semibold max-w-[150px]">Invoice #</th>
                                <th scope="col" class="px-4 py-4 font-semibold">Customer</th>
                                <th scope="col" class="px-4 py-4 font-semibold">Vehicle</th>
                                <th scope="col" class="px-4 py-4 font-semibold">Delivery Date</th>
                                <th scope="col" class="px-4 py-4 font-semibold">Payment</th>
                                <th scope="col" class="px-4 py-4 font-semibold text-right">Total</th>
                                <th scope="col" class="px-4 py-4 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap max-w-[150px]">
                                    <Link :href="route('job-cards.show', sale.id) + '?from=sales'" class="font-bold text-indigo-600 hover:text-indigo-900 underline block truncate" :title="sale.job_number">{{ sale.job_number }}</Link>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                                            {{ sale.customer?.user?.name ? sale.customer.user.name.charAt(0).toUpperCase() : 'U' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ sale.customer?.user?.name || 'Walk-in' }}</div>
                                            <div class="text-xs text-gray-500">{{ sale.customer?.user?.phone || 'No phone' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex w-fit items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            {{ sale.vehicle?.brand?.name }} {{ sale.vehicle?.vehicle_model?.name }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-900 uppercase">{{ sale.vehicle?.registration_number || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-gray-500 font-medium">
                                    {{ formatDate(sale.updated_at) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <button @click="router.patch(route('job-cards.payment-status', sale.id), { payment_status: sale.payment_status === 'paid' ? 'pending' : 'paid' }, { preserveScroll: true })" 
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold transition-colors cursor-pointer"
                                            :class="sale.payment_status === 'paid' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'">
                                        {{ sale.payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
                                        <svg class="ml-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </button>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">
                                    <span class="font-black text-gray-900 text-base">₹{{ Number(sale.final_total || 0).toLocaleString('en-IN') }}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right flex justify-end gap-2">
                                    <Link :href="route('job-cards.edit', sale.id) + '?from=sales'" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-primary-700 shadow-sm hover:bg-primary-50 transition-colors">
                                        <svg class="h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit
                                    </Link>
                                    <a :href="route('job-cards.invoice', sale.id)" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-900 shadow-sm hover:bg-gray-50 transition-colors">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="sales.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No Sales Found</h3>
                                    <p class="mt-1 text-sm text-gray-500">No delivered job cards match your criteria.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="sales.links && sales.links.length > 3" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ sales.from }}</span>
                                    to
                                    <span class="font-medium">{{ sales.to }}</span>
                                    of
                                    <span class="font-medium">{{ sales.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <Link v-for="(link, index) in sales.links" :key="index" :href="link.url || '#'" 
                                        v-html="link.label"
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0 transition-colors"
                                        :class="[
                                            link.active ? 'z-10 bg-primary-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600' : 'text-gray-900 hover:bg-gray-50',
                                            !link.url ? 'text-gray-400 cursor-not-allowed' : '',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === sales.links.length - 1 ? 'rounded-r-md' : ''
                                        ]"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
