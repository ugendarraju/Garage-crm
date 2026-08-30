<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    serviceRequests: Array
});

const approveRequest = (id) => {
    if (confirm('Are you sure you want to approve this request?')) {
        router.post(route('service-requests.approve', id));
    }
};

const declineRequest = (id) => {
    if (confirm('Are you sure you want to decline this request?')) {
        router.post(route('service-requests.decline', id));
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Service Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Customer Service Requests</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div v-if="serviceRequests.length === 0" class="text-center py-8 text-gray-500">
                            No service requests pending.
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b dark:border-zinc-700">
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Customer</th>
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Vehicle</th>
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Issues</th>
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Requested On</th>
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                        <th class="p-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="req in serviceRequests" :key="req.id" class="border-b dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition">
                                        <td class="p-3 align-top">
                                            <div class="font-medium">{{ req.customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ req.phone }}</div>
                                            <div class="text-sm text-gray-500">{{ req.email }}</div>
                                        </td>
                                        <td class="p-3 align-top">
                                            <div class="font-medium text-orange-500">{{ req.vehicle_brand }} {{ req.vehicle_model }}</div>
                                            <div class="text-sm text-gray-500">Year: {{ req.vehicle_year }}</div>
                                        </td>
                                        <td class="p-3 align-top max-w-xs">
                                            <p class="text-sm whitespace-pre-wrap">{{ req.issues }}</p>
                                        </td>
                                        <td class="p-3 align-top text-sm">
                                            {{ formatDate(req.created_at) }}
                                        </td>
                                        <td class="p-3 align-top">
                                            <span v-if="req.status === 'pending'" class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">Pending</span>
                                            <span v-else-if="req.status === 'approved'" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-medium">Approved</span>
                                            <span v-else class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">Declined</span>
                                        </td>
                                        <td class="p-3 align-top text-right space-x-2">
                                            <template v-if="req.status === 'pending'">
                                                <button @click="approveRequest(req.id)" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm font-medium">Approve</button>
                                                <button @click="declineRequest(req.id)" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition text-sm font-medium">Decline</button>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
