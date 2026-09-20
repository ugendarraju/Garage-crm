<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    garage: Object
});

const form = useForm({
    _method: 'POST', // for file uploads
    name: props.garage?.name || '',
    tagline: props.garage?.tagline || '',
    address: props.garage?.address || '',
    gst_number: props.garage?.gst_number || '',
    bank_details: props.garage?.bank_details || '',
    upi_id: props.garage?.upi_id || '',
    header_logo_size: props.garage?.header_logo_size || 32,
    logo: null,
    remove_logo: false,
    qr_code: null,
    remove_qr_code: false,
});

const logoPreview = ref(props.garage?.logo_path ? `/storage/${props.garage.logo_path}` : null);
const qrPreview = ref(props.garage?.qr_code_path ? `/storage/${props.garage.qr_code_path}` : null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = null;
};

const handleQrChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.qr_code = file;
        form.remove_qr_code = false;
        qrPreview.value = URL.createObjectURL(file);
    }
};

const removeQrCode = () => {
    form.qr_code = null;
    form.remove_qr_code = true;
    qrPreview.value = null;
};

const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Garage Settings" />

    <AuthenticatedLayout>
        <div class="px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto py-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">Garage Settings</h1>

            <!-- Feedback Notifications -->
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-8 divide-y divide-gray-200 bg-white p-6 sm:p-8 rounded-xl shadow-sm ring-1 ring-gray-200">
                
                <!-- General Info -->
                <div class="space-y-6 pt-2">
                    <div>
                        <h3 class="text-lg font-bold leading-6 text-gray-900">General Information</h3>
                        <p class="mt-1 text-sm text-gray-500">Details about your garage that will appear on invoices and customer job cards.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Garage Name *</label>
                            <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p v-if="form.errors.name" class="text-xs text-rose-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Tagline / Subtitle</label>
                            <input type="text" v-model="form.tagline" placeholder="e.g. Multi-Brand 2-Wheeler Experts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p v-if="form.errors.tagline" class="text-xs text-rose-600 mt-1">{{ form.errors.tagline }}</p>
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Full Address</label>
                            <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Plot No. 12, Main Road, Auto Nagar..."></textarea>
                            <p v-if="form.errors.address" class="text-xs text-rose-600 mt-1">{{ form.errors.address }}</p>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">GSTIN / Tax ID</label>
                            <input type="text" v-model="form.gst_number" placeholder="e.g. 36AAACG1234F1Z5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase">
                            <p v-if="form.errors.gst_number" class="text-xs text-rose-600 mt-1">{{ form.errors.gst_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="space-y-6 pt-8">
                    <div>
                        <h3 class="text-lg font-bold leading-6 text-gray-900">Garage Branding</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload your garage's personal logo for navigation header and invoice printing.</p>
                    </div>

                    <div class="sm:col-span-6 border-b border-gray-100 pb-6 mb-2">
                        <label class="block text-sm font-medium text-gray-700">Garage Logo</label>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <div class="h-20 w-32 border rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden p-1 shadow-inner">
                                <img v-if="logoPreview" :src="logoPreview" class="object-contain max-h-full max-w-full" />
                                <span v-else class="text-xs text-gray-400 font-medium">No Logo</span>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <input type="file" @change="handleLogoChange" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                
                                <button v-if="logoPreview" type="button" @click="removeLogo" class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors w-fit">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove Logo
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Supports PNG, JPG, JPEG, WEBP or SVG. Max size: 2MB.</p>
                        <p v-if="form.errors.logo" class="text-xs text-rose-600 mt-1">{{ form.errors.logo }}</p>

                        <div class="mt-6" v-if="logoPreview">
                            <label class="block text-sm font-medium text-gray-700">Logo Display Size in Top Header ({{ form.header_logo_size }}px)</label>
                            <div class="flex items-center gap-4 mt-2">
                                <input type="range" min="20" max="80" v-model="form.header_logo_size" class="w-full max-w-xs accent-indigo-600">
                                <span class="text-sm font-bold text-gray-700">{{ form.header_logo_size }}px</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Adjust the preview size of your garage's logo in the top bar.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="space-y-6 pt-8">
                    <div>
                        <h3 class="text-lg font-bold leading-6 text-gray-900">Payment & Bank Details</h3>
                        <p class="mt-1 text-sm text-gray-500">These details and UPI QR code appear directly on customer invoices for direct payments.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Bank Account Details</label>
                            <textarea v-model="form.bank_details" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Bank: HDFC Bank&#10;Account No: 50100234567890&#10;IFSC: HDFC0001234&#10;Branch: Auto Nagar"></textarea>
                            <p v-if="form.errors.bank_details" class="text-xs text-rose-600 mt-1">{{ form.errors.bank_details }}</p>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">UPI ID / Mobile Number</label>
                                <input type="text" v-model="form.upi_id" placeholder="e.g. 9876543210@upi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p v-if="form.errors.upi_id" class="text-xs text-rose-600 mt-1">{{ form.errors.upi_id }}</p>
                            </div>

                            <label class="block text-sm font-medium text-gray-700">Payment QR Code</label>
                            <div class="mt-2 flex flex-wrap items-center gap-4">
                                <div class="h-20 w-20 border rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden p-1 shadow-inner">
                                    <img v-if="qrPreview" :src="qrPreview" class="h-full w-full object-contain" />
                                    <span v-else class="text-xs text-gray-400 text-center font-medium">No QR</span>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <input type="file" @change="handleQrChange" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                    <button v-if="qrPreview" type="button" @click="removeQrCode" class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors w-fit">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Remove QR Code
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Max size: 2MB.</p>
                            <p v-if="form.errors.qr_code" class="text-xs text-rose-600 mt-1">{{ form.errors.qr_code }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 justify-center rounded-lg border border-transparent bg-indigo-600 py-2.5 px-6 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all disabled:opacity-50">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

