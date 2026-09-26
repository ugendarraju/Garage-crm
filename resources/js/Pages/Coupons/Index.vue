<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsNav from '@/Components/SettingsNav.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    coupons: Array,
});

const isModalOpen = ref(false);
const editingCoupon = ref(null);

const form = useForm({
    code: '',
    discount_type: 'fixed',
    discount_value: '',
    expiry_date: '',
    is_active: true,
});

const openModal = (coupon = null) => {
    editingCoupon.value = coupon;
    if (coupon) {
        form.code = coupon.code;
        form.discount_type = coupon.discount_type;
        form.discount_value = coupon.discount_value;
        form.expiry_date = coupon.expiry_date ? coupon.expiry_date.substring(0, 10) : '';
        form.is_active = coupon.is_active;
    } else {
        form.reset();
        form.is_active = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
    editingCoupon.value = null;
};

const submit = () => {
    if (editingCoupon.value) {
        form.put(route('coupons.update', editingCoupon.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('coupons.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCoupon = (id) => {
    if (confirm('Are you sure you want to delete this coupon?')) {
        useForm({}).delete(route('coupons.destroy', id));
    }
};

const toggleActive = (id) => {
    useForm({}).patch(route('coupons.toggle-active', id));
};
</script>

<template>
    <Head title="Coupons" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">
            <SettingsNav />

            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Coupons</h1>
                    <p class="mt-2 text-sm text-gray-500">Manage discount coupons and codes for Job Cards.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button @click="openModal()" type="button" class="block rounded-md bg-gray-900 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition">
                        Add Coupon
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
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Code</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Expiry</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 font-bold text-gray-900">
                                            {{ coupon.code }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">
                                            {{ coupon.discount_type }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                            <span v-if="coupon.discount_type === 'percentage'">{{ coupon.discount_value }}%</span>
                                            <span v-else>₹{{ coupon.discount_value }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ coupon.expiry_date ? new Date(coupon.expiry_date).toLocaleDateString() : 'Never' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            <span :class="[
                                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                                coupon.status_label === 'Active' ? 'bg-green-50 text-green-700 ring-green-600/20' : 
                                                coupon.status_label === 'Expired' ? 'bg-red-50 text-red-700 ring-red-600/20' : 
                                                'bg-gray-50 text-gray-700 ring-gray-600/20'
                                            ]">
                                                {{ coupon.status_label }}
                                            </span>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="toggleActive(coupon.id)" class="text-gray-600 hover:text-gray-900 mr-4">
                                                {{ coupon.is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button @click="openModal(coupon)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                            <button @click="deleteCoupon(coupon.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="coupons.length === 0">
                                        <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                            No coupons created yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">
                    {{ editingCoupon ? 'Edit Coupon' : 'Create New Coupon' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Coupon Code</label>
                        <input type="text" v-model="form.code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase" required :disabled="editingCoupon" placeholder="e.g. SUMMER20">
                        <p class="mt-1 text-xs text-red-500" v-if="form.errors.code">{{ form.errors.code }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Discount Type</label>
                            <select v-model="form.discount_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Value</label>
                            <input type="number" step="0.01" v-model="form.discount_value" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required placeholder="0.00">
                            <p class="mt-1 text-xs text-red-500" v-if="form.errors.discount_value">{{ form.errors.discount_value }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expiry Date (Optional)</label>
                        <input type="date" v-model="form.expiry_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-red-500" v-if="form.errors.expiry_date">{{ form.errors.expiry_date }}</p>
                    </div>

                    <div class="flex items-center">
                        <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                            {{ editingCoupon ? 'Update Coupon' : 'Create Coupon' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
