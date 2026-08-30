<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    metrics: Object,
    recentJobs: Array,
    vehicles: Array,
    brands: Array,
    models: Array,
});

const showVehicleModal = ref(false);

const vehicleForm = useForm({
    brand_id: '',
    vehicle_model_id: '',
    registration_number: '',
});

const availableModels = computed(() => {
    if (!vehicleForm.brand_id) return [];
    return props.models.filter(m => m.brand_id === vehicleForm.brand_id);
});

const addVehicle = () => {
    vehicleForm.post(route('vehicles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showVehicleModal.value = false;
            vehicleForm.reset();
        },
    });
};

const deleteVehicle = (id) => {
    if (confirm('Are you sure you want to remove this vehicle?')) {
        useForm({}).delete(route('vehicles.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Head title="My Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 leading-tight">My Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Welcome Section -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Welcome back, {{ $page.props.auth.user.name }}!</h1>
                        <p class="text-gray-500 font-medium mt-1">Here's an overview of your vehicles and services.</p>
                    </div>
                    <div>
                        <Link :href="route('service-requests.create')" class="inline-flex items-center justify-center rounded-xl bg-primary-700 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Raise Service Request
                        </Link>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-sm font-bold text-gray-500">My Vehicles</h3>
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <span class="text-4xl font-black text-gray-900">{{ metrics.total_vehicles }}</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-sm font-bold text-gray-500">Active Jobs</h3>
                            <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <span class="text-4xl font-black text-gray-900">{{ metrics.active_jobs }}</span>
                            <span class="text-sm font-medium text-gray-500 ml-2 block mt-1">in progress</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-sm font-bold text-gray-500">Total Spent</h3>
                            <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                                <span class="font-bold">₹</span>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <span class="text-4xl font-black text-gray-900">{{ formatCurrency(metrics.total_expenditure) }}</span>
                            <span class="text-sm font-medium text-gray-500 ml-2 block mt-1">lifetime value</span>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    
                    <!-- My Vehicles -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">My Vehicles</h3>
                                <p class="text-sm text-gray-500 mt-1">Your registered vehicles</p>
                            </div>
                            <button @click="showVehicleModal = true" class="text-sm font-bold text-primary-600 hover:text-primary-800 transition-colors flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add New
                            </button>
                        </div>
                        <div class="p-0 flex-1">
                            <div v-if="vehicles.length === 0" class="flex flex-col items-center justify-center h-full p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <p>No vehicles registered.</p>
                            </div>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="vehicle in vehicles" :key="vehicle.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="bg-gray-100 p-3 rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ vehicle.brand?.name }} {{ vehicle.vehicle_model?.name }}</p>
                                            <div class="flex items-center mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#1a56db] text-white uppercase tracking-wider">
                                                    {{ vehicle.registration_number }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="deleteVehicle(vehicle.id)" class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-opacity p-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent Job Cards -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Recent Service Records</h3>
                                <p class="text-sm text-gray-500 mt-1">Your latest job cards</p>
                            </div>
                            <Link :href="route('job-cards.index')" class="text-sm font-bold text-primary-600 hover:text-primary-800 transition-colors flex items-center gap-1">
                                View All
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </Link>
                        </div>
                        <div class="p-0 flex-1">
                            <div v-if="recentJobs.length === 0" class="flex flex-col items-center justify-center h-full p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                <p>No service records yet.</p>
                            </div>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="job in recentJobs" :key="job.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                    <div>
                                        <Link :href="route('job-cards.show', job.id)" class="font-bold text-primary-600 hover:text-primary-900 hover:underline">
                                            {{ job.job_number }}
                                        </Link>
                                        <p class="text-sm text-gray-500 mt-1">{{ job.vehicle?.brand?.name }} {{ job.vehicle?.vehicle_model?.name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': ['scheduled', 'listed'].includes(job.status),
                                                'bg-blue-100 text-blue-800': job.status === 'working',
                                                'bg-green-100 text-green-800': ['complete', 'completed', 'delivered'].includes(job.status),
                                            }">
                                            {{ job.status }}
                                        </span>
                                        <p class="text-sm font-black text-gray-900 mt-1 mt-2">{{ formatCurrency(job.final_total) }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <Modal :show="showVehicleModal" @close="showVehicleModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Add New Vehicle</h2>

                <form @submit.prevent="addVehicle" class="space-y-4">
                    <div>
                        <InputLabel for="brand_id" value="Brand" />
                        <select id="brand_id" v-model="vehicleForm.brand_id" class="mt-1 block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-white" required>
                            <option value="">Select Brand...</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                        </select>
                        <InputError :message="vehicleForm.errors.brand_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vehicle_model_id" value="Model" />
                        <select id="vehicle_model_id" v-model="vehicleForm.vehicle_model_id" class="mt-1 block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500 bg-white" :disabled="!vehicleForm.brand_id" required>
                            <option value="">Select Model...</option>
                            <option v-for="model in availableModels" :key="model.id" :value="model.id">{{ model.name }}</option>
                        </select>
                        <InputError :message="vehicleForm.errors.vehicle_model_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="registration_number" value="Registration Number" />
                        <TextInput id="registration_number" v-model="vehicleForm.registration_number" type="text" class="mt-1 block w-full uppercase" required placeholder="e.g. KA01AB1234" />
                        <InputError :message="vehicleForm.errors.registration_number" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton @click="showVehicleModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': vehicleForm.processing }" :disabled="vehicleForm.processing">
                            Add Vehicle
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
