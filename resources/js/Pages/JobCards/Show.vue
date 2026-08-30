<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { format } from 'date-fns';

const props = defineProps({
    jobCard: Object,
});

const uploadForm = useForm({
    payment_screenshot: null,
});

const submitScreenshot = () => {
    uploadForm.post(route('job-cards.payment-screenshot', props.jobCard.id), {
        preserveScroll: true,
        onSuccess: () => uploadForm.reset(),
    });
};

const calculateTotal = () => {
    return props.jobCard.items ? props.jobCard.items.reduce((total, item) => total + parseFloat(item.total), 0) : 0;
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
    <AuthenticatedLayout>
        <Head title="View Job Card" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Job Card: {{ jobCard.job_number }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Date: {{ jobCard.created_at ? format(new Date(jobCard.created_at), 'dd MMM yyyy') : 'N/A' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link v-if="$page.url.includes('from=sales')" :href="route('sales.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 mr-2">
                        &larr; Back to Sales
                    </Link>
                    <Link v-else :href="route('job-cards.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 mr-2">
                        &larr; Back to Job Cards
                    </Link>
                    <Link v-if="!($page.props.auth.user.role === 'customer' && ['delivered', 'completed', 'complete'].includes(jobCard.status.toLowerCase()))" :href="route('job-cards.edit', jobCard.id) + ($page.url.includes('from=sales') ? '?from=sales' : '')" class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 transition-colors">
                        Edit Job Card
                    </Link>
                    <a :href="route('job-cards.invoice', jobCard.id)" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print Invoice
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Left Column: Details -->
                    <div class="md:col-span-2 space-y-6">
                        
                        <!-- Customer & Vehicle Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <h3 class="text-lg font-bold text-gray-900">Customer & Vehicle Details</h3>
                                <span class="px-3 py-1 text-xs font-bold uppercase rounded-full bg-blue-100 text-blue-800">{{ jobCard.status }}</span>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-8">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer</p>
                                    <p class="font-bold text-gray-900 text-lg">{{ jobCard.customer?.user?.name || 'Walk-in Customer' }}</p>
                                    <p class="text-sm text-gray-600 mt-1 flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        {{ jobCard.customer?.user?.phone || 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Vehicle</p>
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="inline-flex items-center bg-white border border-gray-300 rounded shadow-sm overflow-hidden">
                                            <div class="bg-[#1a56db] flex flex-col items-center justify-center px-2 py-1.5 text-white">
                                                <div class="w-3 h-3 rounded-full border-[1.5px] border-white flex items-center justify-center mb-0.5">
                                                    <div class="w-0.5 h-0.5 bg-white rounded-full"></div>
                                                </div>
                                                <span class="text-[8px] font-bold leading-none">IND</span>
                                            </div>
                                            <div class="px-3 py-1 font-black text-gray-900 uppercase tracking-widest text-lg">
                                                {{ formatLicensePlate(jobCard.vehicle?.registration_number) }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="font-bold text-gray-900">{{ jobCard.vehicle?.brand?.name }} {{ jobCard.vehicle?.vehicle_model?.name }}</p>
                                    <p class="text-sm text-gray-600 mt-1">KM: {{ jobCard.vehicle_running_km || 'N/A' }} | Fuel: {{ jobCard.fuel_range || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Issues -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" v-if="jobCard.issues && jobCard.issues.length > 0">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Reported Issues</h3>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li v-for="issue in jobCard.issues" :key="issue.id" class="flex items-start gap-3">
                                        <div class="mt-0.5 w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-gray-800">{{ issue.description }}</p>
                                            
                                            <!-- Image Attachments -->
                                            <div v-if="issue.images && issue.images.length > 0" class="flex gap-2 mt-2 flex-wrap">
                                                <a v-for="img in issue.images" :key="img" :href="`/storage/${img}`" target="_blank" class="h-16 w-16 bg-gray-200 rounded border border-gray-300 overflow-hidden block hover:opacity-80 transition-opacity">
                                                    <img :src="`/storage/${img}`" class="w-full h-full object-cover" />
                                                </a>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3" v-if="issue.customer_notes || issue.mechanic_notes">
                                                <!-- Customer Notes -->
                                                <div v-if="issue.customer_notes" class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Customer Notes</p>
                                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ issue.customer_notes }}</p>
                                                </div>
                                                
                                                <!-- Mechanic Notes -->
                                                <div v-if="issue.mechanic_notes" class="bg-blue-50/30 rounded-lg p-3 border border-blue-100/50">
                                                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Mechanic Notes</p>
                                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ issue.mechanic_notes }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>



                        <!-- Services & Parts -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Services & Parts</h3>
                            </div>
                            <div class="p-0">
                                <div v-if="!jobCard.items || jobCard.items.length === 0" class="text-center py-8">
                                    <p class="text-sm text-gray-500">No services or parts added yet.</p>
                                </div>
                                <div v-else class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Item</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type</th>
                                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-100">
                                            <tr v-for="item in jobCard.items" :key="item.id">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ item.item_name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 uppercase tracking-wider">
                                                        {{ item.item_type === 'service' ? 'Service' : 'Part' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900 text-right">{{ Number(item.total).toLocaleString('en-IN') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Meta & Actions -->
                    <div class="space-y-6">
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Job Meta</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Priority</p>
                                    <p class="font-bold" :class="{'text-red-600': jobCard.priority === 'High', 'text-yellow-600': jobCard.priority === 'Medium', 'text-green-600': jobCard.priority === 'Low'}">
                                        {{ jobCard.priority }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Expected Delivery</p>
                                    <p class="font-medium text-gray-900">{{ jobCard.expected_delivery_date ? format(new Date(jobCard.expected_delivery_date), 'dd MMM yyyy') : 'Not set' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Assigned Mechanics</p>
                                    <div v-if="jobCard.mechanics && jobCard.mechanics.length > 0" class="mt-2 space-y-2">
                                        <div v-for="mech in jobCard.mechanics" :key="mech.id" class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                                                {{ mech.name ? mech.name.charAt(0).toUpperCase() : 'U' }}
                                            </div>
                                            <span class="text-sm font-medium text-gray-700">{{ mech.name || 'Unknown' }}</span>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-gray-500 italic mt-1">Unassigned</p>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-900 text-white">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-300">Total Estimate</span>
                                    <span class="text-xl font-bold">₹{{ calculateTotal() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Screenshot -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Payment Screenshot</h3>
                            </div>
                            <div class="p-6">
                                <div v-if="jobCard.payment_screenshot_path">
                                    <a :href="`/storage/${jobCard.payment_screenshot_path}`" target="_blank" class="block border border-gray-200 rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                                        <img :src="`/storage/${jobCard.payment_screenshot_path}`" alt="Payment Screenshot" class="w-full h-auto object-cover max-h-48" />
                                    </a>
                                </div>
                                <div v-else>
                                    <form @submit.prevent="submitScreenshot" class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Screenshot</label>
                                            <input type="file" accept="image/*" @input="uploadForm.payment_screenshot = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                            <p v-if="uploadForm.errors.payment_screenshot" class="mt-1 text-sm text-red-600">{{ uploadForm.errors.payment_screenshot }}</p>
                                        </div>
                                        <button type="submit" :disabled="uploadForm.processing" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                                            <svg v-if="uploadForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Upload
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
