<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reminders: Array,
    stats: Object,
    currentFilter: String,
    search: String,
    garage: Object,
    today: String,
});

const searchQuery = ref(props.search || '');

const handleSearch = () => {
    router.get(route('service-reminders.index'), {
        filter: props.currentFilter,
        search: searchQuery.value,
    }, { preserveState: true, replace: true });
};

const clearSearch = () => {
    searchQuery.value = '';
    handleSearch();
};

const filterBy = (filterName) => {
    router.get(route('service-reminders.index'), {
        filter: filterName,
        search: searchQuery.value,
    }, { preserveState: true });
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

const getRelativeDueText = (dueDate) => {
    if (!dueDate) return '';
    const due = new Date(dueDate);
    const today = new Date(props.today);
    const diffTime = due - today;
    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return { text: `${Math.abs(diffDays)} days overdue`, badgeClass: 'bg-rose-100 text-rose-800 border-rose-200' };
    } else if (diffDays === 0) {
        return { text: 'Due today!', badgeClass: 'bg-amber-100 text-amber-800 border-amber-200' };
    } else if (diffDays <= 7) {
        return { text: `Due in ${diffDays} day${diffDays > 1 ? 's' : ''}`, badgeClass: 'bg-amber-50 text-amber-700 border-amber-200' };
    } else {
        return { text: `Due in ${diffDays} days`, badgeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
    }
};

const openWhatsAppReminder = (item) => {
    const customerPhone = item.customer?.user?.phone || '';
    const cleanPhone = customerPhone.replace(/[^0-9]/g, '');
    const phoneWithCountry = cleanPhone.length === 10 ? `91${cleanPhone}` : cleanPhone;

    const customerName = item.customer?.user?.name || 'Valued Customer';
    const vehicleName = `${item.vehicle?.brand?.name || ''} ${item.vehicle?.vehicle_model?.name || ''}`.trim() || 'Vehicle';
    const regNumber = item.vehicle?.registration_number || '';
    const garageName = props.garage?.name || 'Our Workshop';
    const dueDate = new Date(item.next_service_reminder).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });

    let bookingUrl = '';
    if (props.garage?.slug) {
        bookingUrl = `${window.location.origin}/book/${props.garage.slug}`;
    }

    let message = `Hello *${customerName}*,\n\n`;
    message += `This is a friendly service reminder from *${garageName}* 🔧\n\n`;
    message += `Your vehicle *${vehicleName}* (${regNumber}) is due for periodic maintenance service around *${dueDate}*.\n\n`;
    message += `Regular service keeps your vehicle running smoothly, safely, and extends engine life.\n\n`;
    
    if (bookingUrl) {
        message += `📅 Book your appointment online in 1-click:\n${bookingUrl}\n\n`;
    }
    message += `Or simply reply to this message to schedule your convenient slot. Thank you!`;

    const encoded = encodeURIComponent(message);
    const waUrl = `https://wa.me/${phoneWithCountry}?text=${encoded}`;
    
    // Open WhatsApp
    window.open(waUrl, '_blank');

    // Automatically mark sent on backend
    router.post(route('service-reminders.mark-sent', item.id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Next Service Reminders" />

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="p-2 rounded-xl bg-indigo-100 text-indigo-700">🔔</span>
                        Next Service Reminders
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Track upcoming maintenance schedules and re-engage customers via 1-click WhatsApp alerts.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('settings.edit')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 shadow-xs transition"
                    >
                        ⚙️ Reminder Interval: <strong>{{ garage.default_service_reminder_days }} days</strong>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <button
                    type="button"
                    @click="filterBy('overdue')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        currentFilter === 'overdue' ? 'ring-2 ring-rose-500 bg-rose-50/50 border-rose-300' : 'bg-white border-gray-200 hover:border-rose-300'
                    ]"
                >
                    <div class="flex items-center justify-between text-rose-600">
                        <span class="text-xs font-bold uppercase tracking-wider">Overdue</span>
                        <span class="text-lg">🚨</span>
                    </div>
                    <div class="text-2xl font-black text-rose-700 mt-2">{{ stats.overdue }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Need immediate alert</div>
                </button>

                <button
                    type="button"
                    @click="filterBy('this_week')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        currentFilter === 'this_week' ? 'ring-2 ring-amber-500 bg-amber-50/50 border-amber-300' : 'bg-white border-gray-200 hover:border-amber-300'
                    ]"
                >
                    <div class="flex items-center justify-between text-amber-600">
                        <span class="text-xs font-bold uppercase tracking-wider">Due This Week</span>
                        <span class="text-lg">⏱️</span>
                    </div>
                    <div class="text-2xl font-black text-amber-700 mt-2">{{ stats.this_week }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Next 7 days</div>
                </button>

                <button
                    type="button"
                    @click="filterBy('this_month')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        currentFilter === 'this_month' ? 'ring-2 ring-indigo-500 bg-indigo-50/50 border-indigo-300' : 'bg-white border-gray-200 hover:border-indigo-300'
                    ]"
                >
                    <div class="flex items-center justify-between text-indigo-600">
                        <span class="text-xs font-bold uppercase tracking-wider">Due This Month</span>
                        <span class="text-lg">📅</span>
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-2">{{ stats.this_month }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Next 8 to 30 days</div>
                </button>

                <button
                    type="button"
                    @click="filterBy('all')"
                    :class="[
                        'p-4 rounded-xl border text-left transition-all',
                        currentFilter === 'all' ? 'ring-2 ring-gray-600 bg-gray-50 border-gray-300' : 'bg-white border-gray-200 hover:border-gray-300'
                    ]"
                >
                    <div class="flex items-center justify-between text-gray-600">
                        <span class="text-xs font-bold uppercase tracking-wider">All Tracked</span>
                        <span class="text-lg">🚘</span>
                    </div>
                    <div class="text-2xl font-black text-gray-800 mt-2">{{ stats.total }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Total registered</div>
                </button>

                <button
                    type="button"
                    @click="filterBy('sent')"
                    :class="[
                        'p-4 rounded-xl border text-left col-span-2 lg:col-span-1 transition-all',
                        currentFilter === 'sent' ? 'ring-2 ring-emerald-500 bg-emerald-50/50 border-emerald-300' : 'bg-white border-gray-200 hover:border-emerald-300'
                    ]"
                >
                    <div class="flex items-center justify-between text-emerald-600">
                        <span class="text-xs font-bold uppercase tracking-wider">Reminders Sent</span>
                        <span class="text-lg">✅</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-2">{{ stats.sent }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">Messages dispatched</div>
                </button>
            </div>

            <!-- Controls: Search & Tabs -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Search bar -->
                <div class="w-full md:w-96 relative">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        placeholder="Search vehicle reg, customer name or phone..."
                        class="w-full pl-10 pr-10 py-2 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50"
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

                <!-- Filter Pills -->
                <div class="flex items-center gap-1.5 flex-wrap w-full md:w-auto">
                    <button
                        type="button"
                        @click="filterBy('all')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', currentFilter === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        @click="filterBy('overdue')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', currentFilter === 'overdue' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100']"
                    >
                        🚨 Overdue ({{ stats.overdue }})
                    </button>
                    <button
                        type="button"
                        @click="filterBy('this_week')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', currentFilter === 'this_week' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100']"
                    >
                        ⏱️ This Week ({{ stats.this_week }})
                    </button>
                    <button
                        type="button"
                        @click="filterBy('this_month')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', currentFilter === 'this_month' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100']"
                    >
                        📅 This Month ({{ stats.this_month }})
                    </button>
                    <button
                        type="button"
                        @click="filterBy('sent')"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', currentFilter === 'sent' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100']"
                    >
                        ✅ Sent ({{ stats.sent }})
                    </button>
                </div>
            </div>

            <!-- Reminders List -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="reminders.length === 0" class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        🔔
                    </div>
                    <h3 class="text-base font-bold text-gray-800">No service reminders found</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1">
                        When you create job cards, next service reminders are automatically scheduled based on your garage settings.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vehicle</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Next Service Due</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Last Visit</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="item in reminders" :key="item.id" class="hover:bg-slate-50/70 transition">
                                <!-- Vehicle -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="inline-flex items-center bg-white border border-gray-300 rounded shadow-xs overflow-hidden shrink-0">
                                            <div class="bg-[#1a56db] flex flex-col items-center justify-center px-1.5 py-1 text-white">
                                                <span class="text-[7px] font-bold leading-none">IND</span>
                                            </div>
                                            <div class="px-2 py-0.5 font-black text-gray-900 uppercase tracking-wider text-xs">
                                                {{ formatLicensePlate(item.vehicle?.registration_number) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-gray-900">
                                                {{ item.vehicle?.brand?.name }} {{ item.vehicle?.vehicle_model?.name }}
                                            </div>
                                            <Link
                                                v-if="item.vehicle_id"
                                                :href="route('vehicles.show', item.vehicle_id)"
                                                class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold"
                                            >
                                                View Logbook &rarr;
                                            </Link>
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-900">{{ item.customer?.user?.name || 'Walk-in Customer' }}</div>
                                    <div class="text-xs text-gray-500">{{ item.customer?.user?.phone || 'No Phone' }}</div>
                                </td>

                                <!-- Due Date & Badge -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-xs font-bold text-gray-900">
                                        {{ new Date(item.next_service_reminder).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </div>
                                    <div class="mt-1">
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border', getRelativeDueText(item.next_service_reminder).badgeClass]">
                                            {{ getRelativeDueText(item.next_service_reminder).text }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Last Visit -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <Link :href="route('job-cards.show', item.id)" class="text-xs font-bold text-indigo-600 hover:underline">
                                        {{ item.job_number }}
                                    </Link>
                                    <div class="text-[11px] text-gray-500">
                                        {{ item.vehicle_running_km ? `${item.vehicle_running_km} KM` : 'KM not recorded' }}
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span v-if="item.service_reminder_sent_at" class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                            ✓ Sent
                                        </span>
                                        <button
                                            type="button"
                                            @click="openWhatsAppReminder(item)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#25D366] text-white hover:bg-[#1ebd59] shadow-xs transition active:scale-95"
                                            title="Send WhatsApp Reminder"
                                        >
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                            </svg>
                                            <span>WhatsApp</span>
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
