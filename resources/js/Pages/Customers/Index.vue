<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
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

const credentialsModal = ref(null);
const isCredentialsModalOpen = ref(false);
const copied = ref(false);

const page = usePage();
watch(
    () => page.props.flash?.new_customer_credentials,
    (creds) => {
        if (creds) {
            credentialsModal.value = creds;
            isCredentialsModalOpen.value = true;
        }
    },
    { immediate: true }
);

const closeCredentialsModal = () => {
    isCredentialsModalOpen.value = false;
    credentialsModal.value = null;
};

const copyCredentials = async () => {
    if (!credentialsModal.value) return;
    const creds = credentialsModal.value;
    const text = `Garage Portal Login Details:\nWebsite: ${window.location.origin}/login\nUsername / Phone: ${creds.phone}${creds.email ? '\nEmail: ' + creds.email : ''}\nTemporary Password: ${creds.temporary_password}\n\nNote: You will be asked to set a permanent password when you first log in.`;
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    } catch (e) {
        console.error('Failed to copy', e);
    }
};

const getWhatsAppShareUrl = (creds) => {
    if (!creds) return '#';
    const phone = creds.whatsapp_number || creds.phone;
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(
        `Hello ${creds.name},\nYour account has been created for the Garage Customer Portal!\n\nWebsite: ${window.location.origin}/login\nPhone: ${creds.phone}\nTemporary Password: ${creds.temporary_password}\n\nPlease log in to view your vehicle service records and job cards. You will be prompted to set your personal password on your first login.`
    );
    return `https://wa.me/${cleanPhone}?text=${text}`;
};

const resetPassword = (id, customerName = 'this customer') => {
    if (confirm(`Reset password and generate new login credentials for ${customerName}?`)) {
        useForm({}).post(route('customers.resetPassword', id));
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
                                            <button @click="openModal(customer)" class="text-primary-600 hover:text-primary-900 mr-3">Edit</button>
                                            <button @click="resetPassword(customer.id, customer.user.name)" class="text-amber-600 hover:text-amber-900 mr-3" title="Generate temporary password and invite">Reset Pass</button>
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
                    <button v-if="editingCustomer" @click="resetPassword(editingCustomer.id, editingCustomer.user.name)" type="button" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold py-1.5 px-3 border border-amber-200 rounded-lg shadow-xs transition">
                        🔑 Generate New Password
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Rahul Sharma" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number *</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                inputmode="tel"
                                placeholder="e.g. 9876543210 or +919876543210"
                                @input="form.phone = form.phone.replace(/[^0-9+]/g, '')"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                required
                            >
                            <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address (Optional)</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="customer@example.com"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                            <p class="text-[11px] text-gray-500 mt-1">If provided, an invite email with login credentials will be sent automatically.</p>
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp Number (Optional)</label>
                            <input
                                v-model="form.whatsapp_number"
                                type="tel"
                                inputmode="tel"
                                placeholder="e.g. 9876543210 or +919876543210"
                                @input="form.whatsapp_number = form.whatsapp_number.replace(/[^0-9+]/g, '')"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                            <div v-if="form.errors.whatsapp_number" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp_number }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">GST Number (Optional)</label>
                            <input v-model="form.gst_number" type="text" placeholder="e.g. 29AAAAA0000A1Z5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
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

        <!-- Customer Account Credentials Modal -->
        <Modal :show="isCredentialsModalOpen" @close="closeCredentialsModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 leading-tight">
                            {{ credentialsModal?.is_reset ? 'Password Reset' : 'Customer Account Created' }}
                        </h3>
                        <p class="text-xs text-gray-500">Login credentials for the customer portal</p>
                    </div>
                </div>

                <div v-if="credentialsModal?.invite_sent" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Invite email with temporary password was sent to <strong>{{ credentialsModal.email }}</strong>.</span>
                </div>

                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-2.5 text-sm">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Customer:</span>
                        <span class="font-bold text-gray-900">{{ credentialsModal?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Phone (Login ID):</span>
                        <span class="font-bold text-gray-900 font-mono">{{ credentialsModal?.phone }}</span>
                    </div>
                    <div v-if="credentialsModal?.email" class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Email:</span>
                        <span class="font-bold text-gray-900">{{ credentialsModal?.email }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-500 block">Temporary Password</span>
                            <span class="text-lg font-black text-orange-600 font-mono tracking-wider">{{ credentialsModal?.temporary_password }}</span>
                        </div>
                        <button
                            @click="copyCredentials"
                            type="button"
                            class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs flex items-center gap-1"
                        >
                            <svg v-if="!copied" class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span v-if="copied" class="text-emerald-600 font-bold">Copied!</span>
                            <span v-else>Copy</span>
                        </button>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>First Login Security:</strong> The customer will be prompted to set a permanent password when they first log in.</span>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-2">
                    <a
                        :href="getWhatsAppShareUrl(credentialsModal)"
                        target="_blank"
                        class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        Share via WhatsApp
                    </a>
                    <button
                        type="button"
                        @click="closeCredentialsModal"
                        class="py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold text-xs rounded-xl transition text-center"
                    >
                        Done
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
