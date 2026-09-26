<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsNav from '@/Components/SettingsNav.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    garage: Object,
    bookingUrl: String,
});

const form = useForm({
    _method: 'POST', // for file uploads
    name: props.garage?.name || '',
    slug: props.garage?.slug || '',
    tagline: props.garage?.tagline || '',
    address: props.garage?.address || '',
    gst_number: props.garage?.gst_number || '',
    bank_details: props.garage?.bank_details || '',
    upi_id: props.garage?.upi_id || '',
    header_logo_size: props.garage?.header_logo_size || 32,
    default_service_reminder_days: props.garage?.default_service_reminder_days || 90,
    google_review_url: props.garage?.google_review_url || '',
    logo: null,
    remove_logo: false,
    qr_code: null,
    remove_qr_code: false,
});

const logoPreview = ref(props.garage?.logo_path ? `/storage/${props.garage.logo_path}` : null);
const qrPreview = ref(props.garage?.qr_code_path ? `/storage/${props.garage.qr_code_path}` : null);

const copied = ref(false);

const fullBookingUrl = computed(() => {
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const activeSlug = form.slug ? form.slug.toLowerCase().trim() : (props.garage?.slug || '');
    return activeSlug ? `${origin}/book/${activeSlug}` : `${origin}/book`;
});

const copyBookingLink = async () => {
    try {
        await navigator.clipboard.writeText(fullBookingUrl.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
};

const whatsappShareUrl = computed(() => {
    const text = encodeURIComponent(`Book your vehicle service appointment directly with ${form.name || 'our workshop'}: ${fullBookingUrl.value}`);
    return `https://wa.me/?text=${text}`;
});

const onSlugInput = (e) => {
    form.slug = e.target.value.toLowerCase().replace(/[^a-z0-9\-]/g, '-').replace(/-+/g, '-');
};

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
            <SettingsNav />

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

                <!-- Online Customer Booking Link & Custom Slug -->
                <div class="space-y-6 pt-8">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 text-orange-600 font-bold text-sm">🔗</span>
                            <h3 class="text-lg font-bold leading-6 text-gray-900">Direct Customer Booking Link</h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Give your customers a direct link to book service appointments. When they open this link, your garage is pre-selected and the "Select Garage" dropdown is completely hidden!
                        </p>
                    </div>

                    <!-- Custom Slug Input -->
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Custom Link Slug / Name *</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm font-mono">
                                    /book/
                                </span>
                                <input
                                    type="text"
                                    v-model="form.slug"
                                    @input="onSlugInput"
                                    placeholder="e.g. mechonwheels"
                                    class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono lowercase"
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Only lowercase letters, numbers, and dashes (e.g. <code>mechonwheels</code>, <code>apex-motors</code>).</p>
                            <p v-if="form.errors.slug" class="text-xs text-rose-600 mt-1">{{ form.errors.slug }}</p>
                        </div>
                    </div>

                    <!-- Shareable Link Card -->
                    <div class="bg-gradient-to-r from-orange-50 via-amber-50 to-orange-50/40 border border-orange-200/80 rounded-2xl p-5 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-orange-100 text-orange-800 border border-orange-200">
                                        Live Public Link
                                    </span>
                                    <span class="text-xs text-gray-500">Share anywhere</span>
                                </div>
                                <div class="font-mono text-sm sm:text-base font-bold text-gray-900 break-all select-all pt-1">
                                    {{ fullBookingUrl }}
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <!-- Copy Button -->
                                <button
                                    type="button"
                                    @click="copyBookingLink"
                                    :class="copied ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300'"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all"
                                >
                                    <svg v-if="copied" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ copied ? 'Link Copied!' : 'Copy Link' }}</span>
                                </button>

                                <!-- Preview / Open Button -->
                                <a
                                    :href="fullBookingUrl"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all"
                                >
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>Preview</span>
                                </a>

                                <!-- WhatsApp Share Button -->
                                <a
                                    :href="whatsappShareUrl"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#25D366] hover:bg-[#20ba5a] text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all"
                                >
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-orange-200/60 text-xs text-orange-900/80 flex items-start gap-2">
                            <span class="text-sm">💡</span>
                            <span><strong>Pro-Tip:</strong> Paste this URL on your workshop's Google Business Profile under "Appointment Link" and on your WhatsApp Business description to attract direct online customer bookings!</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Communication & Service Reminders -->
                <div class="space-y-6 pt-8">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 font-bold text-sm">🔔</span>
                            <h3 class="text-lg font-bold leading-6 text-gray-900">Service Reminders & Google Reviews</h3>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Configure recurring service reminder timing and your workshop's Google Maps review link.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- Reminder Interval -->
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Default Next Service Reminder (Days)</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input
                                    type="number"
                                    min="7"
                                    max="730"
                                    v-model.number="form.default_service_reminder_days"
                                    class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-semibold"
                                />
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <button type="button" @click="form.default_service_reminder_days = 90" :class="form.default_service_reminder_days === 90 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-2 py-0.5 rounded text-xs font-semibold transition">90 Days (3 Mo)</button>
                                <button type="button" @click="form.default_service_reminder_days = 120" :class="form.default_service_reminder_days === 120 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-2 py-0.5 rounded text-xs font-semibold transition">120 Days (4 Mo)</button>
                                <button type="button" @click="form.default_service_reminder_days = 180" :class="form.default_service_reminder_days === 180 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="px-2 py-0.5 rounded text-xs font-semibold transition">180 Days (6 Mo)</button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Auto-sets the customer's next service due date when closing a Job Card.</p>
                            <p v-if="form.errors.default_service_reminder_days" class="text-xs text-rose-600 mt-1">{{ form.errors.default_service_reminder_days }}</p>
                        </div>

                        <!-- Google Review URL -->
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Google Maps Review Link</label>
                            <input
                                type="url"
                                v-model="form.google_review_url"
                                placeholder="https://g.page/r/.../review or https://maps.app.goo.gl/..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"
                            />
                            <p class="mt-1 text-xs text-gray-500">Allows sending a 1-click WhatsApp message asking happy customers for a 5-star Google review!</p>
                            <p v-if="form.errors.google_review_url" class="text-xs text-rose-600 mt-1">{{ form.errors.google_review_url }}</p>
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

