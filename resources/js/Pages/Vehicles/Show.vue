<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';

const props = defineProps({
    vehicle: Object,
    stats: Object,
});

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

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'delivered':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'complete':
        case 'completed':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'working':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'listed':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Logbook - ${vehicle.registration_number}`" />

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Breadcrumb & Back -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                    <Link :href="route('vehicles.index')" class="hover:text-primary-600 transition">&larr; Back to Vehicles</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-bold">{{ vehicle.registration_number }}</span>
                </div>

                <Link
                    :href="route('job-cards.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-primary-600 rounded-lg hover:bg-primary-700 shadow-xs transition"
                >
                    + Create Job Card
                </Link>
            </div>

            <!-- Vehicle Profile Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 overflow-hidden relative">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                        <!-- License Plate Large IND -->
                        <div class="inline-flex items-center bg-white border-2 border-gray-300 rounded-xl shadow-sm overflow-hidden shrink-0 self-start">
                            <div class="bg-[#1a56db] flex flex-col items-center justify-center px-2.5 py-2 text-white">
                                <div class="w-3.5 h-3.5 rounded-full border-[1.5px] border-white flex items-center justify-center mb-0.5">
                                    <div class="w-1 h-1 bg-white rounded-full"></div>
                                </div>
                                <span class="text-[9px] font-bold leading-none">IND</span>
                            </div>
                            <div class="px-4 py-2 font-black text-gray-900 uppercase tracking-widest text-xl sm:text-2xl font-mono">
                                {{ formatLicensePlate(vehicle.registration_number) }}
                            </div>
                        </div>

                        <!-- Vehicle Specs -->
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-gray-900">
                                {{ vehicle.brand?.name || 'Unknown' }} {{ vehicle.vehicle_model?.name || '' }}
                            </h1>
                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-1 font-medium">
                                <span>Type: <strong>{{ vehicle.vehicle_type || 'Car' }}</strong></span>
                                <span>&bull;</span>
                                <span>Fuel: <strong>{{ vehicle.fuel_type || 'Petrol' }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Details -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-gray-200/80 min-w-[260px]">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Registered Owner</p>
                        <p class="text-sm font-bold text-gray-900">{{ vehicle.customer?.user?.name || 'Walk-in Customer' }}</p>
                        <p class="text-xs text-gray-600 mt-0.5">{{ vehicle.customer?.user?.phone || 'No Phone' }}</p>
                        <a
                            v-if="vehicle.customer?.user?.phone"
                            :href="`https://wa.me/${vehicle.customer.user.phone.replace(/[^0-9]/g, '')}`"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-[#128C7E] hover:underline mt-2"
                        >
                            <span>💬 Message on WhatsApp</span>
                        </a>
                    </div>
                </div>

                <!-- KPI Metric Strip -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mt-6 pt-6 border-t border-gray-100">
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Total Visits</span>
                        <div class="text-xl sm:text-2xl font-black text-gray-900 mt-1">
                            {{ stats.total_visits }}
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Lifetime Spent</span>
                        <div class="text-xl sm:text-2xl font-black text-primary-600 mt-1">
                            ₹{{ Number(stats.lifetime_spend || 0).toLocaleString('en-IN') }}
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Latest Odometer</span>
                        <div class="text-xl sm:text-2xl font-black text-gray-900 mt-1">
                            {{ stats.latest_km ? `${stats.latest_km} KM` : 'N/A' }}
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Next Service</span>
                        <div class="text-sm sm:text-base font-bold text-gray-900 mt-1">
                            {{ stats.next_reminder ? format(new Date(stats.next_reminder), 'dd MMM yyyy') : 'Not scheduled' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Complete Service History Timeline -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>📜</span>
                            Digital Service Logbook & Visit Timeline
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Chronological record of every repair, maintenance task, and part replaced</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!vehicle.job_cards || vehicle.job_cards.length === 0" class="text-center py-12">
                    <p class="text-sm font-semibold text-gray-500">No service history recorded for this vehicle yet.</p>
                </div>

                <!-- Timeline List -->
                <div v-else class="relative border-l-2 border-primary-200 ml-4 sm:ml-6 space-y-8">
                    <div
                        v-for="(jobCard, idx) in vehicle.job_cards"
                        :key="jobCard.id"
                        class="relative pl-6 sm:pl-8"
                    >
                        <!-- Timeline Node Dot -->
                        <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-primary-600 border-4 border-white shadow-xs"></div>

                        <!-- Visit Card -->
                        <div class="bg-slate-50/70 hover:bg-slate-50 border border-gray-200 rounded-xl p-5 shadow-xs transition">
                            <!-- Visit Header -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-200/80">
                                <div class="flex items-center gap-3">
                                    <Link :href="route('job-cards.show', jobCard.id)" class="text-base font-extrabold text-primary-700 hover:underline">
                                        {{ jobCard.job_number }}
                                    </Link>
                                    <span :class="['px-2.5 py-0.5 text-xs font-bold uppercase rounded-full border', getStatusBadgeClass(jobCard.status)]">
                                        {{ jobCard.status }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-4 text-xs font-semibold text-gray-500">
                                    <span class="flex items-center gap-1">
                                        📅 {{ jobCard.created_at ? format(new Date(jobCard.created_at), 'dd MMM yyyy') : 'N/A' }}
                                    </span>
                                    <span v-if="jobCard.vehicle_running_km" class="bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-700">
                                        🛣️ {{ jobCard.vehicle_running_km }} KM
                                    </span>
                                    <span class="text-base font-black text-gray-900">
                                        ₹{{ Number(jobCard.final_total || 0).toLocaleString('en-IN') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Mechanics Assigned -->
                            <div v-if="jobCard.mechanics && jobCard.mechanics.length > 0" class="text-xs text-gray-500 mt-2.5">
                                Mechanic: <strong class="text-gray-700">{{ jobCard.mechanics.map(m => m.name).join(', ') }}</strong>
                            </div>

                            <!-- Reported Issues -->
                            <div v-if="jobCard.issues && jobCard.issues.length > 0" class="mt-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Reported Complaints:</p>
                                <ul class="list-disc list-inside text-xs text-gray-700 space-y-0.5">
                                    <li v-for="issue in jobCard.issues" :key="issue.id">{{ issue.description }}</li>
                                </ul>
                            </div>

                            <!-- Parts & Services Replaced -->
                            <div v-if="jobCard.items && jobCard.items.length > 0" class="mt-4 pt-3 border-t border-gray-200/60">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-2">Work Done & Parts Replaced:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    <div
                                        v-for="item in jobCard.items"
                                        :key="item.id"
                                        class="flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 text-xs"
                                    >
                                        <div class="truncate pr-2">
                                            <span class="font-bold text-gray-800">{{ item.name }}</span>
                                            <span class="text-[10px] text-gray-400 block">{{ item.type }}</span>
                                        </div>
                                        <span class="font-bold text-gray-900 shrink-0">₹{{ Number(item.total).toLocaleString('en-IN') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Action -->
                            <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between text-xs">
                                <span class="text-gray-500">
                                    Next Service Reminder: <strong>{{ jobCard.next_service_reminder ? format(new Date(jobCard.next_service_reminder), 'dd MMM yyyy') : 'None' }}</strong>
                                </span>
                                <Link
                                    :href="route('job-cards.show', jobCard.id)"
                                    class="font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                                >
                                    <span>Full Job Card & Invoice</span>
                                    <span>&rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
