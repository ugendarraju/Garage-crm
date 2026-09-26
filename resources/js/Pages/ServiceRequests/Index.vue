<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    serviceRequests: Array
});

const credentialsModal = ref(null);
const isCredentialsModalOpen = ref(false);
const copied = ref(false);

const page = usePage();
watch(
    () => page.props.flash?.new_customer_credentials,
    (creds) => {
        if (creds) {
            credentialsModal.value = creds;
            isCredentialsModalOpen.value = true;
        }
    },
    { immediate: true }
);

const closeCredentialsModal = () => {
    isCredentialsModalOpen.value = false;
    credentialsModal.value = null;
};

const copyCredentials = async () => {
    if (!credentialsModal.value) return;
    const creds = credentialsModal.value;
    const text = `Garage Portal Login Details:\nWebsite: ${window.location.origin}/login\nUsername / Phone: ${creds.phone}${creds.email ? '\nEmail: ' + creds.email : ''}\nTemporary Password: ${creds.temporary_password}\n\nNote: You will be asked to set a permanent password when you first log in.`;
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    } catch (e) {
        console.error('Failed to copy', e);
    }
};

const getWhatsAppShareUrl = (creds) => {
    if (!creds) return '#';
    const phone = creds.whatsapp_number || creds.phone;
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(
        `Hello ${creds.name},\nYour service request has been approved and your account is ready on the Garage Portal!\n\nWebsite: ${window.location.origin}/login\nPhone: ${creds.phone}\nTemporary Password: ${creds.temporary_password}\n\nPlease log in to track your service request and job cards. You will be prompted to set your personal password on your first login.`
    );
    return `https://wa.me/${cleanPhone}?text=${text}`;
};

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

        <!-- New Customer Credentials Popup Modal -->
        <Modal :show="isCredentialsModalOpen" @close="closeCredentialsModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight">Customer Account Created</h3>
                        <p class="text-xs text-gray-500">Service request approved & portal login generated.</p>
                    </div>
                </div>

                <div v-if="credentialsModal?.invite_sent" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Invite email with temporary password was sent to <strong>{{ credentialsModal.email }}</strong>.</span>
                </div>

                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-2.5 text-sm">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Customer:</span>
                        <span class="font-bold text-gray-900">{{ credentialsModal?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Phone (Login ID):</span>
                        <span class="font-bold text-gray-900 font-mono">{{ credentialsModal?.phone }}</span>
                    </div>
                    <div v-if="credentialsModal?.email" class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Email:</span>
                        <span class="font-bold text-gray-900">{{ credentialsModal?.email }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-500 block">Temporary Password</span>
                            <span class="text-lg font-black text-orange-600 font-mono tracking-wider">{{ credentialsModal?.temporary_password }}</span>
                        </div>
                        <button
                            @click="copyCredentials"
                            type="button"
                            class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs flex items-center gap-1"
                        >
                            <svg v-if="!copied" class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span v-if="copied" class="text-emerald-600 font-bold">Copied!</span>
                            <span v-else>Copy</span>
                        </button>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>First Login Security:</strong> The customer will be prompted to set a permanent password when they first log in.</span>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-2">
                    <a
                        :href="getWhatsAppShareUrl(credentialsModal)"
                        target="_blank"
                        class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        Share via WhatsApp
                    </a>
                    <button
                        type="button"
                        @click="closeCredentialsModal"
                        class="py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold text-xs rounded-xl transition text-center"
                    >
                        Done
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
