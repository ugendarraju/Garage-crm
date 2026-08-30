<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    brands: Array,
    models: Array,
    garages: Array,
    isCustomer: Boolean,
    customerVehicles: Array,
    customerDetails: Object,
});

const form = useForm({
    garage_id: '',
    customer_name: props.customerDetails?.name || '',
    email: props.customerDetails?.email || '',
    phone: props.customerDetails?.phone || '',
    vehicle_brand: '',
    vehicle_model: '',
    vehicle_year: '',
    vehicle_running_km: '',
    fuel_range: '1/2',
    issues: [
        { description: '', image: null }
    ],
    selected_vehicle_id: '',
});

const addIssue = () => {
    form.issues.push({ description: '', image: null });
};

const removeIssue = (index) => {
    if (form.issues.length > 1) {
        form.issues.splice(index, 1);
    }
};

const handleVehicleSelect = () => {
    if (form.selected_vehicle_id) {
        const vehicle = props.customerVehicles.find(v => v.id === form.selected_vehicle_id);
        if (vehicle) {
            form.vehicle_brand = vehicle.brand?.name || 'Unknown';
            form.vehicle_model = vehicle.vehicle_model?.name || 'Unknown';
            form.vehicle_year = 'N/A';
        }
    }
};

const filteredModels = computed(() => {
    if (!form.vehicle_brand) return [];
    const brand = props.brands.find(b => b.name === form.vehicle_brand);
    if (!brand) return [];
    return props.models.filter(m => m.brand_id === brand.id);
});

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 30 }, (_, i) => currentYear - i);

const submit = () => {
    form.post(route('service-requests.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Book a Service" />
    <div class="relative min-h-screen bg-gray-50 flex items-center justify-center p-6 dark:bg-zinc-900">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-transparent"></div>
        
        <div class="relative z-10 w-full max-w-2xl bg-white dark:bg-zinc-800 shadow-2xl rounded-3xl p-8 border border-gray-100 dark:border-zinc-700">
            
            <div class="text-center mb-8">
                <Link href="/">
                    <div class="inline-flex bg-orange-500 p-3 rounded-xl shadow-lg shadow-orange-500/30 mb-4 cursor-pointer hover:bg-orange-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Book a Service</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Fill in your details and we will schedule your appointment.</p>
            </div>

            <div v-if="$page.props.flash.success" class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-6 rounded-2xl text-center border border-green-200 dark:border-green-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-xl font-semibold mb-1">Request Received!</h2>
                <p>{{ $page.props.flash.success }}</p>
                <div class="mt-6 text-sm text-gray-500">You may now close this window or return to our <a href="/" class="text-orange-500 hover:underline">homepage</a>.</div>
            </div>

            <form v-else @submit.prevent="submit" class="space-y-6">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Garage</label>
                    <select v-model="form.garage_id" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                        <option value="" disabled>Choose a garage...</option>
                        <option v-for="garage in props.garages" :key="garage.id" :value="garage.id">{{ garage.name }}</option>
                    </select>
                    <div v-if="form.errors.garage_id" class="text-red-500 text-sm mt-1">{{ form.errors.garage_id }}</div>
                </div>

                <!-- Personal Info -->
                <div v-if="!isCustomer">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                            <input v-model="form.customer_name" type="text" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                            <div v-if="form.errors.customer_name" class="text-red-500 text-sm mt-1">{{ form.errors.customer_name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                            <input v-model="form.phone" type="text" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                            <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-700 mt-6">
                </div>

                <!-- Vehicle Info -->
                <!-- Vehicle Info -->
                <div v-if="isCustomer">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Your Vehicle</label>
                        <select v-model="form.selected_vehicle_id" @change="handleVehicleSelect" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                            <option value="" disabled>Choose a registered vehicle...</option>
                            <option v-for="vehicle in props.customerVehicles" :key="vehicle.id" :value="vehicle.id">
                                {{ vehicle.brand?.name }} {{ vehicle.vehicle_model?.name }} ({{ vehicle.registration_number }})
                            </option>
                        </select>
                        <div v-if="form.errors.vehicle_brand" class="text-red-500 text-sm mt-1">{{ form.errors.vehicle_brand }}</div>
                    </div>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vehicle Brand</label>
                        <select v-model="form.vehicle_brand" required @change="form.vehicle_model = ''" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                            <option value="" disabled>Select Brand</option>
                            <option v-for="brand in props.brands" :key="brand.id" :value="brand.name">{{ brand.name }}</option>
                        </select>
                        <div v-if="form.errors.vehicle_brand" class="text-red-500 text-sm mt-1">{{ form.errors.vehicle_brand }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                        <select v-model="form.vehicle_model" :disabled="!form.vehicle_brand" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white disabled:opacity-50">
                            <option value="" disabled>Select Model</option>
                            <option v-for="model in filteredModels" :key="model.id" :value="model.name">{{ model.name }}</option>
                        </select>
                        <div v-if="form.errors.vehicle_model" class="text-red-500 text-sm mt-1">{{ form.errors.vehicle_model }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                        <select v-model="form.vehicle_year" required class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                            <option value="" disabled>Select Year</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                        <div v-if="form.errors.vehicle_year" class="text-red-500 text-sm mt-1">{{ form.errors.vehicle_year }}</div>
                    </div>
                </div>

                <!-- Vehicle Condition -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vehicle Running Till Date (KM)</label>
                        <input v-model="form.vehicle_running_km" type="text" placeholder="Current KM" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white">
                        <div v-if="form.errors.vehicle_running_km" class="text-red-500 text-sm mt-1">{{ form.errors.vehicle_running_km }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fuel / Range</label>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 bg-gray-50 border border-gray-300 dark:bg-zinc-900 dark:border-zinc-700 rounded-xl px-4 py-2.5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.fuel_range" value="Empty" class="text-orange-500 focus:ring-orange-500 dark:bg-zinc-800 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Empty</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.fuel_range" value="1/4" class="text-orange-500 focus:ring-orange-500 dark:bg-zinc-800 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">1/4</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.fuel_range" value="1/2" class="text-orange-500 focus:ring-orange-500 dark:bg-zinc-800 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">1/2</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.fuel_range" value="3/4" class="text-orange-500 focus:ring-orange-500 dark:bg-zinc-800 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">3/4</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.fuel_range" value="Full" class="text-orange-500 focus:ring-orange-500 dark:bg-zinc-800 dark:border-zinc-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Full</span>
                            </label>
                        </div>
                        <div v-if="form.errors.fuel_range" class="text-red-500 text-sm mt-1">{{ form.errors.fuel_range }}</div>
                    </div>
                </div>

                <!-- Issues Section -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reported Issues</h3>
                        <button type="button" @click="addIssue" class="inline-flex items-center text-sm font-semibold text-orange-600 bg-orange-50 hover:bg-orange-100 rounded-lg px-3 py-1.5 transition-colors">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Issue
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(issue, index) in form.issues" :key="index" class="bg-gray-50 border border-gray-200 dark:bg-zinc-800/50 dark:border-zinc-700 rounded-xl p-4 relative">
                            <div class="absolute top-4 right-4">
                                <button v-if="form.issues.length > 1" type="button" @click="removeIssue(index)" class="text-red-500 hover:text-red-700 p-1 bg-red-50 hover:bg-red-100 rounded-md transition-colors" title="Remove Issue">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="grid gap-4 pr-10">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Issue #{{ index + 1 }} Description</label>
                                    <textarea v-model="issue.description" rows="2" required placeholder="Describe what needs to be fixed..." class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white"></textarea>
                                    <div v-if="form.errors[`issues.${index}.description`]" class="text-red-500 text-sm mt-1">{{ form.errors[`issues.${index}.description`] }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attach Image (Optional)</label>
                                    <input type="file" accept="image/*" @change="e => issue.image = e.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 dark:file:bg-orange-900/30 dark:file:text-orange-400 dark:text-gray-400" />
                                    <div v-if="form.errors[`issues.${index}.image`]" class="text-red-500 text-sm mt-1">{{ form.errors[`issues.${index}.image`] }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.issues" class="text-red-500 text-sm mt-1">{{ form.errors.issues }}</div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="form.processing" class="w-full bg-orange-500 text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-orange-600 focus:outline-none focus:ring-4 focus:ring-orange-500/30 transition disabled:opacity-50">
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else>Submit Service Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
