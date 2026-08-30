<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import AIAssistantWidget from '@/Components/AIAssistantWidget.vue';

const props = defineProps({
    inventory: Array,
    categories: Array,
    brands: Array
});

const isModalOpen = ref(false);
const isBulkModalOpen = ref(false);
const isCategoryModalOpen = ref(false);
const isBrandModalOpen = ref(false);
const isStockModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    part_name: '',
    part_code: '',
    category: '',
    brand: '',
    current_stock: 0,
    min_stock_level: 5,
    current_price: 0,
    gst_rate: 18,
    location: '',
});

const openEditModal = (item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.part_name = item.part_name;
    form.part_code = item.part_code || '';
    form.category = item.category || '';
    form.brand = item.brand || '';
    form.current_stock = item.current_stock;
    form.min_stock_level = item.min_stock_level;
    form.current_price = item.current_price;
    isModalOpen.value = true;
};

const openAddModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('inventory.update', editingId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('inventory.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const stockForm = useForm({
    id: null,
    amount: 0,
    type: 'add'
});

const openStockModal = (item) => {
    stockForm.reset();
    stockForm.id = item.id;
    isStockModalOpen.value = true;
};

const submitStock = () => {
    stockForm.put(route('inventory.update', stockForm.id), {
        onSuccess: () => {
            isStockModalOpen.value = false;
        }
    });
};

const deleteItem = (id) => {
    if (confirm('Are you sure you want to delete this item?')) {
        useForm({}).delete(route('inventory.destroy', id), {
            preserveScroll: true
        });
    }
};

const bulkForm = useForm({
    file: null
});

const submitBulk = () => {
    bulkForm.post(route('inventory.bulk-import'), {
        onSuccess: () => {
            isBulkModalOpen.value = false;
            bulkForm.reset();
        }
    });
};

const exportCsv = () => {
    window.location.href = route('inventory.export');
};

const categoryForm = useForm({
    name: '',
    description: ''
});

const submitCategory = () => {
    categoryForm.post(route('part-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
        }
    });
};

const deleteCategory = (id) => {
    if (confirm('Are you sure you want to delete this category?')) {
        categoryForm.delete(route('part-categories.destroy', id), {
            preserveScroll: true
        });
    }
};

const brandForm = useForm({
    name: ''
});

const submitBrand = () => {
    brandForm.post(route('part-brands.store'), {
        preserveScroll: true,
        onSuccess: () => {
            brandForm.reset();
        }
    });
};

const deleteBrand = (id) => {
    if (confirm('Are you sure you want to delete this brand?')) {
        brandForm.delete(route('part-brands.destroy', id), {
            preserveScroll: true
        });
    }
};

const searchQuery = ref('');

const filteredInventory = computed(() => {
    let result = props.inventory;
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(item => 
            (item.part_name && item.part_name.toLowerCase().includes(query)) ||
            (item.part_code && item.part_code.toLowerCase().includes(query)) ||
            (item.category && item.category.toLowerCase().includes(query)) ||
            (item.brand && item.brand.toLowerCase().includes(query))
        );
    }
    return result;
});

const totalItems = computed(() => props.inventory.length);
const stockValue = computed(() => {
    return props.inventory.reduce((total, item) => total + (item.current_stock * item.current_price), 0);
});

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};
</script>

<template>
    <Head title="Inventory" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Parts Inventory</h1>
                    <p class="mt-1 text-sm text-gray-500 font-medium">Manage your workshop inventory and supplier accounts.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="isBrandModalOpen = true" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Brands
                    </button>
                    <button @click="isCategoryModalOpen = true" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Manage Categories
                    </button>

                    
                    <div class="flex h-10 items-center bg-gray-100 rounded-lg border border-gray-200 overflow-hidden">
                        <span class="text-xs text-gray-500 font-semibold px-3">Default Qty</span>
                        <input type="text" value="200" class="w-12 h-full text-center text-sm font-bold bg-transparent border-none focus:ring-0 p-0 text-gray-900" />
                        <button class="h-full text-xs font-semibold text-red-600 px-3 hover:bg-gray-200 transition-colors">Apply</button>
                    </div>

                    <button @click="exportCsv" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Export
                    </button>
                    <button @click="isBulkModalOpen = true" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Bulk
                    </button>
                    
                    <button @click="openAddModal" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#b91c1c] px-4 text-sm font-semibold text-white shadow-sm hover:bg-red-800 transition-colors">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Item
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-semibold text-gray-500">Total Items</span>
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-3xl font-black text-gray-900">{{ totalItems }}</h3>
                            <p class="text-xs text-gray-400 mt-1">Total inventory items</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-semibold text-gray-500">Stock Value</span>
                            <span class="text-gray-400 font-bold">₹</span>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-3xl font-black text-gray-900">{{ formatCurrency(stockValue) }}</h3>
                            <p class="text-xs text-gray-400 mt-1">Total inventory value</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-semibold text-gray-500">Purchase Value</span>
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-3xl font-black text-gray-900">₹0</h3>
                            <p class="text-xs text-gray-400 mt-1">Total purchase value</p>
                        </div>
                        <div class="mt-auto pt-4 flex justify-end">
                            <Link :href="route('purchases.index')" class="text-xs font-bold text-gray-600 hover:text-gray-900 flex items-center">
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                View History
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-semibold text-gray-500">Outstanding</span>
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-3xl font-black text-gray-900">₹0</h3>
                            <p class="text-xs text-gray-400 mt-1">Pending payments</p>
                        </div>
                        <div class="mt-auto pt-4 flex justify-end">
                            <button class="text-xs font-bold text-gray-600 hover:text-gray-900 flex items-center">
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                View History
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-semibold text-gray-500">Default Qty</span>
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-3xl font-black text-gray-900">200</h3>
                            <p class="text-xs text-gray-400 mt-1">Auto-fill qty</p>
                        </div>
                    </div>
                </div>

                <!-- Filters & Table Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input v-model="searchQuery" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm placeholder-gray-500 focus:ring-primary-500 focus:border-primary-500" placeholder="Search inventory items..." />
                        </div>
                        
                        <!-- Filter Row 1 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Category</label>
                                <select class="block w-full py-2 pl-3 pr-10 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white">
                                    <option>All Categories</option>
                                    <option v-for="cat in categories" :key="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                                <select class="block w-full py-2 pl-3 pr-10 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white">
                                    <option>All Status</option>
                                    <option>In Stock</option>
                                    <option>Out of Stock</option>
                                    <option>Low Stock</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Stock Level</label>
                                <select class="block w-full py-2 pl-3 pr-10 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white">
                                    <option>All Levels</option>
                                    <option>High</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Row 2 -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Min Qty</label>
                                <input type="number" value="0" class="block w-full py-2 px-3 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Max Qty</label>
                                <input type="text" value="999+" class="block w-full py-2 px-3 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Min Price (₹)</label>
                                <input type="number" value="0.00" step="0.01" class="block w-full py-2 px-3 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Max Price (₹)</label>
                                <input type="text" value="99999+" class="block w-full py-2 px-3 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Item Details</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Category</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Brand</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Available</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Unit Price</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600">Stock Status</th>
                                    <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="item in filteredInventory" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-800">{{ item.part_name }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ item.part_code || '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-600 font-medium">{{ item.category || 'Other' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-800 font-bold">{{ item.brand || '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-bold text-gray-800">{{ item.current_stock }} <span class="font-normal text-gray-500">Units</span></div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-bold text-gray-800">₹{{ item.current_price }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center text-sm font-bold" :class="item.current_stock > item.min_stock_level ? 'text-green-600' : 'text-red-600'">
                                            <svg v-if="item.current_stock > item.min_stock_level" class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <svg v-else class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ item.current_stock > item.min_stock_level ? 'In Stock' : 'Out of Stock' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openEditModal(item)" class="inline-flex items-center rounded border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-sm">
                                                <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                Edit
                                            </button>
                                            <button @click="deleteItem(item.id)" class="inline-flex items-center rounded border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 shadow-sm">
                                                <svg class="mr-1.5 h-3.5 w-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredInventory.length === 0">
                                    <td colspan="7" class="py-12 text-center text-sm font-medium text-gray-500">
                                        No inventory items found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="isModalOpen" @close="isModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Item' : 'Add New Item' }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Part Name *</label>
                            <input v-model="form.part_name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Part Code</label>
                            <input v-model="form.part_code" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select v-model="form.category" @change="if(form.category === '__NEW__') { isCategoryModalOpen = true; form.category = ''; }" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="" disabled>Select category...</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                                <option value="__NEW__" class="font-bold text-indigo-600">+ Add New Category...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Brand</label>
                            <select v-model="form.brand" @change="if(form.brand === '__NEW__') { isBrandModalOpen = true; form.brand = ''; }" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="" disabled>Select brand...</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.name">{{ brand.name }}</option>
                                <option value="__NEW__" class="font-bold text-indigo-600">+ Add New Brand...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Initial Stock</label>
                            <input v-model="form.current_stock" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Min Stock Level</label>
                            <input v-model="form.min_stock_level" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price (₹)</label>
                            <input v-model="form.current_price" type="number" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="isModalOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">{{ isEditing ? 'Update Item' : 'Save Item' }}</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="isBulkModalOpen" @close="isBulkModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Bulk Import Inventory</h2>
                <form @submit.prevent="submitBulk" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
                        <input type="file" @change="e => bulkForm.file = e.target.files[0]" accept=".csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-md text-xs text-gray-600">
                        <p class="font-bold mb-1">Expected CSV Format (No Headers in data row, just headers in row 1):</p>
                        <p>Part Name, Part Code, Category, Stock, Min Stock, Price, Location</p>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="isBulkModalOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="bulkForm.processing" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">Upload & Import</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="isCategoryModalOpen" @close="isCategoryModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Manage Categories</h2>
                
                <form @submit.prevent="submitCategory" class="mb-6 flex gap-3">
                    <input v-model="categoryForm.name" type="text" placeholder="New Category Name..." required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="submit" :disabled="categoryForm.processing" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none disabled:opacity-50">Add</button>
                </form>

                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="cat in categories" :key="cat.id">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ cat.name }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <button @click="deleteCategory(cat.id)" class="text-red-600 hover:text-red-900 font-semibold">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No categories added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="isCategoryModalOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Close</button>
                </div>
            </div>
        </Modal>

        <Modal :show="isBrandModalOpen" @close="isBrandModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Manage Brands</h2>
                
                <form @submit.prevent="submitBrand" class="mb-6 flex gap-3">
                    <input v-model="brandForm.name" type="text" placeholder="New Brand Name..." required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="submit" :disabled="brandForm.processing" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none disabled:opacity-50">Add</button>
                </form>

                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="brand in brands" :key="brand.id">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ brand.name }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <button @click="deleteBrand(brand.id)" class="text-red-600 hover:text-red-900 font-semibold">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="brands.length === 0">
                                <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No brands added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="isBrandModalOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Close</button>
                </div>
            </div>
        </Modal>
        
        <AIAssistantWidget />
    </AuthenticatedLayout>
</template>
