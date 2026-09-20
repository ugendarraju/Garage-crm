<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            crm_name: 'GarageSaarthi',
            crm_subheading: 'Smart Workspace',
            crm_logo_path: null,
            crm_logo_size: 36,
            crm_favicon_path: null,
            logo_url: null,
            favicon_url: null,
        }),
    },
});

const form = useForm({
    _method: 'POST',
    crm_name: props.settings.crm_name || 'GarageSaarthi',
    crm_subheading: props.settings.crm_subheading || '',
    crm_logo_size: props.settings.crm_logo_size || 36,
    logo: null,
    remove_logo: false,
    favicon: null,
    remove_favicon: false,
});

const fileInput = ref(null);
const logoPreview = ref(props.settings.logo_url || null);

const faviconInput = ref(null);
const faviconPreview = ref(props.settings.favicon_url || null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleFaviconChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.favicon = file;
        form.remove_favicon = false;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const triggerFaviconInput = () => {
    if (faviconInput.value) {
        faviconInput.value.click();
    }
};

const removeFavicon = () => {
    form.favicon = null;
    form.remove_favicon = true;
    faviconPreview.value = null;
    if (faviconInput.value) {
        faviconInput.value.value = '';
    }
};

const crmInitials = computed(() => {
    const name = form.crm_name.trim();
    if (!name) return 'GS';
    const parts = name.split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Keep preview updated
        },
    });
};
</script>

<template>
    <Head title="CRM Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">CRM Branding & Settings</h1>
                    <p class="text-sm text-gray-500 mt-1">Configure your CRM's brand name, tagline, navigation logo, and browser favicon.</p>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto py-4">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 shadow-sm flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">
                    ✓
                </div>
                <div>
                    <p class="text-sm font-semibold text-green-900">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Branding Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            Application Branding
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Customize the text displayed in the navigation sidebar, mobile drawers, and browser headers.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-1">
                                    CRM Application Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="form.crm_name"
                                    required
                                    placeholder="e.g. GarageSaarthi or Piston CRM"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 font-medium"
                                />
                                <p class="text-xs text-gray-500 mt-1.5">Replaces "GarageSaarthi" across the entire platform.</p>
                                <p v-if="form.errors.crm_name" class="text-xs text-red-600 mt-1">{{ form.errors.crm_name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-1">
                                    Subheading / Tagline
                                </label>
                                <input
                                    type="text"
                                    v-model="form.crm_subheading"
                                    placeholder="e.g. Smart Workspace for Garages"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 font-medium"
                                />
                                <p class="text-xs text-gray-500 mt-1.5">Appears directly below the CRM name in the sidebar and headers.</p>
                                <p v-if="form.errors.crm_subheading" class="text-xs text-red-600 mt-1">{{ form.errors.crm_subheading }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo & Graphics Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            CRM Logo Upload
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload a custom logo image to represent your brand in the navigation.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-2">Application Logo</label>
                            
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 p-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50">
                                <!-- Logo preview container -->
                                <div class="flex-shrink-0 h-24 w-24 rounded-xl border border-gray-200 bg-white flex items-center justify-center p-2 shadow-inner overflow-hidden">
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Logo Preview"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-[10px] uppercase font-bold text-gray-400">Default Icon</span>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-3">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/png, image/jpeg, image/svg+xml, image/webp"
                                        class="hidden"
                                        @change="handleLogoChange"
                                    />

                                    <div class="flex flex-wrap items-center gap-3">
                                        <button
                                            type="button"
                                            @click="triggerFileInput"
                                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            {{ logoPreview ? 'Change Logo' : 'Upload Logo' }}
                                        </button>

                                        <button
                                            v-if="logoPreview"
                                            type="button"
                                            @click="removeLogo"
                                            class="px-3.5 py-2 bg-white hover:bg-red-50 text-red-600 hover:text-red-700 text-sm font-semibold rounded-lg border border-red-200 transition flex items-center gap-1.5"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove Logo
                                        </button>
                                    </div>

                                    <p class="text-xs text-gray-500">
                                        Supports PNG, SVG, JPG, or WebP. Transparent backgrounds look best. Recommended max dimensions: 512×512 px. Max file size: 2MB.
                                    </p>
                                    <p v-if="form.errors.logo" class="text-xs text-red-600 font-semibold">{{ form.errors.logo }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Size Slider -->
                        <div v-if="logoPreview" class="pt-4 border-t border-gray-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-sm font-bold text-gray-800">
                                    Logo Display Height in Sidebar:
                                    <span class="text-primary-600 font-extrabold">{{ form.crm_logo_size }}px</span>
                                </label>
                                <span class="text-xs text-gray-400">Default: 36px</span>
                            </div>
                            <input
                                type="range"
                                min="24"
                                max="64"
                                step="2"
                                v-model.number="form.crm_logo_size"
                                class="w-full accent-primary-600 cursor-pointer"
                            />
                            <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                                <span>Small (24px)</span>
                                <span>Medium (36px)</span>
                                <span>Large (64px)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Favicon Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            CRM Browser Favicon
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload a favicon that displays in browser tabs, bookmarks, and mobile shortcuts.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-2">Browser Tab Favicon</label>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 p-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50">
                                <!-- Favicon preview container -->
                                <div class="flex-shrink-0 h-16 w-16 rounded-xl border border-gray-200 bg-white flex items-center justify-center p-2 shadow-inner overflow-hidden">
                                    <img
                                        v-if="faviconPreview"
                                        :src="faviconPreview"
                                        alt="Favicon Preview"
                                        class="h-8 w-8 object-contain"
                                    />
                                    <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6 mb-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <span class="text-[8px] uppercase font-bold text-gray-400">Default</span>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-3">
                                    <input
                                        ref="faviconInput"
                                        type="file"
                                        accept="image/x-icon, image/png, image/svg+xml, image/jpeg, image/webp"
                                        class="hidden"
                                        @change="handleFaviconChange"
                                    />

                                    <div class="flex flex-wrap items-center gap-3">
                                        <button
                                            type="button"
                                            @click="triggerFaviconInput"
                                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            {{ faviconPreview ? 'Change Favicon' : 'Upload Favicon' }}
                                        </button>

                                        <button
                                            v-if="faviconPreview"
                                            type="button"
                                            @click="removeFavicon"
                                            class="px-3.5 py-2 bg-white hover:bg-red-50 text-red-600 hover:text-red-700 text-sm font-semibold rounded-lg border border-red-200 transition flex items-center gap-1.5"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove Favicon
                                        </button>
                                    </div>

                                    <p class="text-xs text-gray-500">
                                        Square images work best (32×32 or 64×64 px). Supported formats: .ico, .png, .svg. Max file size: 1MB.
                                    </p>
                                    <p v-if="form.errors.favicon" class="text-xs text-red-600 font-semibold">{{ form.errors.favicon }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Preview Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Live Header, Sidebar & Browser Tab Preview
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">This is how your branding and favicon will look live across the CRM.</p>
                    </div>

                    <div class="p-6 bg-gray-100/60 space-y-6">
                        <!-- Browser Tab Simulation -->
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-2">Simulated Browser Tab</span>
                            <div class="bg-gray-200/90 rounded-t-xl p-2 max-w-sm flex items-end">
                                <div class="bg-white rounded-t-lg px-3 py-2 flex items-center gap-2.5 shadow-sm max-w-[240px] border-t border-x border-gray-300">
                                    <img
                                        v-if="faviconPreview"
                                        :src="faviconPreview"
                                        class="h-4 w-4 object-contain shrink-0"
                                        alt="Favicon Tab"
                                    />
                                    <div v-else class="h-4 w-4 rounded-full bg-primary-600 flex items-center justify-center text-white text-[8px] font-bold shrink-0">
                                        {{ crmInitials.substring(0, 1) }}
                                    </div>
                                    <span class="text-xs font-medium text-gray-800 truncate">{{ form.crm_name || 'CRM' }} - {{ form.crm_subheading || 'Workspace' }}</span>
                                    <span class="text-gray-400 hover:text-gray-600 text-xs ml-auto cursor-pointer">×</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Sidebar Preview -->
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-2">Desktop Sidebar Header</span>
                            <div class="bg-white border border-gray-200 rounded-xl p-4 max-w-sm flex items-center gap-3 shadow-sm">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    :style="{ height: form.crm_logo_size + 'px' }"
                                    class="object-contain rounded shrink-0"
                                    alt="Logo"
                                />
                                <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-xl shadow-lg shadow-primary-900/20">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="overflow-hidden">
                                    <span class="text-lg font-extrabold text-gray-900 tracking-tight leading-none block truncate">
                                        {{ form.crm_name || 'Your Brand Name' }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 font-semibold leading-none uppercase tracking-wider block mt-1 truncate">
                                        {{ form.crm_subheading || 'Smart Workspace' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Header Badge Preview -->
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block mb-2">Mobile Header Badge</span>
                            <div class="bg-white border border-gray-200 rounded-xl p-3 max-w-xs flex items-center gap-3 shadow-sm">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    class="h-8 w-auto object-contain rounded"
                                    alt="Mobile Logo"
                                />
                                <div v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-sm shadow-sm">
                                    {{ crmInitials }}
                                </div>
                                <span class="text-sm font-bold text-gray-800">
                                    {{ form.crm_name || 'Brand' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-8 py-3 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Saving Settings...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
