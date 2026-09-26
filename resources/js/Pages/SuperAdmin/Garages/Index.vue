<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';

const props = defineProps({
    garages: Array
});

const showModal = ref(false);

const form = useForm({
    name: '',
    owner_name: '',
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('garages.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const manageGarage = (id) => {
    router.post(route('garages.impersonate', id));
};

const toggleActive = (id) => {
    router.post(route('garages.toggle-active', id), {}, { preserveScroll: true });
};

const deleteGarage = (id) => {
    if(confirm('Are you sure you want to completely delete this Garage and its Owner? This cannot be undone.')){
        router.delete(route('garages.destroy', id));
    }
}

const showOwnersModal = ref(false);
const selectedGarage = ref(null);

const ownerForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
});

watch(() => props.garages, (newGarages) => {
    if (selectedGarage.value) {
        selectedGarage.value = newGarages.find(g => g.id === selectedGarage.value.id) || null;
    }
}, { deep: true });

const openOwnersModal = (garage) => {
    selectedGarage.value = garage;
    showOwnersModal.value = true;
};

const submitOwner = () => {
    ownerForm.post(route('garage-owners.store', selectedGarage.value.id), {
        onSuccess: () => {
            ownerForm.reset();
        }
    });
};

const toggleOwnerStatus = (id) => {
    router.post(route('garage-owners.toggle-active', id), {}, { preserveScroll: true });
};

const resettingOwnerId = ref(null);
const customPassword = ref('');

const initiatePasswordReset = (id) => {
    resettingOwnerId.value = id;
    customPassword.value = '';
};

const cancelPasswordReset = () => {
    resettingOwnerId.value = null;
    customPassword.value = '';
};

const submitPasswordReset = (id) => {
    router.post(route('garage-owners.reset-password', id), { password: customPassword.value }, { 
        preserveScroll: true,
        onSuccess: () => {
            resettingOwnerId.value = null;
            customPassword.value = '';
        }
    });
};

// Action Logs Modal State & Functions
const showActionLogsModal = ref(false);
const selectedGarageForLogs = ref(null);
const garageLogs = ref([]);
const garageLogsTotal = ref(0);
const loadingLogs = ref(false);
const logsPage = ref(1);
const logsTotalPages = ref(1);
const logsFilterModule = ref('all');
const logsFilterSearch = ref('');
const expandedLogIds = ref({});

const toggleLogDetail = (id) => {
    expandedLogIds.value[id] = !expandedLogIds.value[id];
};

const openActionLogsModal = async (garage) => {
    selectedGarageForLogs.value = garage;
    showActionLogsModal.value = true;
    logsPage.value = 1;
    logsFilterModule.value = 'all';
    logsFilterSearch.value = '';
    expandedLogIds.value = {};
    await fetchGarageLogs();
};

const fetchGarageLogs = async (page = 1) => {
    if (!selectedGarageForLogs.value) return;
    loadingLogs.value = true;
    logsPage.value = page;
    try {
        const url = new URL(route('garages.action-logs', selectedGarageForLogs.value.id), window.location.origin);
        url.searchParams.append('page', page);
        if (logsFilterModule.value !== 'all') {
            url.searchParams.append('module', logsFilterModule.value);
        }
        if (logsFilterSearch.value.trim()) {
            url.searchParams.append('search', logsFilterSearch.value.trim());
        }

        const res = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        if (res.ok) {
            const data = await res.json();
            garageLogs.value = data.logs.data || [];
            garageLogsTotal.value = data.total || 0;
            logsTotalPages.value = data.logs.last_page || 1;
        }
    } catch (err) {
        console.error('Failed to load action logs:', err);
    } finally {
        loadingLogs.value = false;
    }
};

const formatRelativeTime = (dateStr) => {
    if (!dateStr) return '';
    const now = new Date();
    const d = new Date(dateStr);
    const diffMs = now - d;
    const diffSec = Math.floor(diffMs / 1000);
    if (diffSec < 60) return `${diffSec}s ago`;
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHour = Math.floor(diffMin / 60);
    if (diffHour < 24) return `${diffHour}h ago`;
    const diffDay = Math.floor(diffHour / 24);
    if (diffDay === 1) return 'Yesterday';
    if (diffDay < 7) return `${diffDay}d ago`;
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
};
</script>

<template>
    <Head title="Master Dashboard - Garages" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">Master Dashboard</h2>
                <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                    <Link :href="route('action-logs.index')" class="px-4 py-2 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition font-semibold text-sm flex items-center gap-1.5 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Audit Action Logs
                    </Link>
                    <button @click="showModal = true" class="w-full sm:w-auto px-4 py-2 bg-orange-500 text-white rounded-xl shadow-lg hover:bg-orange-600 transition font-semibold text-center">
                        + Add New Garage
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-700 p-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Garages</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ garages.length }}</h3>
                        </div>
                        <div class="h-12 w-12 rounded-xl bg-orange-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 shadow-sm sm:rounded-lg overflow-x-auto border border-gray-100 dark:border-zinc-700">
                    <table class="min-w-full text-left">
                        <thead class="bg-gray-50 dark:bg-zinc-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-sm text-gray-600 dark:text-gray-300">Garage Name</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 dark:text-gray-300">Status</th>
                                <th class="p-4 font-semibold text-sm text-gray-600 dark:text-gray-300 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                            <tr v-for="garage in garages" :key="garage.id" class="hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="p-4">
                                    <div class="font-medium text-gray-900 dark:text-white text-lg">{{ garage.name }}</div>
                                    <div class="mt-1">
                                        <button @click="openOwnersModal(garage)" class="text-sm font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded hover:bg-orange-100 transition">
                                            Owners ({{ garage.owners?.length || 0 }})
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span v-if="garage.is_active" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-medium">Active</span>
                                    <span v-else class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">Inactive</span>
                                </td>
                                <td class="p-4 text-right whitespace-nowrap space-x-2">
                                    <button @click="openActionLogsModal(garage)" class="px-3 py-2 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg transition font-medium text-sm inline-flex items-center gap-1.5" title="View Garage Action Logs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Action Logs
                                    </button>
                                    <button @click="toggleActive(garage.id)" :class="[garage.is_active ? 'text-orange-500 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50', 'px-3 py-2 rounded-lg transition font-medium text-sm']">
                                        {{ garage.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button @click="manageGarage(garage.id)" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-lg hover:opacity-80 transition">
                                        Manage Garage
                                    </button>
                                    <button @click="deleteGarage(garage.id)" class="px-3 py-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition font-medium text-sm">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Garage Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/75 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-8 max-w-md w-full shadow-2xl">
                <h3 class="text-xl font-bold mb-4 dark:text-white">Register New Garage</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Garage Name</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Owner Name</label>
                        <input v-model="form.owner_name" type="text" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Owner Email (Login)</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Owner Password</label>
                        <input v-model="form.password" type="password" required minlength="8" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-500 font-medium">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-orange-500 text-white rounded-xl shadow font-semibold hover:bg-orange-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Owners Modal -->
        <div v-if="showOwnersModal && selectedGarage" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/75 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-8 max-w-2xl w-full shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold dark:text-white">Manage Owners: {{ selectedGarage.name }}</h3>
                    <button @click="showOwnersModal = false" class="text-gray-500 hover:text-gray-700">Close</button>
                </div>

                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                
                <div class="mb-8">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Add New Owner</h4>
                    <form @submit.prevent="submitOwner" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input v-model="ownerForm.name" type="text" required class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input v-model="ownerForm.email" type="email" required class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                            <input v-model="ownerForm.phone" type="tel" class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                            <input v-model="ownerForm.password" type="password" required minlength="8" class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div>
                            <button type="submit" :disabled="ownerForm.processing" class="w-full px-4 py-2 bg-orange-500 text-white text-sm rounded-lg shadow font-semibold hover:bg-orange-600">Add</button>
                        </div>
                    </form>
                    <div v-if="ownerForm.errors.email" class="text-red-500 text-xs mt-1">{{ ownerForm.errors.email }}</div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Owners</h4>
                    <div class="space-y-3">
                        <div v-for="owner in selectedGarage.owners" :key="owner.id" class="flex flex-col bg-gray-50 dark:bg-zinc-900 p-3 rounded-xl border border-gray-100 dark:border-zinc-700">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-3">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ owner.name }}</div>
                                    <div class="text-xs text-gray-500">{{ owner.email }}</div>
                                </div>
                                
                                <div v-if="resettingOwnerId !== owner.id" class="flex flex-wrap items-center gap-2">
                                    <span :class="owner.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 text-xs rounded-full font-medium">
                                        {{ owner.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                    <button @click="toggleOwnerStatus(owner.id)" class="px-2 py-1 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-600 text-xs rounded-md font-medium hover:bg-gray-100 dark:hover:bg-zinc-700 transition">
                                        {{ owner.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button @click="initiatePasswordReset(owner.id)" class="px-2 py-1 bg-red-50 text-red-600 border border-red-100 text-xs rounded-md font-medium hover:bg-red-100 transition">
                                        Reset Password
                                    </button>
                                </div>
                            </div>

                            <div v-if="resettingOwnerId === owner.id" class="flex flex-col gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-zinc-700 w-full">
                                <div class="text-xs text-gray-500 font-medium">Reset Password (leave blank to auto-generate)</div>
                                <div class="flex gap-2 w-full">
                                    <input v-model="customPassword" type="text" placeholder="Enter new password..." class="flex-1 text-sm rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1">
                                    <button @click="submitPasswordReset(owner.id)" class="px-4 py-1 bg-green-500 text-white text-xs rounded-md font-medium hover:bg-green-600 transition shadow-sm">
                                        Save
                                    </button>
                                    <button @click="cancelPasswordReset" class="px-4 py-1 bg-gray-200 text-gray-700 dark:bg-zinc-700 dark:text-gray-300 text-xs rounded-md font-medium hover:bg-gray-300 transition shadow-sm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Logs Modal for Garage -->
        <div v-if="showActionLogsModal && selectedGarageForLogs" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-3xl p-6 sm:p-8 max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-zinc-700 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 dark:border-zinc-700 gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                Action Logs: {{ selectedGarageForLogs.name }}
                            </h3>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Auditing user actions, status transitions, inventory changes, and billing in this workshop (Total: {{ garageLogsTotal }})
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('action-logs.index', { garage_id: selectedGarageForLogs.id })"
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition inline-flex items-center gap-1"
                        >
                            Open Full View ↗
                        </Link>
                        <button
                            @click="showActionLogsModal = false"
                            class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Filters Bar -->
                <div class="py-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-zinc-700">
                    <div class="flex-1 relative">
                        <input
                            v-model="logsFilterSearch"
                            @keydown.enter="fetchGarageLogs(1)"
                            type="text"
                            placeholder="Filter actions by description, user, keyword..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        />
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="logsFilterModule"
                            @change="fetchGarageLogs(1)"
                            class="text-xs rounded-xl border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="all">All Modules</option>
                            <option value="job_cards">Job Cards</option>
                            <option value="inventory">Inventory</option>
                            <option value="purchases">Purchases</option>
                            <option value="expenses">Expenses</option>
                            <option value="sales">Counter Sales</option>
                            <option value="staff">Staff & Team</option>
                            <option value="customers">Customers</option>
                            <option value="settings">Settings</option>
                            <option value="garages">Garage Admin</option>
                            <option value="auth">Logins / Auth</option>
                        </select>
                        <button
                            @click="fetchGarageLogs(1)"
                            class="px-3 py-1.5 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 transition"
                        >
                            Search
                        </button>
                    </div>
                </div>

                <!-- Modal Logs Body -->
                <div class="flex-1 overflow-y-auto py-3 space-y-2">
                    <div v-if="loadingLogs" class="py-12 text-center text-xs text-gray-500">
                        <svg class="animate-spin h-6 w-6 text-indigo-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading audit logs for this garage...
                    </div>

                    <div v-else-if="garageLogs.length === 0" class="py-12 text-center text-xs text-gray-500">
                        No action logs recorded yet for this garage.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="log in garageLogs"
                            :key="log.id"
                            class="p-3 bg-gray-50 dark:bg-zinc-900 rounded-xl border border-gray-100 dark:border-zinc-700 text-xs flex flex-col gap-1.5"
                        >
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                        {{ log.module }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full font-semibold text-[10px] bg-gray-200 text-gray-800 dark:bg-zinc-700 dark:text-gray-200">
                                        {{ log.action }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ log.user_name || 'System' }}
                                        <span class="text-[10px] text-gray-400 capitalize" v-if="log.user_role">({{ log.user_role }})</span>
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-400 font-mono">
                                    {{ formatRelativeTime(log.created_at) }}
                                </div>
                            </div>

                            <p class="text-gray-700 dark:text-gray-300">{{ log.description }}</p>

                            <div v-if="log.details || log.ip_address" class="flex items-center justify-between text-[10px] text-gray-400 pt-1 border-t border-gray-200/50 dark:border-zinc-800">
                                <span v-if="log.ip_address">IP: {{ log.ip_address }}</span>
                                <span v-else></span>
                                <button
                                    v-if="log.details"
                                    @click="toggleLogDetail(log.id)"
                                    class="text-indigo-600 hover:text-indigo-700 font-medium"
                                >
                                    {{ expandedLogIds[log.id] ? 'Hide Details' : 'View Payload' }}
                                </button>
                            </div>

                            <div v-if="expandedLogIds[log.id] && log.details" class="p-2 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 font-mono text-[10px] overflow-x-auto">
                                <pre class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ JSON.stringify(log.details, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Pagination -->
                <div v-if="logsTotalPages > 1" class="pt-3 border-t border-gray-100 dark:border-zinc-700 flex items-center justify-between text-xs text-gray-500">
                    <div>Page {{ logsPage }} of {{ logsTotalPages }}</div>
                    <div class="flex items-center gap-2">
                        <button
                            :disabled="logsPage <= 1 || loadingLogs"
                            @click="fetchGarageLogs(logsPage - 1)"
                            class="px-3 py-1 bg-gray-100 dark:bg-zinc-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 text-xs font-semibold"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="logsPage >= logsTotalPages || loadingLogs"
                            @click="fetchGarageLogs(logsPage + 1)"
                            class="px-3 py-1 bg-gray-100 dark:bg-zinc-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 text-xs font-semibold"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
