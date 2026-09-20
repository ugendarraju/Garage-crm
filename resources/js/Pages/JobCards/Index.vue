<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    jobCards: Array
});

const form = useForm({});

const deleteJobCard = (id) => {
    if (confirm('Are you sure you want to delete this job card?')) {
        form.delete(route('job-cards.destroy', id), {
            preserveScroll: true,
        });
    }
};

const updateStatus = (id, event) => {
    router.patch(route('job-cards.status', id), {
        status: event.target.value
    }, {
        preserveScroll: true,
    });
};

const activeTab = ref('All Jobs');
const viewMode = ref('grid'); // 'grid' or 'table'
const searchQuery = ref('');

const tabs = ['All Jobs', 'Estimates', 'Pending', 'In Progress', 'Completed', 'Delivered'];

const filteredJobCards = computed(() => {
    return props.jobCards.filter(job => {
        // Tab filtering
        if (activeTab.value !== 'All Jobs') {
            const mappedStatus = activeTab.value.toLowerCase().replace(' ', '_');
            if (activeTab.value === 'In Progress' && job.status !== 'working') return false;
            if (activeTab.value === 'Pending' && job.status !== 'listed') return false;
            if (activeTab.value === 'Completed' && job.status !== 'complete') return false;
            if (activeTab.value === 'Estimates' && job.status !== 'scheduled') return false;
            if (activeTab.value === 'Delivered' && job.status !== 'delivered') return false;
        }

        // Search filtering
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const matchJob = job.job_number.toLowerCase().includes(query);
            const matchCustomer = job.customer?.user?.name.toLowerCase().includes(query) || job.customer?.user?.phone.includes(query);
            const matchVehicle = job.vehicle?.registration_number.toLowerCase().includes(query);
            if (!matchJob && !matchCustomer && !matchVehicle) return false;
        }

        return true;
    });
});

const getStatusColor = (status) => {
    switch (status) {
        case 'complete': return 'bg-green-100 text-green-700 border-green-200';
        case 'working': return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'listed': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'scheduled': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'delivered': return 'bg-blue-100 text-blue-700 border-blue-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'complete': return 'Completed';
        case 'working': return 'In Progress';
        case 'listed': return 'Pending';
        case 'scheduled': return 'Estimate';
        case 'delivered': return 'Delivered';
        default: return status ? (status.charAt(0).toUpperCase() + status.slice(1)) : '';
    }
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 'High': return 'bg-red-100 text-red-700 border-red-200';
        case 'Medium': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'Low': return 'bg-green-100 text-green-700 border-green-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatLicensePlate = (plate) => {
    if (!plate) return 'UNKNOWN';
    let clean = plate.replace(/[^A-Z0-9]/gi, '').toUpperCase();
    const match = clean.match(/^([A-Z]{2})(\d{1,2})([A-Z]{0,3})(\d{1,4})$/);
    if (match) {
        const parts = [match[1], match[2]];
        if (match[3]) parts.push(match[3]);
        if (match[4]) parts.push(match[4]);
        return parts.join(' ');
    }
    return clean.replace(/([A-Z])(\d)/g, '$1 $2').replace(/(\d)([A-Z])/g, '$1 $2');
};
</script>

<template>
    <Head title="Job Cards" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Job Cards</h1>
                    <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-500 font-medium">Manage all your garage job cards and track service progress.</p>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 self-start sm:self-auto" v-if="$page.props.auth.user.role !== 'customer'">
                    <Link :href="route('job-cards.create')" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-3.5 py-2 sm:px-4 sm:py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-primary-600 transition-colors">
                        <svg class="-ml-1 mr-1.5 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">Create </span>New Jobcard
                    </Link>
                </div>
            </div>
        </template>

        <div class="mt-4">
            <!-- Tabs -->
            <div class="flex justify-start sm:justify-center overflow-x-auto pb-2 sm:pb-0 mb-6 sm:mb-8 no-scrollbar">
                <nav class="flex space-x-1 rounded-full bg-gray-200/50 p-1 shrink-0" aria-label="Tabs">
                    <button v-for="tab in tabs" :key="tab" @click="activeTab = tab" 
                            :class="[activeTab === tab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900', 'rounded-full px-3.5 sm:px-5 py-1.5 sm:py-2 text-xs sm:text-sm font-bold transition-all duration-200 whitespace-nowrap']">
                        {{ tab }}
                    </button>
                </nav>
            </div>

            <!-- Filters & Controls -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 mb-6 sm:mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex-1 w-full relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" class="block w-full rounded-lg border-0 py-2 sm:py-2.5 pl-9 sm:pl-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 text-xs sm:text-sm" placeholder="Search job cards, customer, vehicle...">
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <select class="rounded-lg border-0 py-2 sm:py-2.5 pl-3 sm:pl-4 pr-8 sm:pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 text-xs sm:text-sm">
                            <option>All Status</option>
                        </select>
                        <button class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors whitespace-nowrap">
                            <svg class="mr-1.5 h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            <span class="hidden sm:inline">Show </span>Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center bg-gray-100 p-1 rounded-lg">
                    <button @click="viewMode = 'grid'" :class="[viewMode === 'grid' ? 'bg-primary-700 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900', 'rounded-md px-3 py-1.5 text-sm font-semibold transition-all flex items-center']">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Grid
                    </button>
                    <button @click="viewMode = 'table'" :class="[viewMode === 'table' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900', 'rounded-md px-3 py-1.5 text-sm font-semibold transition-all flex items-center']">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Table
                    </button>
                </div>
                <div class="text-sm text-gray-500 font-medium">
                    Showing {{ filteredJobCards.length }} Job Cards
                </div>
            </div>

            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="job in filteredJobCards" :key="job.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col transition-shadow hover:shadow-md">
                    <!-- Top section with Plate -->
                    <div class="p-5 pb-0 bg-gradient-to-b from-blue-50/50 to-white relative">
                        <div class="flex justify-center mb-4">
                            <!-- License Plate (Stylized) -->
                            <div class="inline-flex items-center bg-white border border-gray-300 rounded shadow-sm overflow-hidden">
                                <div class="bg-[#1a56db] flex flex-col items-center justify-center px-2 py-1.5 text-white">
                                    <div class="w-3 h-3 rounded-full border-[1.5px] border-white flex items-center justify-center mb-0.5">
                                        <div class="w-0.5 h-0.5 bg-white rounded-full"></div>
                                    </div>
                                    <span class="text-[8px] font-bold leading-none">IND</span>
                                </div>
                                <div class="px-3 py-1 font-black text-gray-900 uppercase tracking-widest text-lg">
                                    {{ formatLicensePlate(job.vehicle?.registration_number) }}
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <h3 class="text-base font-bold text-gray-900">{{ job.vehicle?.brand?.name }} {{ job.vehicle?.vehicle_model?.name }}</h3>
                            <p class="text-xs font-medium text-gray-400 mt-0.5"><Link :href="route('job-cards.show', job.id)" class="text-indigo-600 hover:text-indigo-900 underline">{{ job.job_number }}</Link> - {{ formatDate(job.created_at) }}</p>
                        </div>
                        <div class="flex justify-center gap-2 mt-3 mb-4">
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border', getPriorityColor(job.priority)]">
                                {{ job.priority || 'Medium' }}
                            </span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border', getStatusColor(job.status)]">
                                {{ getStatusLabel(job.status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-100"></div>

                    <!-- Customer Info -->
                    <div class="p-5 flex-1">
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-gray-900">{{ job.customer?.user?.name }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ job.customer?.user?.phone }}</p>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-xs font-medium text-gray-500">Total Cost:</span>
                            <span class="text-lg font-black text-gray-900">₹{{ job.final_total ? Number(job.final_total).toLocaleString('en-IN') : '0' }}</span>
                        </div>
                        <div class="mt-4" v-if="$page.props.auth.user.role !== 'customer'">
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Change Status:</label>
                            <select @change="updateStatus(job.id, $event)" class="block w-full rounded-lg border-gray-300 py-1.5 pl-3 pr-10 text-gray-900 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500">
                                <option value="scheduled" :selected="job.status === 'scheduled'">Estimate</option>
                                <option value="listed" :selected="job.status === 'listed'">Pending</option>
                                <option value="working" :selected="job.status === 'working'">In Progress</option>
                                <option value="complete" :selected="job.status === 'complete'">Completed</option>
                                <option value="delivered" :selected="job.status === 'delivered'">Delivered</option>
                            </select>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50/50 border-t border-gray-100">
                        <div class="grid grid-cols-2 gap-2">
                            <Link :href="route('job-cards.show', job.id)" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                                <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                View
                            </Link>
                            <Link v-if="$page.props.auth.user.role !== 'customer'" :href="route('job-cards.edit', job.id)" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                                <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                Edit
                            </Link>
                            <button v-if="$page.props.auth.user.role !== 'customer'" @click="deleteJobCard(job.id)" type="button" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100 transition-colors shadow-sm">
                                <svg class="mr-1.5 h-3.5 w-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Delete
                            </button>
                            <a :href="route('job-cards.invoice', job.id)" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                                <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredJobCards.length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-200">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-gray-900">No Job Cards found</h3>
                <p v-if="$page.props.auth.user.role !== 'customer'" class="mt-1 text-sm text-gray-500">Get started by creating a new job card.</p>
                <p v-else class="mt-1 text-sm text-gray-500">Book a service to get started.</p>
                <div class="mt-6">
                    <Link v-if="$page.props.auth.user.role !== 'customer'" :href="route('job-cards.create')" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 transition-colors">
                        <svg class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Create New Jobcard
                    </Link>
                    <Link v-else :href="route('service-requests.create')" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 transition-colors">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Raise Service Request
                    </Link>
                </div>
            </div>
            
            <!-- Table View -->
            <div v-else-if="viewMode === 'table'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Job Details</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vehicle</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status & Priority</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="job in filteredJobCards" :key="job.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="route('job-cards.show', job.id)" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 underline block mb-1">{{ job.job_number }}</Link>
                                    <div class="text-xs text-gray-500">{{ formatDate(job.created_at) }}</div>
                                    <div class="text-sm font-bold text-gray-900 mt-1">₹{{ job.final_total ? Number(job.final_total).toLocaleString('en-IN') : '0' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ job.customer?.user?.name || 'Walk-in' }}</div>
                                    <div class="text-xs text-gray-500">{{ job.customer?.user?.phone || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="inline-flex items-center bg-white border border-gray-300 rounded shadow-sm overflow-hidden mb-1">
                                        <div class="bg-[#1a56db] flex flex-col items-center justify-center px-1.5 py-1 text-white">
                                            <div class="w-2.5 h-2.5 rounded-full border-[1px] border-white flex items-center justify-center mb-0.5">
                                                <div class="w-[1.5px] h-[1.5px] bg-white rounded-full"></div>
                                            </div>
                                            <span class="text-[6px] font-bold leading-none">IND</span>
                                        </div>
                                        <div class="px-2 py-0.5 font-black text-gray-900 uppercase tracking-widest text-sm">
                                            {{ formatLicensePlate(job.vehicle?.registration_number) }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ job.vehicle?.brand?.name }} {{ job.vehicle?.vehicle_model?.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border', getStatusColor(job.status)]">
                                            {{ getStatusLabel(job.status) }}
                                        </span>
                                        <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border', getPriorityColor(job.priority)]">
                                            {{ job.priority || 'Medium' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <select v-if="$page.props.auth.user.role !== 'customer'" @change="updateStatus(job.id, $event)" class="text-xs rounded border-gray-300 py-1 pl-2 pr-6 text-gray-700 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="scheduled" :selected="job.status === 'scheduled'">Estimate</option>
                                            <option value="listed" :selected="job.status === 'listed'">Pending</option>
                                            <option value="working" :selected="job.status === 'working'">In Progress</option>
                                            <option value="complete" :selected="job.status === 'complete'">Completed</option>
                                            <option value="delivered" :selected="job.status === 'delivered'">Delivered</option>
                                        </select>
                                        <Link :href="route('job-cards.show', job.id)" class="text-blue-600 hover:text-blue-900" title="View">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <Link v-if="$page.props.auth.user.role !== 'customer'" :href="route('job-cards.edit', job.id)" class="text-gray-600 hover:text-gray-900" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </Link>
                                        <a :href="route('job-cards.invoice', job.id)" target="_blank" class="text-green-600 hover:text-green-900" title="Download Invoice">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        </a>
                                        <button v-if="$page.props.auth.user.role !== 'customer'" @click="deleteJobCard(job.id)" class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>