<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import InspectionChecklist from '@/Components/InspectionChecklist.vue';

const props = defineProps({
    customers: Array,
    mechanics: Array,
    serviceTypes: Array,
    brands: Array,
    models: Array,
    inventoryItems: Array,
});

const form = useForm({
    // Existing Customer logic
    customer_id: '',
    
    // New Customer inline logic
    new_customer_name: '',
    new_customer_phone: '',
    
    // Existing Vehicle Logic
    vehicle_id: '',
    
    // New Vehicle Inline logic
    new_vehicle_reg: '',
    new_vehicle_brand_id: '',
    new_vehicle_model_id: '',
    
    // Mechanics logic
    mechanic_ids: [],
    
    // Job details
    job_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: '',
    priority: 'Medium',
    status: 'listed',
    next_service_reminder: '',
    vehicle_running_km: '',
    fuel_range: '1/2',
    amount_paid: '',
    discount: 0,
    coupon_code: '',
    inspection_checklist: null,
    description: '',
    
    // Customer Issues
    customer_issues: [
        { description: '' }
    ],
});

const couponMessage = ref('');
const couponSuccess = ref(false);

const applyCoupon = () => {
    if (!form.coupon_code) return;
    
    axios.post(route('coupons.apply'), {
        code: form.coupon_code,
        subtotal: subtotal.value
    }).then(response => {
        form.discount = response.data.discount;
        form.coupon_code = response.data.code;
        couponSuccess.value = true;
        couponMessage.value = `₹${response.data.discount} discount applied!`;
    }).catch(error => {
        form.discount = 0;
        couponSuccess.value = false;
        couponMessage.value = error.response?.data?.error || 'Failed to apply coupon.';
    });
};

// UI State
const showNewCustomerForm = ref(false);
const showNewVehicleForm = ref(false);

const filteredVehicles = computed(() => {
    if (!form.customer_id) return [];
    const customer = props.customers.find(c => c.id === form.customer_id);
    return customer ? customer.vehicles : [];
});

const filteredModels = computed(() => {
    if (!form.new_vehicle_brand_id) return [];
    return props.models.filter(m => m.brand_id === form.new_vehicle_brand_id);
});

// Services state
const services = ref([
    { id: 1, type: 'Service', name: '', rate: 0, apply_gst: false, gst_percent: null }
]);

const extractGstRate = (gstString) => {
    if (!gstString || gstString === 'None') return 0;
    if (gstString.includes('CGST')) {
        let match = gstString.match(/CGST (\d+(\.\d+)?)%/);
        if (match) return Number(match[1]) * 2;
    } else if (gstString.includes('IGST')) {
        let match = gstString.match(/IGST (\d+(\.\d+)?)%/);
        if (match) return Number(match[1]);
    }
    return parseInt(gstString) || 0;
};

const addItem = () => {
    services.value.push({
        id: Date.now(),
        type: 'Service',
        name: '',
        rate: 0,
        apply_gst: false,
        gst_percent: null
    });
};

const removeItem = (index) => {
    services.value.splice(index, 1);
};

const servicesTotal = computed(() => {
    return services.value.filter(s => s.type === 'Service').reduce((acc, curr) => acc + (Number(curr.rate) || 0), 0);
});

const partsTotal = computed(() => {
    return services.value.filter(s => s.type === 'Part').reduce((acc, curr) => acc + (Number(curr.rate) || 0), 0);
});

const subtotal = computed(() => servicesTotal.value + partsTotal.value);

const gstTotal = computed(() => {
    return services.value.filter(s => s.apply_gst).reduce((acc, curr) => {
        const percent = extractGstRate(curr.gst_percent) || 18;
        return acc + ((Number(curr.rate) || 0) * percent / 100);
    }, 0);
});

const finalTotal = computed(() => subtotal.value + gstTotal.value);

const submit = () => {
    // If not using new customer, clear those fields
    if (form.customer_id) {
        form.new_customer_name = '';
        form.new_customer_phone = '';
    }
    
    // If not using new vehicle, clear those fields
    if (form.vehicle_id) {
        form.new_vehicle_reg = '';
        form.new_vehicle_brand_id = '';
        form.new_vehicle_model_id = '';
    }

    form.transform((data) => ({
        ...data,
        services: services.value
    })).post(route('job-cards.store'));
};

const handleCustomerSelect = (e) => {
    if (e.target.value === 'new') {
        showNewCustomerForm.value = true;
        form.customer_id = '';
    } else {
        showNewCustomerForm.value = false;
        // Reset vehicle selection when customer changes
        form.vehicle_id = '';
    }
};

const handleVehicleSelect = (e) => {
    if (e.target.value === 'new') {
        showNewVehicleForm.value = true;
        form.vehicle_id = '';
    } else {
        showNewVehicleForm.value = false;
    }
};

    const scrollToMechanics = () => {
        const el = document.getElementById('mechanics_section');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the section temporarily
            const originalBg = el.style.backgroundColor;
            el.style.backgroundColor = '#fef08a'; // yellow-200
            el.style.transition = 'background-color 0.5s ease';
            
            setTimeout(() => {
                el.style.backgroundColor = originalBg || 'transparent';
            }, 1500);
            
            // Focus the select box
            const select = el.querySelector('select');
            if (select) select.focus();
        }
    };

    const addIssue = () => {
        form.customer_issues.push({ description: '' });
    };

    const removeIssue = (index) => {
        form.customer_issues.splice(index, 1);
    };
</script>

<template>
    <Head title="Job Card Workspace" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('job-cards.index')" class="p-1.5 sm:p-2 rounded-full hover:bg-gray-200 transition-colors shrink-0">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Job Card Workspace</h1>
                        <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-500 font-medium">Create customers, register vehicles, and build job cards.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="showNewCustomerForm = true; form.customer_id = ''" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-1 sm:mr-2 h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        <span class="hidden sm:inline">Add </span>Customer
                    </button>
                    <button type="button" @click="showNewVehicleForm = true; form.vehicle_id = ''" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-1 sm:mr-2 h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="hidden sm:inline">Add </span>Vehicle
                    </button>
                    <button type="button" @click="scrollToMechanics" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-1 sm:mr-2 h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="hidden sm:inline">Add </span>Mechanic
                    </button>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="flex flex-col lg:flex-row gap-8">
            <!-- Left Panel (Sidebar Workspace) -->
            <div class="w-full lg:w-1/3 flex flex-col gap-6">
                <!-- Customer Summary Widget -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 overflow-hidden relative hover:shadow-md transition-shadow">
                    <div class="absolute top-0 left-0 w-full h-1 bg-red-500"></div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Customer & Vehicle
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500">Customer</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">
                                <span v-if="form.customer_id">
                                    {{ customers.find(c => c.id === form.customer_id)?.user?.name }}
                                </span>
                                <span v-else-if="showNewCustomerForm">
                                    {{ form.new_customer_name || 'New Customer...' }}
                                </span>
                                <span v-else class="text-gray-400 font-normal">Select or create customer</span>
                            </p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-500">Vehicles</p>
                                <a v-if="form.vehicle_id" :href="route('vehicles.show', form.vehicle_id)" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                    <span>📜 History</span>
                                </a>
                            </div>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">
                                <span v-if="form.vehicle_id">
                                    {{ filteredVehicles.find(v => v.id === form.vehicle_id)?.registration_number }}
                                </span>
                                <span v-else-if="showNewVehicleForm">
                                    {{ form.new_vehicle_reg || 'New Vehicle...' }}
                                </span>
                                <span v-else class="text-gray-400 font-normal">Select customer first</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Media Gallery -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 overflow-hidden relative hover:shadow-md transition-shadow">
                    <div class="absolute top-0 left-0 w-full h-1 bg-red-500"></div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Media Gallery
                    </h3>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-500 mb-2">Photos (PNG/JPG/JPEG)</label>
                        <div class="flex gap-2">
                            <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-gray-50">Choose</button>
                            <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-gray-50">Camera</button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Type</label>
                        <select class="block w-full rounded-lg border-gray-300 py-2 pl-3 pr-10 text-gray-900 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 bg-gray-50">
                            <option>Before</option>
                            <option>After</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Notes</label>
                        <textarea v-model="form.description" rows="3" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-gray-900 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 bg-gray-50" placeholder="Description..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Main Workspace Panel -->
            <div class="w-full lg:w-2/3 flex flex-col gap-6">
                
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Build Job Card</h2>
                    <p class="text-sm text-gray-500 font-medium mb-6">If any details are not available, you can search. If you need to add something, just select 'Add New'.</p>

                    <!-- Section 1: Customer & Vehicle -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 relative">
                        <div class="absolute left-0 top-0 h-full w-1 bg-red-500 rounded-l-lg"></div>
                        <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Customer & Vehicle
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <!-- Customer Selection -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Customer *</label>
                                <select v-model="form.customer_id" @change="handleCustomerSelect" class="block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500 shadow-sm bg-gray-50">
                                    <option value="" disabled>Select Customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.user?.name }} ({{ customer.user?.phone }})
                                    </option>
                                    <option value="new" class="font-bold text-primary-600">+ Add New Customer</option>
                                </select>
                                <div v-if="form.errors.customer_id" class="text-red-500 text-xs mt-1">{{ form.errors.customer_id }}</div>
                            </div>

                            <!-- Vehicle Selection -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Vehicle *</label>
                                <select v-model="form.vehicle_id" @change="handleVehicleSelect" :disabled="!form.customer_id && !showNewCustomerForm" class="block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500 shadow-sm bg-gray-50">
                                    <option value="" disabled>Select vehicle</option>
                                    <option v-for="vehicle in filteredVehicles" :key="vehicle.id" :value="vehicle.id">
                                        {{ vehicle.registration_number }}
                                    </option>
                                    <option value="new" class="font-bold text-primary-600">+ Add New Vehicle</option>
                                </select>
                            </div>
                        </div>

                        <!-- Inline New Customer Form -->
                        <div v-if="showNewCustomerForm" class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-5">
                            <h4 class="text-xs font-bold text-primary-600 mb-3 uppercase tracking-wider">New Customer Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Full Name</label>
                                    <input v-model="form.new_customer_name" type="text" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Phone Number</label>
                                    <input v-model="form.new_customer_phone" type="text" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                        </div>

                        <!-- Inline New Vehicle Form -->
                        <div v-if="showNewVehicleForm" class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-5">
                            <h4 class="text-xs font-bold text-primary-600 mb-3 uppercase tracking-wider">New Vehicle Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Registration No.</label>
                                    <input v-model="form.new_vehicle_reg" type="text" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Brand</label>
                                    <select v-model="form.new_vehicle_brand_id" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="" disabled>Select Brand</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Model</label>
                                    <select v-model="form.new_vehicle_model_id" :disabled="!form.new_vehicle_brand_id" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="" disabled>Select Model</option>
                                        <option v-for="model in filteredModels" :key="model.id" :value="model.id">{{ model.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Mechanics -->
                        <div id="mechanics_section">
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Mechanics * <span class="text-gray-400 font-normal">(Hold Ctrl/Cmd to select multiple)</span></label>
                            <select v-model="form.mechanic_ids" multiple class="block w-full rounded-lg border-gray-300 py-2 pl-3 pr-3 text-gray-900 text-sm focus:border-primary-500 focus:ring-primary-500 shadow-sm bg-gray-50 h-24">
                                <option v-for="mech in mechanics" :key="mech.id" :value="mech.id">
                                    {{ mech.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Section 2: Job Details -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 relative">
                        <div class="absolute left-0 top-0 h-full w-1 bg-gray-300 rounded-l-lg"></div>
                        <h3 class="text-sm font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Job Details
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Job Date *</label>
                                <input v-model="form.job_date" type="date" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Expected Delivery Date *</label>
                                <input v-model="form.expected_delivery_date" type="date" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Priority</label>
                                <select v-model="form.priority" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Status</label>
                                <select v-model="form.status" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                                    <option value="scheduled">Estimate</option>
                                    <option value="listed">Pending</option>
                                    <option value="working">In Progress</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Next Service Reminder</label>
                                <input v-model="form.next_service_reminder" type="date" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Vehicle Running Till Date (KM)</label>
                                <input v-model="form.vehicle_running_km" type="text" placeholder="Current KM" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 shadow-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-3">Fuel / Range</label>
                                <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.fuel_range" value="Empty" class="text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm font-semibold text-gray-700">Empty</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.fuel_range" value="1/4" class="text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm font-semibold text-gray-700">1/4</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.fuel_range" value="1/2" class="text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm font-semibold text-gray-700">1/2</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.fuel_range" value="3/4" class="text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm font-semibold text-gray-700">3/4</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.fuel_range" value="Full" class="text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm font-semibold text-gray-700">Full</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2.5: Customer Reported Issues -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 relative">
                        <div class="absolute left-0 top-0 h-full w-1 bg-red-500 rounded-l-lg"></div>
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Customer Reported Issues
                            </h3>
                            <button type="button" @click="addIssue" class="px-3 py-1.5 bg-red-50 text-red-700 text-xs font-bold rounded-lg hover:bg-red-100 transition-colors border border-red-100">+ Add Issue</button>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="(issue, index) in form.customer_issues" :key="index" class="flex items-start gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <span class="bg-white text-gray-400 font-bold text-xs h-8 w-8 rounded flex items-center justify-center border border-gray-200 shadow-sm shrink-0">{{ index + 1 }}</span>
                                <input v-model="issue.description" type="text" placeholder="Describe the issue reported by the customer..." class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-white">
                                <button type="button" @click="removeIssue(index)" class="text-gray-400 hover:text-red-500 p-2 rounded transition-colors shrink-0" title="Remove Issue">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            
                            <div v-if="form.customer_issues.length === 0" class="text-center py-6 text-sm text-gray-500 italic bg-gray-50 rounded-lg border border-gray-100">
                                No issues added. Click "+ Add Issue" to record customer complaints.
                            </div>
                        </div>
                    </div>

                    <!-- Section 2.8: Vehicle Intake Inspection Checklist -->
                    <InspectionChecklist v-model="form.inspection_checklist" class="mb-6" />

                    <!-- Section 3: Services & Parts -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 relative">
                        <div class="absolute left-0 top-0 h-full w-1 bg-gray-300 rounded-l-lg"></div>
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Services & Parts
                            </h3>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="addItem" class="px-3 py-1.5 bg-primary-700 text-white text-xs font-bold rounded-lg hover:bg-primary-600">+ Add Item</button>
                            </div>
                        </div>

                        <!-- Dynamic Service List -->
                        <div class="space-y-4">
                            <div v-for="(service, index) in services" :key="service.id" class="bg-orange-50/50 rounded-xl p-4 border border-orange-100 relative">
                                <button type="button" @click="removeItem(index)" class="absolute top-3 right-3 text-gray-400 hover:text-red-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                
                                <span class="inline-block bg-orange-100 text-orange-800 text-[10px] font-bold px-2 py-0.5 rounded-full mb-3 uppercase tracking-wider">{{ service.type }}</span>
                                
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">Type *</label>
                                        <select v-model="service.type" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 bg-white">
                                            <option value="Service">Service</option>
                                            <option value="Part">Part</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">{{ service.type === 'Part' ? 'Part *' : 'Service *' }}</label>
                                        <select v-if="service.type === 'Service'" v-model="service.name" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 bg-white">
                                            <option value="" disabled>Select a service...</option>
                                            <option v-for="st in serviceTypes" :key="st.id" :value="st.name">{{ st.name }}</option>
                                        </select>
                                        <SearchableSelect 
                                            v-else 
                                            v-model="service.name" 
                                            :options="inventoryItems.map(p => p.part_name)"
                                            placeholder="Select a part..." 
                                        />
                                    </div>
                                    <div class="col-span-1">
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">Rate (₹) *</label>
                                        <input type="number" v-model="service.rate" class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 bg-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">GST Option</label>
                                        <select 
                                            :value="service.apply_gst ? (service.gst_percent || 'CGST 9% + SGST 9%') : 'None'" 
                                            @change="(e) => { 
                                                if (e.target.value === 'None') { 
                                                    service.apply_gst = false; 
                                                    service.gst_percent = null; 
                                                } else { 
                                                    service.apply_gst = true; 
                                                    service.gst_percent = e.target.value; 
                                                } 
                                            }" 
                                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 bg-white"
                                        >
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
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">GST Total</label>
                                        <input type="text" :value="service.apply_gst ? `₹${(service.rate * (extractGstRate(service.gst_percent) || 18) / 100).toFixed(2)}` : '₹0.00'" disabled class="block w-full rounded-lg border-gray-200 py-2 px-3 text-sm bg-gray-50 text-gray-500 font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase">Item Total</label>
                                        <input type="text" :value="service.apply_gst ? `₹${(service.rate * (1 + (extractGstRate(service.gst_percent) || 18) / 100)).toFixed(2)}` : `₹${(service.rate || 0).toFixed(2)}`" disabled class="block w-full rounded-lg border-gray-200 py-2 px-3 text-sm bg-gray-50 text-gray-900 font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Breakdown & Submit -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <!-- Cost Breakdown -->
                        <div class="bg-white rounded-lg border border-gray-200 p-5 relative">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 mb-5">
                                <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                Cost Breakdown
                            </h3>
                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Services ({{ services.filter(s => s.type === 'Service').length }}):</span>
                                    <span class="text-gray-900 font-semibold">₹{{ servicesTotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Spare Parts ({{ services.filter(s => s.type === 'Part').length }}):</span>
                                    <span class="text-gray-900 font-semibold">₹{{ partsTotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Outsourced Work (0):</span>
                                    <span class="text-gray-900 font-semibold">₹0.00</span>
                                </div>
                                <div class="border-t border-gray-100 pt-3 flex justify-between text-sm">
                                    <span class="text-gray-700 font-semibold">Subtotal:</span>
                                    <span class="text-gray-900 font-bold">₹{{ subtotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 font-medium">Applied GST (Item-wise):</span>
                                    <span class="text-green-600 font-semibold">+ ₹{{ gstTotal.toFixed(2) }}</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-4 pb-4 mb-4 flex flex-col gap-2">
                                <div class="flex justify-between items-center" v-if="form.discount > 0">
                                    <span class="text-sm font-bold text-gray-700">Discount Applied:</span>
                                    <span class="text-sm font-bold text-red-600">- ₹{{ parseFloat(form.discount).toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-black text-gray-900">Final Total:</span>
                                    <span class="text-xl font-black text-green-600">₹{{ Math.max(0, finalTotal - (form.discount || 0)).toFixed(2) }}</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-gray-700">Have a Coupon?</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                        </div>
                                        <input type="text" v-model="form.coupon_code" placeholder="Enter code" class="block w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-gray-50 uppercase">
                                    </div>
                                    <button type="button" @click="applyCoupon" class="bg-red-300 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-sm hover:bg-red-400 transition-colors">Apply</button>
                                </div>
                                <div v-if="couponMessage" :class="{'text-green-600': couponSuccess, 'text-red-600': !couponSuccess}" class="text-xs font-semibold mt-1">
                                    {{ couponMessage }}
                                </div>
                            </div>
                        </div>

                        <!-- Finalize Action -->
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-5 flex flex-col relative overflow-hidden">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 mb-6 relative z-10">
                                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Finalize Job Card
                            </h3>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 relative z-10 flex items-start gap-3">
                                <input type="checkbox" v-model="form.status" true-value="scheduled" false-value="listed" class="mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">Estimate Only</p>
                                    <p class="text-xs text-yellow-700 font-medium mt-0.5">Mechanics & dates are optional</p>
                                </div>
                            </div>
                            
                            <div class="mt-auto relative z-10">
                                <Link :href="route('job-cards.index')" class="block w-full text-center mb-3 rounded-lg px-6 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-200 transition-colors">
                                    Cancel
                                </Link>
                                <button type="submit" :disabled="form.processing" class="w-full flex items-center justify-center gap-2 rounded-lg bg-red-700 px-6 py-3.5 text-sm font-bold text-white hover:bg-red-800 transition-colors shadow-sm disabled:opacity-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ form.processing ? 'Saving...' : 'Create Job Card' }}
                                </button>
                            </div>
                            
                            <!-- Background decorative element -->
                            <div class="absolute -bottom-10 -right-10 opacity-5 pointer-events-none">
                                <svg class="w-40 h-40" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"></path></svg>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>