<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    customers: Array,
    brands: Array,
    models: Array,
});

const isModalOpen = ref(false);
const editingCustomer = ref(null);
const isViewJobsModalOpen = ref(false);
const viewingCustomer = ref(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    gst_number: '',
    whatsapp_number: '',
    vehicles: [],
});

const openModal = (customer = null) => {
    editingCustomer.value = customer;
    if (customer) {
        form.name = customer.user.name;
        form.phone = customer.user.phone;
        form.email = customer.user.email?.includes('customer_') ? '' : customer.user.email;
        form.address = customer.address || '';
        form.gst_number = customer.gst_number || '';
        form.whatsapp_number = customer.whatsapp_number || '';
        form.vehicles = customer.vehicles ? customer.vehicles.map(v => ({
            id: v.id,
            registration_number: v.registration_number,
            brand_id: v.brand_id,
            model_id: v.model_id
        })) : [];
    } else {
        form.reset();
        form.vehicles = [];
    }
    isModalOpen.value = true;
};

const openViewJobsModal = (customer) => {
    viewingCustomer.value = customer;
    isViewJobsModalOpen.value = true;
};

const closeViewJobsModal = () => {
    isViewJobsModalOpen.value = false;
    viewingCustomer.value = null;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    editingCustomer.value = null;
};

const submit = () => {
    if (editingCustomer.value) {
        form.put(route('customers.update', editingCustomer.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const addVehicle = () => {
    form.vehicles.push({
        registration_number: '',
        brand_id: '',
        model_id: ''
    });
};

const removeVehicle = (index) => {
    form.vehicles.splice(index, 1);
};

const filteredModels = (brandId) => {
    if (!brandId) return [];
    return props.models.filter(m => m.brand_id == brandId);
};

const deleteCustomer = (id) => {
    if (confirm('Are you sure you want to delete this customer? All their associated vehicles and job cards will also be affected.')) {
        useForm({}).delete(route('customers.destroy', id));
    }
};

const resetPassword = (id) => {
    if (confirm('Are you sure you want to reset this customer\'s password to "password"?')) {
        useForm({}).post(route('customers.resetPassword', id), {
            onSuccess: () => alert('Password successfully reset to: password')
        });
    }
};

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
    <Head title="Customers" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Customers</h1>
                    <p class="mt-2 text-sm text-gray-500">A list of all the customers in your garage including their contact details and job history.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button @click="openModal()" type="button" class="block rounded-md bg-primary-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        Add Customer
                    </button>
                </div>
            </div>
            
            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg bg-white">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Contact</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Jobs</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Added On</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
                                                    {{ customer.user.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-gray-900">{{ customer.user.name }}</div>
                                                    <div class="text-gray-500">{{ customer.gst_number ? 'GST: ' + customer.gst_number : '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <div class="text-gray-900">{{ customer.user.phone }}</div>
                                            <div class="text-gray-500">{{ customer.user.email?.includes('customer_') ? 'No email' : customer.user.email }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <button @click="openViewJobsModal(customer)" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 hover:bg-blue-100 cursor-pointer">
                                                {{ customer.job_cards_count }} Jobs
                                            </button>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ formatDate(customer.created_at) }}
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="openModal(customer)" class="text-primary-600 hover:text-primary-900 mr-4">Edit</button>
                                            <button @click="deleteCustomer(customer.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="customers.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No customers found. Click "Add Customer" to create one.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Customer Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}
                    </h2>
                    <button v-if="editingCustomer" @click="resetPassword(editingCustomer.id)" type="button" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-1 px-3 border border-gray-400 rounded shadow">
                        Reset Password
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number *</label>
                            <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address (Optional)</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp Number (Optional)</label>
                            <input v-model="form.whatsapp_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">GST Number (Optional)</label>
                            <input v-model="form.gst_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Address (Optional)</label>
                            <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div class="pt-6 mt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-900">Customer Vehicles</h3>
                            <button type="button" @click="addVehicle" class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg hover:bg-blue-100 transition-colors border border-blue-100">
                                + Add Vehicle
                            </button>
                        </div>

                        <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                            <div v-for="(vehicle, index) in form.vehicles" :key="index" class="bg-gray-50 p-4 rounded-lg border border-gray-200 relative">
                                <button type="button" @click="removeVehicle(index)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pr-8">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Reg No. *</label>
                                        <input v-model="vehicle.registration_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm uppercase" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Brand *</label>
                                        <select v-model="vehicle.brand_id" @change="vehicle.model_id = ''" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                                            <option value="" disabled>Select Brand</option>
                                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Model *</label>
                                        <select v-model="vehicle.model_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required :disabled="!vehicle.brand_id">
                                            <option value="" disabled>Select Model</option>
                                            <option v-for="model in filteredModels(vehicle.brand_id)" :key="model.id" :value="model.id">{{ model.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.vehicles.length === 0" class="text-center py-4 text-sm text-gray-500 italic border border-dashed border-gray-300 rounded-lg">
                                No vehicles added yet.
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">Save Customer</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- View Jobs Modal -->
        <Modal :show="isViewJobsModalOpen" @close="closeViewJobsModal" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        Job History - {{ viewingCustomer?.user?.name }}
                    </h2>
                    <button @click="closeViewJobsModal" type="button" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="viewingCustomer?.job_cards && viewingCustomer.job_cards.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Job No.</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vehicle</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="job in viewingCustomer.job_cards" :key="job.id">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                    <Link :href="route('job-cards.show', job.id)" class="text-primary-600 hover:text-primary-900 hover:underline">
                                        {{ job.job_number }}
                                    </Link>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ job.vehicle?.brand?.name }} {{ job.vehicle?.vehicle_model?.name }}
                                    <span class="block text-xs text-gray-400">{{ job.vehicle?.registration_number }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
                                        job.status === 'delivered' ? 'bg-green-100 text-green-800' : 
                                        job.status === 'working' ? 'bg-blue-100 text-blue-800' :
                                        job.status === 'completed' ? 'bg-indigo-100 text-indigo-800' :
                                        'bg-gray-100 text-gray-800'
                                    ]">
                                        {{ job.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">₹{{ job.final_total }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ formatDate(job.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    No job history found for this customer.
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
