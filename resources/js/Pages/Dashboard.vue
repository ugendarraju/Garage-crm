<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    metrics: Object,
    recentJobs: Array,
    recentCustomers: Array,
    mechanicsWorkload: Array,
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'complete': return 'bg-green-500 text-white';
        case 'working': return 'bg-orange-400 text-white';
        case 'listed': return 'bg-red-500 text-white';
        case 'scheduled': return 'bg-yellow-400 text-white';
        default: return 'bg-gray-500 text-white';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'complete': return 'Completed';
        case 'working': return 'In Progress';
        case 'listed': return 'Pending';
        case 'scheduled': return 'Scheduled';
        default: return status;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="px-2">
            <!-- Header section -->
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Welcome back, {{ $page.props.auth.user.name.split(' ')[0] }}!</h1>
                <p class="mt-1 text-sm text-gray-500 font-medium">Here's what's happening with your garage today.</p>
            </div>

            <!-- Stats -->
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- Total Job Cards -->
                <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <dt class="text-sm font-semibold text-gray-500">Total Jobs Card</dt>
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <dd>
                        <p class="text-4xl font-black text-gray-900 mb-1">{{ metrics.total_jobs }}</p>
                        <p class="text-xs font-semibold text-gray-600">{{ metrics.pending_jobs }} pending</p>
                    </dd>
                </div>

                <!-- Total Customers -->
                <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <dt class="text-sm font-semibold text-gray-500">Total Customers</dt>
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <dd>
                        <p class="text-4xl font-black text-gray-900 mb-1">{{ metrics.total_customers }}</p>
                        <p class="text-xs font-semibold text-gray-600">{{ metrics.new_customers_month }} new this month</p>
                    </dd>
                </div>

                <!-- Total Revenue -->
                <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <dt class="text-sm font-semibold text-gray-500">Total Revenue</dt>
                        <span class="text-xl font-bold text-gray-400">₹</span>
                    </div>
                    <dd>
                        <p class="text-4xl font-black text-gray-900 mb-1">₹{{ Number(metrics.total_revenue || 0).toLocaleString('en-IN') }}</p>
                        <p class="text-xs font-semibold text-gray-600">This Month</p>
                    </dd>
                </div>

                <!-- Active Mechanics -->
                <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <dt class="text-sm font-semibold text-gray-500">Active Mechanics</dt>
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <dd>
                        <p class="text-4xl font-black text-gray-900 mb-1">{{ metrics.active_mechanics }}</p>
                        <p class="text-xs font-semibold text-gray-600">Available for work</p>
                    </dd>
                </div>
            </dl>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Recent Job Cards -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-6 flex flex-col hover:shadow-md transition-shadow">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Recent Job Cards</h3>
                            <p class="text-xs font-medium text-gray-500 mt-1">Latest job cards created</p>
                        </div>
                        <Link :href="route('job-cards.index')" class="text-sm font-semibold text-primary-600 hover:text-primary-700">View All</Link>
                    </div>
                    <ul role="list" class="flex-1 space-y-6">
                        <li v-for="job in recentJobs" :key="job.id" class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <span :class="[getStatusColor(job.status), 'px-3 py-1 rounded-full text-xs font-bold w-24 text-center shadow-sm']">
                                    {{ getStatusLabel(job.status) }}
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ job.job_number }}</p>
                                    <p class="text-xs font-medium text-gray-500">{{ job.customer?.user?.name || 'Walk-in' }}</p>
                                </div>
                            </div>
                            <div class="text-xs font-medium text-gray-400">
                                {{ formatDate(job.created_at) }}
                            </div>
                        </li>
                        <li v-if="recentJobs.length === 0" class="text-sm text-gray-500 py-4">No recent job cards.</li>
                    </ul>
                </div>

                <!-- Recent Customers -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-200 p-6 flex flex-col hover:shadow-md transition-shadow">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Recent Customers</h3>
                            <p class="text-xs font-medium text-gray-500 mt-1">Latest customers added</p>
                        </div>
                        <button class="text-sm font-semibold text-primary-600 hover:text-primary-700">View All</button>
                    </div>
                    <ul role="list" class="flex-1 space-y-6">
                        <li v-for="customer in recentCustomers" :key="customer.id" class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-[#fdebe7] text-[#b73b22] flex items-center justify-center font-bold text-lg">
                                    {{ customer.user?.name ? customer.user.name.charAt(0).toUpperCase() : 'U' }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ customer.user?.name || 'Unknown' }}</p>
                                    <p class="text-xs font-medium text-gray-500">{{ customer.user?.phone || 'No phone' }}</p>
                                </div>
                            </div>
                            <div class="text-xs font-medium text-gray-400">
                                {{ formatDate(customer.created_at) }}
                            </div>
                        </li>
                        <li v-if="recentCustomers.length === 0" class="text-sm text-gray-500 py-4">No recent customers.</li>
                    </ul>
                </div>
            </div>

            <!-- Mechanics Workload -->
            <div class="mt-8 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <h3 class="text-base font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Mechanics Workload
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="py-3 px-4 font-semibold text-gray-900 rounded-l-lg">Mechanic</th>
                                    <th class="py-3 px-4 font-semibold text-gray-900">Active Jobs</th>
                                    <th class="py-3 px-4 font-semibold text-gray-900 rounded-r-lg">Assigned Tasks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="mechanic in mechanicsWorkload" :key="mechanic.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900">{{ mechanic.name }}</div>
                                        <div class="text-xs text-gray-500 capitalize">{{ mechanic.role }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center justify-center bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full font-bold text-xs">
                                            {{ mechanic.active_jobs_count }} Jobs
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="job in mechanic.active_jobs" :key="job.job_number" 
                                                  :class="[
                                                      job.priority === 'High' ? 'border-red-200 text-red-700 bg-red-50' : 
                                                      job.priority === 'Low' ? 'border-green-200 text-green-700 bg-green-50' : 
                                                      'border-yellow-200 text-yellow-700 bg-yellow-50',
                                                      'inline-flex items-center rounded-md border px-2 py-1 text-[10px] font-bold uppercase tracking-wider'
                                                  ]">
                                                {{ job.job_number }}
                                            </span>
                                            <span v-if="!mechanic.active_jobs || !mechanic.active_jobs.length" class="text-gray-400 text-xs italic">No active jobs</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!mechanicsWorkload || mechanicsWorkload.length === 0">
                                    <td colspan="3" class="py-8 text-center text-gray-500 text-sm">No mechanics found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
