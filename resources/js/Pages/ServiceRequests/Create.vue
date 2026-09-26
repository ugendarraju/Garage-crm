<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    brands: Array,
    models: Array,
    garages: Array,
    selectedGarage: Object,
    isCustomer: Boolean,
    customerVehicles: Array,
    customerDetails: Object,
});

const form = useForm({
    garage_id: props.selectedGarage?.id || '',
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
    form.issues.push({ description: '', image: null, previewUrl: null });
};

const handleImageSelect = (e, issue) => {
    const file = e.target.files?.[0];
    if (file) {
        if (issue.previewUrl) {
            URL.revokeObjectURL(issue.previewUrl);
        }
        issue.image = file;
        issue.previewUrl = URL.createObjectURL(file);
    }
    e.target.value = '';
};

const removeImage = (issue) => {
    if (issue.previewUrl) {
        URL.revokeObjectURL(issue.previewUrl);
    }
    issue.image = null;
    issue.previewUrl = null;
};

const removeIssue = (index) => {
    if (form.issues.length > 1) {
        if (form.issues[index]?.previewUrl) {
            URL.revokeObjectURL(form.issues[index].previewUrl);
        }
        form.issues.splice(index, 1);
    }
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    const kb = bytes / 1024;
    if (kb < 1024) return `${Math.round(kb)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
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
            form.issues.forEach(issue => {
                if (issue.previewUrl) {
                    URL.revokeObjectURL(issue.previewUrl);
                }
            });
            form.reset();
            if (props.selectedGarage?.id) {
                form.garage_id = props.selectedGarage.id;
            }
        }
    });
};
</script>

<template>
    <Head :title="props.selectedGarage ? `Book a Service - ${props.selectedGarage.name}` : 'Book a Service'" />
    <div class="relative min-h-screen bg-gray-50 flex items-center justify-center p-6 dark:bg-zinc-900">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-transparent"></div>
        
        <div class="relative z-10 w-full max-w-2xl bg-white dark:bg-zinc-800 shadow-2xl rounded-3xl p-8 border border-gray-100 dark:border-zinc-700">
            
            <div class="text-center mb-8">
                <!-- Garage Branded Header (When garage is specified) -->
                <div v-if="props.selectedGarage" class="mb-4">
                    <div v-if="props.selectedGarage.logo_path" class="inline-block p-2 bg-white dark:bg-zinc-700 rounded-2xl shadow-md border border-gray-200 dark:border-zinc-600 mb-3">
                        <img :src="`/storage/${props.selectedGarage.logo_path}`" :alt="props.selectedGarage.name" class="h-16 w-auto max-w-[200px] object-contain rounded-xl" />
                    </div>
                    <div v-else class="inline-flex bg-orange-500 p-3.5 rounded-2xl shadow-lg shadow-orange-500/30 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800">
                            <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Direct Workshop Booking
                        </span>
                    </div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ props.selectedGarage.name }}</h1>
                    <p v-if="props.selectedGarage.tagline" class="text-sm font-semibold text-orange-600 dark:text-orange-400 mt-1">{{ props.selectedGarage.tagline }}</p>
                    <p v-if="props.selectedGarage.address" class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 max-w-md mx-auto">{{ props.selectedGarage.address }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Fill in your details below and we will schedule your vehicle appointment.</p>
                </div>

                <!-- Generic Header (When no specific garage is passed) -->
                <div v-else>
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
                <!-- Select Garage: Only shown if no garage is pre-selected -->
                <div v-if="!props.selectedGarage" class="mb-6">
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
                            <input
                                v-model="form.phone"
                                @input="form.phone = form.phone.replace(/[^0-9+]/g, '')"
                                type="tel"
                                inputmode="tel"
                                maxlength="16"
                                placeholder="+919876543210"
                                required
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-zinc-900 dark:border-zinc-700 dark:text-white"
                            >
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
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Attach Photo (Optional)
                                    </label>

                                    <!-- Hidden Inputs: Camera direct capture and Gallery picker -->
                                    <input
                                        :id="`camera-input-${index}`"
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        class="sr-only"
                                        @change="e => handleImageSelect(e, issue)"
                                    />
                                    <input
                                        :id="`gallery-input-${index}`"
                                        type="file"
                                        accept="image/*"
                                        class="sr-only"
                                        @change="e => handleImageSelect(e, issue)"
                                    />

                                    <!-- When Photo is Attached: Show Preview Card -->
                                    <div v-if="issue.previewUrl" class="flex items-center gap-3 p-2.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-xl shadow-sm">
                                        <div class="relative shrink-0">
                                            <img
                                                :src="issue.previewUrl"
                                                alt="Issue photo preview"
                                                class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg border border-gray-200 dark:border-zinc-700"
                                            />
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Photo Ready
                                                </span>
                                                <span v-if="issue.image?.size" class="text-[11px] text-gray-500 font-mono">
                                                    {{ formatFileSize(issue.image.size) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-1">
                                                {{ issue.image?.name || 'Camera photo' }}
                                            </p>
                                            
                                            <!-- Retake / Change / Remove Controls -->
                                            <div class="flex items-center gap-3 mt-1.5 text-xs">
                                                <label :for="`camera-input-${index}`" class="text-orange-600 hover:text-orange-700 dark:text-orange-400 font-semibold cursor-pointer flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <circle cx="12" cy="13" r="3" stroke-width="2" />
                                                    </svg>
                                                    Retake
                                                </label>
                                                <span class="text-gray-300 dark:text-zinc-600">•</span>
                                                <label :for="`gallery-input-${index}`" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 font-semibold cursor-pointer">
                                                    Choose Other
                                                </label>
                                                <span class="text-gray-300 dark:text-zinc-600">•</span>
                                                <button type="button" @click="removeImage(issue)" class="text-rose-600 hover:text-rose-700 dark:text-rose-400 font-semibold inline-flex items-center gap-0.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- When No Photo is Attached: Direct Camera & Gallery Buttons -->
                                    <div v-else class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        <!-- Camera Button -->
                                        <label
                                            :for="`camera-input-${index}`"
                                            class="flex-1 inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-orange-50 hover:bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300 dark:hover:bg-orange-900/50 border border-orange-200 dark:border-orange-800 rounded-xl text-xs sm:text-sm font-semibold cursor-pointer shadow-sm transition-all active:scale-95 text-center select-none"
                                        >
                                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <circle cx="12" cy="13" r="3" stroke-width="2" />
                                            </svg>
                                            <span>Take Photo (Camera)</span>
                                        </label>

                                        <!-- Gallery / File Upload Button -->
                                        <label
                                            :for="`gallery-input-${index}`"
                                            class="flex-1 inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-gray-200 border border-gray-300 dark:border-zinc-700 rounded-xl text-xs sm:text-sm font-semibold cursor-pointer shadow-sm transition-all active:scale-95 text-center select-none"
                                        >
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>From Gallery</span>
                                        </label>
                                    </div>

                                    <div v-if="form.errors[`issues.${index}.image`]" class="text-red-500 text-xs mt-1.5">
                                        {{ form.errors[`issues.${index}.image`] }}
                                    </div>
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
