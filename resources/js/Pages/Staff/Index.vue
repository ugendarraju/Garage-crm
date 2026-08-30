<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    staff: Array,
});

const isModalOpen = ref(false);
const editingStaff = ref(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    role: 'mechanic',
    password: '',
});

const openModal = (member = null) => {
    editingStaff.value = member;
    if (member) {
        form.name = member.name;
        form.phone = member.phone;
        form.email = member.email;
        form.role = member.role;
        form.password = '';
    } else {
        form.reset();
        form.role = 'mechanic';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
    editingStaff.value = null;
};

const submit = () => {
    if (editingStaff.value) {
        form.put(route('staff.update', editingStaff.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('staff.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleActive = (id) => {
    useForm({}).patch(route('staff.toggle-active', id));
};

const deleteStaff = (id) => {
    if (confirm('Are you sure you want to remove this staff member?')) {
        useForm({}).delete(route('staff.destroy', id));
    }
};

</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">User Management</h1>
                    <p class="mt-2 text-sm text-gray-500">Manage mechanics, managers, and their access to the CRM.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button @click="openModal()" type="button" class="block rounded-md bg-primary-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        Add Staff Member
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
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
                                                    {{ member.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-gray-900">{{ member.name }}</div>
                                                    <div class="text-gray-500">{{ member.email }}</div>
                                                    <div class="text-gray-500 text-xs">{{ member.phone }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                  :class="member.role === 'manager' ? 'bg-purple-50 text-purple-700 ring-purple-700/10' : 'bg-gray-50 text-gray-600 ring-gray-500/10'">
                                                {{ member.role.charAt(0).toUpperCase() + member.role.slice(1) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <button @click="toggleActive(member.id)" 
                                                    :class="[member.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700', 'px-2 py-1 rounded-full text-xs font-bold transition-colors']">
                                                {{ member.is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="openModal(member)" class="text-primary-600 hover:text-primary-900 mr-4">Edit</button>
                                            <button @click="deleteStaff(member.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="staff.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No staff members found. Click "Add Staff Member" to create one.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Staff Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">
                    {{ editingStaff ? 'Edit Staff Member' : 'Add New Staff Member' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number *</label>
                            <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address *</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role *</label>
                        <select v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <option value="mechanic">Mechanic</option>
                            <option value="manager">Manager</option>
                        </select>
                        <div v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Password {{ editingStaff ? '(Leave blank to keep current)' : '*' }}
                        </label>
                        <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" :required="!editingStaff">
                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            {{ form.processing ? 'Saving...' : 'Save Staff' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
