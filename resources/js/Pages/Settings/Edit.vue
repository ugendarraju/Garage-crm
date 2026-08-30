<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    garage: Object
});

const form = useForm({
    _method: 'POST', // for file uploads
    name: props.garage.name || '',
    tagline: props.garage.tagline || '',
    address: props.garage.address || '',
    gst_number: props.garage.gst_number || '',
    bank_details: props.garage.bank_details || '',
    upi_id: props.garage.upi_id || '',
    header_logo_size: props.garage.header_logo_size || 32,
    logo: null,
    qr_code: null,
});

const logoPreview = ref(props.garage.logo_path ? `/storage/${props.garage.logo_path}` : null);
const qrPreview = ref(props.garage.qr_code_path ? `/storage/${props.garage.qr_code_path}` : null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleQrChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.qr_code = file;
        qrPreview.value = URL.createObjectURL(file);
    }
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

            <form @submit.prevent="submit" class="space-y-8 divide-y divide-gray-200 bg-white p-8 rounded-lg shadow ring-1 ring-black ring-opacity-5">
                
                <!-- General Info -->
                <div class="space-y-6 pt-2">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">General Information</h3>
                        <p class="mt-1 text-sm text-gray-500">Details about your garage that will appear on invoices.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Garage Name *</label>
                            <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Tagline</label>
                            <input type="text" v-model="form.tagline" placeholder="e.g. Smart Garage Management" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Full Address</label>
                            <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="123 Mechanics Way..."></textarea>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">GST Number</label>
                            <input type="text" v-model="form.gst_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase">
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="space-y-6 pt-8">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Branding</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload your garage logo.</p>
                    </div>

                        <div class="sm:col-span-6 border-b border-gray-100 pb-6 mb-2">
                            <label class="block text-sm font-medium text-gray-700">Logo</label>
                            <div class="mt-2 flex items-center gap-x-4">
                                <img v-if="logoPreview" :src="logoPreview" class="object-contain border rounded bg-gray-50" :style="{ height: form.header_logo_size + 'px' }" />
                                <div v-else class="h-16 w-16 border border-dashed rounded flex items-center justify-center text-gray-400 bg-gray-50">No Logo</div>
                                <input type="file" @change="handleLogoChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Square or landscape logos work best. Max size: 2MB. Recommended dimensions: 200x50 to 512x512 pixels.</p>
                            <p v-if="form.errors.logo" class="text-xs text-red-500 mt-1">{{ form.errors.logo }}</p>

                            <div class="mt-6" v-if="logoPreview">
                                <label class="block text-sm font-medium text-gray-700">Header Logo Display Size ({{ form.header_logo_size }}px)</label>
                                <input type="range" min="20" max="100" v-model="form.header_logo_size" class="w-full mt-2 accent-indigo-600">
                                <p class="mt-1 text-xs text-gray-500">Adjust how large the logo appears in the top header and invoices.</p>
                            </div>
                        </div>
                </div>

                <!-- Payment Details -->
                <div class="space-y-6 pt-8">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Payment Details</h3>
                        <p class="mt-1 text-sm text-gray-500">Provide options for customers to pay their invoices.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Bank Details</label>
                            <textarea v-model="form.bank_details" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Bank: Global Bank Inc.&#10;Account: 1234-5678-9012&#10;IFSC: GBIN0001234"></textarea>
                        </div>

                        <div class="sm:col-span-3">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">UPI ID / Number</label>
                                <input type="text" v-model="form.upi_id" placeholder="e.g. 9876543210@upi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <label class="block text-sm font-medium text-gray-700">Payment QR Code</label>
                            <div class="mt-2 flex items-center gap-x-4">
                                <img v-if="qrPreview" :src="qrPreview" class="h-20 w-20 object-contain border rounded bg-gray-50" />
                                <div v-else class="h-20 w-20 border border-dashed rounded flex items-center justify-center text-gray-400 bg-gray-50 text-xs text-center p-1">No QR Code</div>
                                <input type="file" @change="handleQrChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Max size: 2MB.</p>
                            <p v-if="form.errors.qr_code" class="text-xs text-red-500 mt-1">{{ form.errors.qr_code }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-8 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
