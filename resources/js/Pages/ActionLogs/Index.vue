<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsNav from '@/Components/SettingsNav.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    garages: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            today: 0,
            this_week: 0,
            critical: 0,
        }),
    },
    isMasterAdmin: Boolean,
    selectedGarageId: [Number, String, null],
});

const filterForm = reactive({
    search: props.filters?.search || '',
    module: props.filters?.module || 'all',
    action: props.filters?.action || 'all',
    user_id: props.filters?.user_id || 'all',
    date_range: props.filters?.date_range || 'all',
    garage_id: props.filters?.garage_id || (props.selectedGarageId || ''),
});

const expandedRows = ref({});

const toggleRow = (id) => {
    expandedRows.value[id] = !expandedRows.value[id];
};

const applyFilters = () => {
    router.get(route('action-logs.index'), {
        search: filterForm.search || undefined,
        module: filterForm.module !== 'all' ? filterForm.module : undefined,
        action: filterForm.action !== 'all' ? filterForm.action : undefined,
        user_id: filterForm.user_id !== 'all' ? filterForm.user_id : undefined,
        date_range: filterForm.date_range !== 'all' ? filterForm.date_range : undefined,
        garage_id: filterForm.garage_id ? filterForm.garage_id : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.module = 'all';
    filterForm.action = 'all';
    filterForm.user_id = 'all';
    filterForm.date_range = 'all';
    if (props.isMasterAdmin) {
        filterForm.garage_id = '';
    }
    applyFilters();
};

const exportCsv = () => {
    const params = new URLSearchParams();
    if (filterForm.garage_id) params.append('garage_id', filterForm.garage_id);
    if (filterForm.module && filterForm.module !== 'all') params.append('module', filterForm.module);
    if (filterForm.action && filterForm.action !== 'all') params.append('action', filterForm.action);
    if (filterForm.search) params.append('search', filterForm.search);

    window.location.href = route('action-logs.export') + '?' + params.toString();
};

const formatModuleLabel = (mod) => {
    const map = {
        job_cards: 'Job Cards',
        inventory: 'Inventory',
        purchases: 'Purchases',
        sales: 'Counter Sales',
        expenses: 'Expenses',
        service_requests: 'Service Bookings',
        staff: 'Staff & Team',
        customers: 'Customers',
        settings: 'Settings & Branding',
        garages: 'Garage Management',
        auth: 'Authentication',
        system: 'System Backups',
    };
    return map[mod] || mod;
};

const getActionBadgeClass = (action) => {
    if (['create', 'created', 'added'].includes(action)) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300';
    }
    if (['delete', 'deleted', 'removed'].includes(action)) {
        return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300';
    }
    if (['status_change', 'status_changed'].includes(action)) {
        return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300';
    }
    if (['payment', 'payment_received', 'payment_status'].includes(action)) {
        return 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300';
    }
    if (['impersonate', 'impersonated'].includes(action)) {
        return 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300';
    }
    if (['login'].includes(action)) {
        return 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300';
    }
    return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    });
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
    <Head title="Action Logs & Audit Trail" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-gray-100 leading-tight flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        Action Logs & Audit Trail
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ isMasterAdmin ? 'Complete multi-tenant activity log and security audit records across workshops' : 'Detailed chronological log of all staff activities, job card changes, stock adjustments and payments' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        @click="exportCsv"
                        class="px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-200 rounded-xl shadow-xs hover:bg-gray-50 dark:hover:bg-zinc-700 transition font-semibold text-xs flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV Audit Log
                    </button>
                    <Link
                        v-if="isMasterAdmin"
                        :href="route('garages.index')"
                        class="px-4 py-2 bg-gray-900 text-white rounded-xl shadow-xs hover:bg-gray-800 transition font-semibold text-xs flex items-center gap-1.5"
                    >
                        ← Back to Garages
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <SettingsNav v-if="!isMasterAdmin" />

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-800 p-5 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-xs">
                    <p class="text-xs font-semibold text-gray-500">Total Logged Actions</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1">Recorded events</p>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-xs">
                    <p class="text-xs font-semibold text-emerald-600">Today's Activity</p>
                    <h3 class="text-2xl font-bold text-emerald-700 dark:text-emerald-400 mt-1">{{ stats.today }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1">Actions since midnight</p>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-xs">
                    <p class="text-xs font-semibold text-indigo-600">Last 7 Days</p>
                    <h3 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400 mt-1">{{ stats.this_week }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1">Rolling week volume</p>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-xs">
                    <p class="text-xs font-semibold text-rose-600">Critical Actions</p>
                    <h3 class="text-2xl font-bold text-rose-700 dark:text-rose-400 mt-1">{{ stats.critical }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1">Deletions, resets & restores</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-5 border border-gray-100 dark:border-zinc-700 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <div class="flex-1 relative">
                        <input
                            v-model="filterForm.search"
                            @keydown.enter="applyFilters"
                            type="text"
                            placeholder="Search actions by description, user, keyword, IP..."
                            class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 transition shadow-xs"
                        >
                            Filter
                        </button>
                        <button
                            @click="resetFilters"
                            class="px-3 py-2 bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300 rounded-xl text-xs font-medium hover:bg-gray-200 transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Dropdown Filters Row -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 pt-2 border-t border-gray-100 dark:border-zinc-700">
                    <!-- Garage Selector (SuperAdmin Master) -->
                    <div v-if="isMasterAdmin">
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Garage</label>
                        <select
                            v-model="filterForm.garage_id"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="">All Garages</option>
                            <option v-for="g in garages" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>

                    <!-- Module Filter -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Module</label>
                        <select
                            v-model="filterForm.module"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="all">All Modules</option>
                            <option value="job_cards">Job Cards</option>
                            <option value="inventory">Inventory & Stock</option>
                            <option value="purchases">Purchases</option>
                            <option value="sales">Counter Sales</option>
                            <option value="expenses">Expenses</option>
                            <option value="service_requests">Service Bookings</option>
                            <option value="customers">Customers</option>
                            <option value="staff">Staff & Team</option>
                            <option value="settings">Settings & Branding</option>
                            <option value="garages">Garage Admin</option>
                            <option value="auth">Logins / Security</option>
                            <option value="system">Backups & System</option>
                        </select>
                    </div>

                    <!-- Action Type Filter -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Action Type</label>
                        <select
                            v-model="filterForm.action"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="all">All Actions</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="status_changed">Status Changed</option>
                            <option value="payment_received">Payment</option>
                            <option value="impersonated">Impersonated</option>
                            <option value="login">Login</option>
                            <option value="restored">Restored</option>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Performed By</label>
                        <select
                            v-model="filterForm.user_id"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="all">All Users</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.role }})
                            </option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Date Range</label>
                        <select
                            v-model="filterForm.date_range"
                            @change="applyFilters"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white py-1.5"
                        >
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">Last 7 Days</option>
                            <option value="this_month">This Month</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action Logs Table -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-100 dark:border-zinc-700 shadow-xs overflow-hidden">
                <div v-if="!logs.data || logs.data.length === 0" class="py-16 text-center">
                    <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-gray-800 dark:text-white">No Action Logs Found</h4>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                        There are no recorded action events matching the selected filters. Try broadening your filter criteria.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-zinc-900/50 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-zinc-700">
                                <th class="py-3 px-4">Timestamp</th>
                                <th v-if="isMasterAdmin" class="py-3 px-4">Garage</th>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Module</th>
                                <th class="py-3 px-4">Action</th>
                                <th class="py-3 px-4">Description</th>
                                <th class="py-3 px-4 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700 text-xs">
                            <template v-for="log in logs.data" :key="log.id">
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-zinc-700/20 transition">
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatRelativeTime(log.created_at) }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                            {{ formatDate(log.created_at) }}
                                        </div>
                                    </td>

                                    <td v-if="isMasterAdmin" class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ log.garage ? log.garage.name : 'System-Wide' }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[11px] flex items-center justify-center shrink-0">
                                                {{ (log.user_name || 'S')[0].toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ log.user_name || 'System' }}</div>
                                                <div class="text-[10px] text-gray-400 capitalize">{{ log.user_role || 'automated' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-medium text-[11px] bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-200">
                                            {{ formatModuleLabel(log.module) }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full font-semibold text-[10px] border', getActionBadgeClass(log.action)]">
                                            {{ log.action }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-gray-700 dark:text-gray-200">
                                        <p class="line-clamp-2 max-w-md">{{ log.description }}</p>
                                        <span v-if="log.ip_address" class="text-[10px] text-gray-400 font-mono block mt-0.5">
                                            IP: {{ log.ip_address }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <button
                                            v-if="log.details"
                                            @click="toggleRow(log.id)"
                                            class="px-2.5 py-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg transition"
                                        >
                                            {{ expandedRows[log.id] ? 'Hide' : 'Inspect' }}
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">—</span>
                                    </td>
                                </tr>

                                <!-- Expandable Details Row -->
                                <tr v-if="expandedRows[log.id] && log.details" class="bg-gray-50/50 dark:bg-zinc-900/40">
                                    <td :colspan="isMasterAdmin ? 7 : 6" class="p-4">
                                        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 text-xs font-mono overflow-x-auto max-h-60">
                                            <div class="text-[11px] font-sans font-bold text-gray-500 uppercase tracking-wider mb-2">Event Metadata / Payload:</div>
                                            <pre class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ JSON.stringify(log.details, null, 2) }}</pre>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="px-5 py-4 border-t border-gray-100 dark:border-zinc-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                    <div>
                        Showing <span class="font-semibold text-gray-800 dark:text-white">{{ logs.from || 0 }}</span> to <span class="font-semibold text-gray-800 dark:text-white">{{ logs.to || 0 }}</span> of <span class="font-semibold text-gray-800 dark:text-white">{{ logs.total || 0 }}</span> actions
                    </div>
                    <div class="flex items-center gap-1 flex-wrap">
                        <template v-for="(link, i) in logs.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300',
                                    'px-3 py-1.5 rounded-lg border border-gray-200 dark:border-zinc-600 transition text-xs'
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-gray-300 dark:text-zinc-600 cursor-not-allowed text-xs"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
