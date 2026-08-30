<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    serviceTypes: Array,
});

const isModalOpen = ref(false);
const editingServiceType = ref(null);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const openModal = (st = null) => {
    editingServiceType.value = st;
    if (st) {
        form.name = st.name;
        form.description = st.description;
        form.is_active = st.is_active;
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
    editingServiceType.value = null;
};

const submit = () => {
    if (editingServiceType.value) {
        form.put(route('service-types.update', editingServiceType.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('service-types.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleActive = (id) => {
    useForm({}).patch(route('service-types.toggle-active', id));
};

const deleteServiceType = (id) => {
    if (confirm('Are you sure you want to delete this service type?')) {
        useForm({}).delete(route('service-types.destroy', id));
    }
};
</script>

<template>
    <Head title="Service Types" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto py-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Service Types</h1>
                    <p class="mt-2 text-sm text-gray-500">Manage categories of services like General Service, Body Parts, Mods, etc.</p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <button @click="openModal()" type="button" class="block rounded-md bg-primary-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        Add Service Type
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
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Description</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="st in serviceTypes" :key="st.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 font-bold text-gray-900">
                                            {{ st.name }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">
                                            {{ st.description || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <button @click="toggleActive(st.id)" 
                                                    :class="[st.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700', 'px-2 py-1 rounded-full text-xs font-bold transition-colors']">
                                                {{ st.is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button @click="openModal(st)" class="text-primary-600 hover:text-primary-900 mr-4">Edit</button>
                                            <button @click="deleteServiceType(st.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="serviceTypes.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No Service Types found. Click "Add Service Type" to create one.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">
                    {{ editingServiceType ? 'Edit Service Type' : 'Add New Service Type' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type Name *</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Body Parts, Mods, General Service" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                    </div>

                    <div class="flex items-center gap-2 mt-4">
                        <input type="checkbox" v-model="form.is_active" id="is_active" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active Status</label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            {{ form.processing ? 'Saving...' : 'Save Service Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
