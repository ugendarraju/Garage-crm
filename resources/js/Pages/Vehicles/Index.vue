<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    vehicles: Object,
    search: String,
});

const searchQuery = ref(props.search || '');

const handleSearch = () => {
    router.get(route('vehicles.index'), {
        search: searchQuery.value,
    }, { preserveState: true, replace: true });
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
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
    return clean;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Vehicle Database & Logbooks" />

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="p-2 rounded-xl bg-blue-100 text-blue-700">🚘</span>
                        Vehicles & Service Logbooks
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Search vehicle registration numbers to instantly review entire historical service records, parts replaced, and KM history.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('job-cards.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-sm transition"
                    >
                        + Create Job Card
                    </Link>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-96 relative">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        placeholder="Search plate (e.g. DL01AB1234), brand, owner..."
                        class="w-full pl-10 pr-10 py-2.5 text-sm border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-gray-50"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    >
                        ✕
                    </button>
                </div>

                <div class="text-xs font-semibold text-gray-500">
                    Showing {{ vehicles.total || vehicles.data?.length || 0 }} vehicles
                </div>
            </div>

            <!-- Vehicles Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="!vehicles.data || vehicles.data.length === 0" class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        🚘
                    </div>
                    <h3 class="text-base font-bold text-gray-800">No vehicles found</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1">
                        Vehicles are automatically registered when creating job cards or customer profiles.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Registration Plate</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Make & Model</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer / Owner</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Workshop Visits</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="vehicle in vehicles.data" :key="vehicle.id" class="hover:bg-slate-50/70 transition">
                                <!-- Plate -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <Link :href="route('vehicles.show', vehicle.id)" class="inline-flex items-center bg-white border border-gray-300 rounded shadow-xs overflow-hidden hover:border-primary-500 transition">
                                        <div class="bg-[#1a56db] flex flex-col items-center justify-center px-1.5 py-1 text-white">
                                            <span class="text-[7px] font-bold leading-none">IND</span>
                                        </div>
                                        <div class="px-2.5 py-0.5 font-black text-gray-900 uppercase tracking-wider text-xs">
                                            {{ formatLicensePlate(vehicle.registration_number) }}
                                        </div>
                                    </Link>
                                </td>

                                <!-- Make & Model -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-900">
                                        {{ vehicle.brand?.name || 'Unknown Make' }} {{ vehicle.vehicle_model?.name || '' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500">
                                        {{ vehicle.vehicle_type || 'Car' }} &bull; {{ vehicle.fuel_type || 'Petrol' }}
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-900">{{ vehicle.customer?.user?.name || 'Walk-in Customer' }}</div>
                                    <div class="text-xs text-gray-500">{{ vehicle.customer?.user?.phone || 'No Phone' }}</div>
                                </td>

                                <!-- Visits Count -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ vehicle.job_cards_count || 0 }} Visit{{ vehicle.job_cards_count === 1 ? '' : 's' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <Link
                                        :href="route('vehicles.show', vehicle.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 transition"
                                    >
                                        <span>📜 View Logbook</span>
                                        <span class="text-primary-500">&rarr;</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="vehicles.links && vehicles.links.length > 3" class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in vehicles.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 text-xs rounded font-semibold',
                                link.active ? 'bg-primary-600 text-white' : 'text-gray-700 hover:bg-gray-100',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
