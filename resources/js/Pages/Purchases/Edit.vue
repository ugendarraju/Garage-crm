<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const uiTotals = ref({ subtotal: 0, cgst: 0, sgst: 0, igst: 0 });

const props = defineProps({
    invoice: Object,
    suppliers: Array,
    inventoryItems: Array,
});

const form = useForm({
    invoice_number: props.invoice.invoice_number,
    supplier_id: props.invoice.supplier_id,
    purchase_date: props.invoice.purchase_date,
    status: props.invoice.status,
    notes: props.invoice.notes || '',
    bill_image: null,
    payment_receipt: null,
    total_amount: props.invoice.total_amount,
    items: props.invoice.items.map(item => ({
        inventory_id: item.inventory_id || '',
        part_name: item.part_name || '',
        quantity: item.quantity,
        unit_price: item.unit_price,
        gst_percent: item.gst_percent || 'None',
        total_price: item.total_price,
    })),
    _method: 'PUT'
});

const addItemRow = () => {
    form.items.push({ inventory_id: '', part_name: '', quantity: 1, unit_price: 0, gst_percent: 'None', total_price: 0 });
};

const removeItemRow = (index) => {
    form.items.splice(index, 1);
    calculateTotals();
};

const handleItemSelect = (index) => {
    const item = form.items[index];
    if (item.inventory_id) {
        const inv = props.inventoryItems.find(i => i.id === item.inventory_id);
        if (inv) {
            item.part_name = inv.part_name;
        }
    }
    calculateTotals();
};

const calculateTotals = () => {
    let sub = 0;
    let cgst = 0;
    let sgst = 0;
    let igst = 0;

    form.items.forEach(item => {
        let basePrice = Number(item.quantity) * Number(item.unit_price);
        sub += basePrice;
        
        let taxAmount = 0;
        if (item.gst_percent && item.gst_percent !== 'None') {
            if (item.gst_percent.includes('CGST')) {
                let match = item.gst_percent.match(/CGST (\d+(\.\d+)?)%/);
                if (match) {
                    let rate = Number(match[1]);
                    taxAmount = basePrice * (rate * 2 / 100);
                    cgst += basePrice * (rate / 100);
                    sgst += basePrice * (rate / 100);
                }
            } else if (item.gst_percent.includes('IGST')) {
                let match = item.gst_percent.match(/IGST (\d+(\.\d+)?)%/);
                if (match) {
                    let rate = Number(match[1]);
                    taxAmount = basePrice * (rate / 100);
                    igst += basePrice * (rate / 100);
                }
            }
        }
        item.total_price = basePrice + taxAmount;
    });

    form.total_amount = sub + cgst + sgst + igst;
    uiTotals.value = { subtotal: sub, cgst, sgst, igst };
};

// initialize uiTotals
calculateTotals();

const selectedSupplier = computed(() => {
    return props.suppliers.find(s => s.id === form.supplier_id) || null;
});

const handleBillUpload = (event) => {
    form.bill_image = event.target.files[0];
};

const handleReceiptUpload = (event) => {
    form.payment_receipt = event.target.files[0];
};

const submit = () => {
    form.post(route('purchases.update', props.invoice.id));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount || 0);
};
</script>

<template>
    <Head title="Edit Purchase Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-1">
                        <Link :href="route('purchases.index')" class="hover:text-gray-900">Purchases</Link>
                        <svg class="h-4 w-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="text-gray-900 font-medium">Edit Invoice</span>
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Edit Purchase Invoice</h1>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Invoice Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Invoice Number</label>
                                <input v-model="form.invoice_number" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                <div v-if="form.errors.invoice_number" class="text-red-500 text-xs mt-1">{{ form.errors.invoice_number }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Supplier</label>
                                <select v-model="form.supplier_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                    <option value="" disabled>Select a supplier...</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                                </select>
                                <div v-if="selectedSupplier" class="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100">
                                    <div v-if="selectedSupplier.address"><span class="font-semibold text-gray-600">Address:</span> {{ selectedSupplier.address }}</div>
                                    <div v-if="selectedSupplier.gst_number" class="mt-1"><span class="font-semibold text-gray-600">GST:</span> {{ selectedSupplier.gst_number }}</div>
                                </div>
                                <div v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                <input v-model="form.purchase_date" type="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                <div v-if="form.errors.purchase_date" class="text-red-500 text-xs mt-1">{{ form.errors.purchase_date }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Total Amount</label>
                                <div class="mt-1 font-bold text-gray-900 text-lg">{{ formatCurrency(form.total_amount) }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                                <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partial">Partial</option>
                                    <option value="paid">Paid</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bill Image / Attachment</label>
                                <input type="file" @change="handleBillUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <div v-if="invoice.bill_image_path" class="mt-2 text-sm">
                                    <a :href="`/storage/${invoice.bill_image_path}`" target="_blank" class="text-blue-600 hover:text-blue-900">View Current Attachment</a>
                                </div>
                                <div v-if="form.errors.bill_image" class="text-red-500 text-xs mt-1">{{ form.errors.bill_image }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Receipt / Screenshot</label>
                                <input type="file" @change="handleReceiptUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <div v-if="invoice.payment_receipt_path" class="mt-2 text-sm">
                                    <a :href="`/storage/${invoice.payment_receipt_path}`" target="_blank" class="text-blue-600 hover:text-blue-900">View Current Receipt</a>
                                </div>
                                <div v-if="form.errors.payment_receipt" class="text-red-500 text-xs mt-1">{{ form.errors.payment_receipt }}</div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
                            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm" placeholder="Any payment references or notes..."></textarea>
                            <div v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</div>
                        </div>
                    </div>
                    
                    <!-- Editable Purchased Items -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold text-gray-900">Purchased Items</h2>
                            <button type="button" @click="addItemRow" class="text-sm font-bold text-blue-600 hover:text-blue-800">+ Add Row</button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2 w-1/4">Inventory Part</th>
                                        <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2 w-1/4">Part Name</th>
                                        <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2 w-24">Qty</th>
                                        <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2 w-32">Unit Price (₹)</th>
                                        <th class="text-left text-xs font-medium text-gray-500 uppercase pb-2 w-48">GST Option</th>
                                        <th class="text-right text-xs font-medium text-gray-500 uppercase pb-2 w-32">Total (₹)</th>
                                        <th class="pb-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="py-2 pr-2 align-top">
                                            <select v-model="item.inventory_id" @change="handleItemSelect(index)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                                <option value="">-- Custom Part --</option>
                                                <option v-for="inv in inventoryItems" :key="inv.id" :value="inv.id">{{ inv.part_name }}</option>
                                            </select>
                                        </td>
                                        <td class="py-2 pr-2 align-top">
                                            <input v-model="item.part_name" :disabled="!!item.inventory_id" :required="!item.inventory_id" type="text" placeholder="Enter name..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm disabled:bg-gray-100">
                                        </td>
                                        <td class="py-2 pr-2 align-top">
                                            <input v-model="item.quantity" @input="calculateTotals" type="number" step="0.01" required min="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                        </td>
                                        <td class="py-2 pr-2 align-top">
                                            <input v-model="item.unit_price" @input="calculateTotals" type="number" step="0.01" required min="0" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                        </td>
                                        <td class="py-2 pr-2 align-top">
                                            <select v-model="item.gst_percent" @change="calculateTotals" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                                <option value="None">None (0%)</option>
                                                <option value="CGST 2.5% + SGST 2.5%">CGST 2.5% + SGST 2.5%</option>
                                                <option value="IGST 5%">IGST 5%</option>
                                                <option value="CGST 6% + SGST 6%">CGST 6% + SGST 6%</option>
                                                <option value="IGST 12%">IGST 12%</option>
                                                <option value="CGST 9% + SGST 9%">CGST 9% + SGST 9%</option>
                                                <option value="IGST 18%">IGST 18%</option>
                                                <option value="CGST 14% + SGST 14%">CGST 14% + SGST 14%</option>
                                                <option value="IGST 28%">IGST 28%</option>
                                                <option v-if="!['None', 'CGST 2.5% + SGST 2.5%', 'IGST 5%', 'CGST 6% + SGST 6%', 'IGST 12%', 'CGST 9% + SGST 9%', 'IGST 18%', 'CGST 14% + SGST 14%', 'IGST 28%'].includes(item.gst_percent)" :value="item.gst_percent">{{ item.gst_percent }} (Legacy)</option>
                                            </select>
                                        </td>
                                        <td class="py-2 pr-2 align-top text-right">
                                            <div class="font-bold text-gray-900 pt-2 text-base">₹{{ (item.total_price || 0).toFixed(2) }}</div>
                                        </td>
                                        <td class="py-2 text-right align-top">
                                            <button type="button" @click="removeItemRow(index)" v-if="form.items.length > 1" class="text-red-500 hover:text-red-700 pt-2">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="form.errors.items" class="text-red-500 text-xs mt-2">{{ form.errors.items }}</div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <div class="w-80 bg-gray-50 rounded-lg p-5 border border-gray-200 shadow-sm">
                                <div class="flex justify-between items-center text-sm mb-2 text-gray-600">
                                    <span>Subtotal</span>
                                    <span>₹{{ (uiTotals.subtotal || 0).toFixed(2) }}</span>
                                </div>
                                <div v-if="uiTotals.cgst > 0" class="flex justify-between items-center text-sm mb-2 text-gray-500">
                                    <span>Total CGST</span>
                                    <span>₹{{ uiTotals.cgst.toFixed(2) }}</span>
                                </div>
                                <div v-if="uiTotals.sgst > 0" class="flex justify-between items-center text-sm mb-2 text-gray-500">
                                    <span>Total SGST</span>
                                    <span>₹{{ uiTotals.sgst.toFixed(2) }}</span>
                                </div>
                                <div v-if="uiTotals.igst > 0" class="flex justify-between items-center text-sm mb-2 text-gray-500">
                                    <span>Total IGST</span>
                                    <span>₹{{ uiTotals.igst.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xl font-black text-gray-900 border-t border-gray-200 pt-3 mt-3">
                                    <span>Grand Total</span>
                                    <span>₹{{ (form.total_amount || 0).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-6">
                        <Link :href="route('purchases.index')" class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
