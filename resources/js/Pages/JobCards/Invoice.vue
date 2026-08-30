<script setup>
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { onMounted, computed } from 'vue';

const props = defineProps({
    jobCard: Object,
});

const subtotal = computed(() => {
    return props.jobCard.items ? props.jobCard.items.reduce((total, item) => total + (parseFloat(item.unit_price) * item.quantity), 0) : 0;
});

const gstTotal = computed(() => {
    return props.jobCard.items ? props.jobCard.items.reduce((total, item) => total + (parseFloat(item.total) - (parseFloat(item.unit_price) * item.quantity)), 0) : 0;
});

const finalTotal = computed(() => {
    return props.jobCard.items ? props.jobCard.items.reduce((total, item) => total + parseFloat(item.total), 0) : 0;
});

onMounted(() => {
    // Automatically trigger print dialog when page loads
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Invoice" />

    <div class="invoice-container min-h-screen bg-gray-100 py-8 font-sans">
        <!-- Print Action Bar (Hidden when printing) -->
        <div class="max-w-5xl mx-auto mb-6 print:hidden flex justify-end gap-3 px-4 sm:px-0">
            <button onclick="window.close()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg shadow-sm text-sm font-bold hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">
                Close
            </button>
            <button onclick="window.print()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg shadow-md text-sm font-bold hover:bg-indigo-700 transition-colors flex items-center gap-2 focus:ring-4 focus:ring-indigo-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Invoice
            </button>
        </div>

        <!-- Invoice Paper -->
        <div class="max-w-5xl mx-auto bg-white shadow-2xl rounded-lg overflow-hidden print:shadow-none print:w-full print:max-w-none print:m-0 print:rounded-none">
            
            <!-- Top Color Bar -->
            <div class="h-3 bg-indigo-600 w-full print:bg-indigo-600"></div>

            <div class="p-10 sm:p-16 print:p-0">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row print:flex-row justify-between items-start pb-8 print:pb-4 border-b border-gray-200">
                    <div class="mb-6 md:mb-0 print:mb-0">
                        <div class="flex items-center gap-3 mb-3 print:mb-2">
                            <template v-if="jobCard.garage.logo_path">
                                <img :src="`/storage/${jobCard.garage.logo_path}`" :style="{ height: (jobCard.garage.header_logo_size || 48) + 'px' }" class="object-contain" alt="Garage Logo" />
                            </template>
                            <template v-else>
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm print:bg-indigo-600">
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </template>
                            <span class="text-3xl font-black text-gray-900 tracking-tight">{{ jobCard.garage.name }}</span>
                        </div>
                        <p class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 print:mb-1" v-if="jobCard.garage.tagline">{{ jobCard.garage.tagline }}</p>
                        
                        <template v-if="jobCard.garage.address">
                            <p class="text-sm text-gray-600 whitespace-pre-line max-w-xs mb-2 print:mb-1">{{ jobCard.garage.address }}</p>
                        </template>
                        <template v-else>
                            <p class="text-sm text-gray-600 mb-2 print:mb-1">Please update garage address in Settings.</p>
                        </template>

                        <p class="text-sm text-gray-600 mt-1 font-medium" v-if="jobCard.garage.phone || jobCard.garage.owner?.phone">{{ jobCard.garage.phone || jobCard.garage.owner?.phone }}</p>
                        <p class="text-sm text-gray-600 font-medium" v-if="jobCard.garage.email || jobCard.garage.owner?.email">{{ jobCard.garage.email || jobCard.garage.owner?.email }}</p>
                        
                        <p class="text-sm text-gray-600 font-bold mt-2 print:mt-1" v-if="jobCard.garage.gst_number">GSTIN: {{ jobCard.garage.gst_number }}</p>
                    </div>
                    <div class="text-left md:text-right print:text-right">
                        <h1 class="text-4xl font-black text-indigo-900 uppercase tracking-widest mb-4 print:mb-2">TAX INVOICE</h1>
                        <table class="w-full md:w-auto print:w-auto ml-0 md:ml-auto print:ml-auto text-sm">
                            <tbody>
                                <tr>
                                    <td class="py-1 pr-4 text-gray-500 font-bold text-left md:text-right print:text-right">Invoice No:</td>
                                    <td class="py-1 text-gray-900 font-bold text-right">{{ jobCard.job_number }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 text-gray-500 font-bold text-left md:text-right print:text-right">Invoice Date:</td>
                                    <td class="py-1 text-gray-900 font-bold text-right">{{ jobCard.delivered_at ? format(new Date(jobCard.delivered_at), 'MMM dd, yyyy') : (jobCard.status === 'delivered' ? format(new Date(jobCard.updated_at), 'MMM dd, yyyy') : format(new Date(jobCard.created_at), 'MMM dd, yyyy')) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 text-gray-500 font-bold text-left md:text-right print:text-right">Status:</td>
                                    <td class="py-1 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wider">
                                            {{ jobCard.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer & Vehicle -->
                <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-10 print:gap-4 py-8 print:py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3 print:mb-1">Billed To:</h3>
                        <p class="font-black text-gray-900 text-xl mb-1">{{ jobCard.customer?.user?.name || 'Walk-in Customer' }}</p>
                        <p class="text-sm text-gray-600 font-medium">{{ jobCard.customer?.user?.phone || 'N/A' }}</p>
                        <p class="text-sm text-gray-500">{{ jobCard.customer?.user?.email }}</p>
                    </div>
                    <div>
                        <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100 h-full">
                            <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3 print:mb-1">Vehicle Details:</h3>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="border-2 border-gray-900 rounded bg-white flex items-center overflow-hidden">
                                    <div class="bg-blue-600 w-4 h-full flex flex-col items-center justify-center py-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-yellow-400 mb-0.5"></div>
                                        <span class="text-[5px] font-bold text-white leading-none">IND</span>
                                    </div>
                                    <span class="px-2 font-black text-gray-900 tracking-wider text-sm">{{ jobCard.vehicle?.registration_number || 'UNKNOWN' }}</span>
                                </div>
                            </div>
                            <p class="font-bold text-gray-900">{{ jobCard.vehicle?.brand?.name }} {{ jobCard.vehicle?.vehicle_model?.name }}</p>
                            <p class="text-sm text-gray-600">Odometer: {{ jobCard.vehicle_running_km || 'N/A' }} KM</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="py-8 print:py-4">
                    <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-4 print:mb-2">Job Description</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 print:bg-gray-100">
                                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider rounded-l-lg">Item / Service</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Qty</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Unit Price</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">GST</th>
                                    <th class="py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right rounded-r-lg">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in jobCard.items" :key="item.id" class="group hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <p class="font-bold text-gray-900">{{ item.item_name }}</p>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ item.item_type }}</p>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-700 font-medium">{{ item.quantity }}</td>
                                    <td class="py-4 px-4 text-right text-gray-700">₹{{ parseFloat(item.unit_price).toFixed(2) }}</td>
                                    <td class="py-4 px-4 text-right">
                                        <p class="text-gray-900 font-medium">{{ item.gst_percent }}%</p>
                                        <p class="text-xs text-gray-500">₹{{ (parseFloat(item.total) - (parseFloat(item.unit_price) * item.quantity)).toFixed(2) }}</p>
                                    </td>
                                    <td class="py-4 px-4 text-right font-bold text-gray-900">₹{{ parseFloat(item.total).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals & Payment -->
                <div class="flex flex-col md:flex-row print:flex-row justify-between items-start border-t border-gray-200 pt-8 print:pt-4 mt-4 print:mt-0 break-inside-avoid">
                    <div class="mb-8 md:mb-0 print:mb-0 flex-1 pr-0 md:pr-8 print:pr-8 flex flex-row gap-6 print:gap-4">
                        <div class="flex-1">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 print:mb-1">Payment Terms & Notes</h4>
                            <p class="text-xs text-gray-600 mb-4 print:mb-2 text-justify">Payment is due upon completion of services. All parts remain the property of {{ jobCard.garage.name }} until full payment is received.</p>
                            
                            <template v-if="jobCard.garage.bank_details || jobCard.garage.upi_id">
                                <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 print:mb-1">Payment Details</h4>
                                <div class="text-xs text-gray-600">
                                    <p class="whitespace-pre-line mb-1" v-if="jobCard.garage.bank_details">{{ jobCard.garage.bank_details }}</p>
                                    <p v-if="jobCard.garage.upi_id"><span class="font-bold text-gray-700">UPI:</span> {{ jobCard.garage.upi_id }}</p>
                                </div>
                            </template>
                        </div>
                        <div v-if="jobCard.garage.qr_code_path" class="flex-shrink-0 text-center">
                            <img :src="`/storage/${jobCard.garage.qr_code_path}`" class="h-32 w-32 print:h-28 print:w-28 object-contain border rounded p-1.5 mx-auto bg-white" alt="Scan to Pay" />
                            <p class="text-[10px] font-bold text-gray-500 uppercase mt-2">Scan to Pay</p>
                        </div>
                    </div>
                    
                    <div class="w-full md:w-80 print:w-72 flex-shrink-0">
                        <div class="bg-gray-50 rounded-xl p-5 print:p-4 border border-gray-100">
                            <div class="flex justify-between items-center mb-3 print:mb-2">
                                <span class="text-sm text-gray-600 font-medium">Subtotal</span>
                                <span class="text-sm font-bold text-gray-900">₹{{ subtotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4 print:mb-2 pb-4 print:pb-2 border-b border-gray-200" v-if="jobCard.discount_amount && parseFloat(jobCard.discount_amount) > 0">
                                <span class="text-sm text-green-600 font-medium">Discount</span>
                                <span class="text-sm font-bold text-green-600">-₹{{ parseFloat(jobCard.discount_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4 print:mb-3 pb-4 print:pb-3 border-b border-gray-200">
                                <span class="text-sm text-gray-600 font-medium">Total GST</span>
                                <span class="text-sm font-bold text-gray-900">₹{{ gstTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-lg print:text-base font-black text-indigo-900 uppercase">Grand Total</span>
                                <span class="text-2xl print:text-xl font-black text-indigo-600 tracking-tight">₹{{ finalTotal.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-16 print:mt-8 pt-8 print:pt-4 border-t border-gray-200 flex flex-col md:flex-row print:flex-row justify-between items-center text-center md:text-left print:text-left gap-4 break-inside-avoid">
                    <div>
                        <p class="text-base font-black text-gray-900 mb-1">Thank you for your business!</p>
                        <p class="text-xs text-gray-500 font-medium">Terms & Conditions apply.</p>
                    </div>
                    <div class="text-right hidden md:block print:block">
                        <div class="h-16 w-32 border-b-2 border-gray-800 mb-2"></div>
                        <p class="text-xs font-bold text-gray-600 uppercase tracking-wider">Authorized Signatory</p>
                    </div>
                </div>

            </div>
            
            <!-- Bottom Color Bar -->
            <div class="h-2 bg-gray-900 w-full print:bg-gray-900"></div>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        width: 100%;
        height: 100%;
    }
    @page {
        margin: 0.5cm;
        size: A4 portrait;
    }
    .invoice-container {
        padding: 0 !important;
        margin: 0 !important;
        background-color: white !important;
    }
}
</style>
