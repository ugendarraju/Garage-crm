<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const uiTotals = ref({ subtotal: 0, cgst: 0, sgst: 0, igst: 0 });

const props = defineProps({
    suppliers: Array,
    inventoryItems: Array,
});

const isModalOpen = ref(false);

const supplierForm = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    gst_number: '',
    balance: 0,
});

const submitSupplier = () => {
    supplierForm.post(route('suppliers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            supplierForm.reset();
        }
    });
};

const form = useForm({
    supplier_id: '',
    invoice_number: '',
    purchase_date: new Date().toISOString().split('T')[0],
    status: 'unpaid',
    notes: '',
    bill_image: null,
    payment_receipt: null,
    items: [
        { inventory_id: '', part_name: '', quantity: 1, unit_price: 0, gst_percent: 'None', total_price: 0 }
    ],
    total_amount: 0
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

const handleBillUpload = (event) => {
    form.bill_image = event.target.files[0];
};

const handleReceiptUpload = (event) => {
    form.payment_receipt = event.target.files[0];
};

const selectedSupplier = computed(() => {
    return props.suppliers.find(s => s.id === form.supplier_id) || null;
});

const submit = () => {
    // Inertia form.post automatically handles FormData when files are present
    form.post(route('purchases.store'));
};
</script>

<template>
    <Head title="New Purchase Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-1">
                        <Link :href="route('purchases.index')" class="hover:text-gray-900">Purchases</Link>
                        <svg class="h-4 w-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="text-gray-900 font-medium">New Invoice</span>
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Record Purchase Invoice</h1>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Invoice Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Invoice Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700">Supplier *</label>
                                    <button @click="isModalOpen = true" type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-900">+ Add Supplier</button>
                                </div>
                                <select v-model="form.supplier_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                    <option value="" disabled>Select a supplier...</option>
                                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                                </select>
                                <div v-if="selectedSupplier" class="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100">
                                    <div v-if="selectedSupplier.address"><span class="font-semibold text-gray-600">Address:</span> {{ selectedSupplier.address }}</div>
                                    <div v-if="selectedSupplier.gst_number" class="mt-1"><span class="font-semibold text-gray-600">GST:</span> {{ selectedSupplier.gst_number }}</div>
                                </div>
                                <div v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Invoice Number *</label>
                                <input v-model="form.invoice_number" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                <div v-if="form.errors.invoice_number" class="text-red-500 text-xs mt-1">{{ form.errors.invoice_number }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Purchase Date *</label>
                                <input v-model="form.purchase_date" type="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                <div v-if="form.errors.purchase_date" class="text-red-500 text-xs mt-1">{{ form.errors.purchase_date }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Status *</label>
                                <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partial">Partial</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bill Image / Attachment</label>
                                <input type="file" @change="handleBillUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <div v-if="form.errors.bill_image" class="text-red-500 text-xs mt-1">{{ form.errors.bill_image }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Receipt / Screenshot</label>
                                <input type="file" @change="handleReceiptUpload" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                <div v-if="form.errors.payment_receipt" class="text-red-500 text-xs mt-1">{{ form.errors.payment_receipt }}</div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
                            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm" placeholder="Any payment references or notes..."></textarea>
                            <div v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Procured Items</h2>
                                <p class="text-xs text-gray-500 font-normal mt-0.5">Select an existing inventory part to restock, or choose "+ New Part" and type its name to auto-register it into inventory.</p>
                            </div>
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
                                                <option value="">+ New Part (Type Name)</option>
                                                <option v-for="inv in inventoryItems" :key="inv.id" :value="inv.id">{{ inv.part_name }} (In Stock: {{ inv.current_stock || 0 }})</option>
                                            </select>
                                        </td>
                                        <td class="py-2 pr-2 align-top">
                                            <input v-model="item.part_name" :disabled="!!item.inventory_id" :required="!item.inventory_id" type="text" :placeholder="item.inventory_id ? 'Selected from Inventory' : 'Type new part name...'" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm disabled:bg-gray-100 disabled:text-gray-600">
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

                    <!-- Submit -->
                    <div class="flex justify-end gap-3">
                        <Link :href="route('purchases.index')" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2 bg-green-600 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Save Invoice & Update Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Supplier Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitSupplier">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Add New Supplier</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Company Name *</label>
                                    <input v-model="supplierForm.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                                    <input v-model="supplierForm.contact_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input v-model="supplierForm.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input v-model="supplierForm.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Registered Address</label>
                                    <textarea v-model="supplierForm.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">GST Number</label>
                                    <input v-model="supplierForm.gst_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="supplierForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-medium text-white hover:bg-gray-800 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Save Supplier
                            </button>
                            <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
