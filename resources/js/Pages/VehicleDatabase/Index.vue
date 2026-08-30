<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    brands: Array
});

const page = usePage();
const activeGarageId = computed(() => page.props.auth.active_garage_id);

const selectedBrand = ref(null);

const brandForm = useForm({
    name: ''
});

const modelForm = useForm({
    brand_id: '',
    name: ''
});

const selectBrand = (brand) => {
    selectedBrand.value = brand;
    modelForm.brand_id = brand.id;
};

const submitBrand = () => {
    brandForm.post(route('vehicle-brands.store'), {
        preserveScroll: true,
        onSuccess: () => {
            brandForm.reset();
        }
    });
};

const deleteBrand = (brandId) => {
    if (confirm('Are you sure you want to delete this brand and all its models?')) {
        router.delete(route('vehicle-brands.destroy', brandId), { preserveScroll: true });
        if (selectedBrand.value && selectedBrand.value.id === brandId) {
            selectedBrand.value = null;
        }
    }
};

const submitModel = () => {
    modelForm.post(route('vehicle-models.store'), {
        preserveScroll: true,
        onSuccess: () => {
            modelForm.reset('name');
            // Update selected brand's models to reactively show the new one without full reload if possible,
            // though Inertia will pass new props. We can find the updated brand from props.
            const updatedBrand = props.brands.find(b => b.id === selectedBrand.value.id);
            if (updatedBrand) selectedBrand.value = updatedBrand;
        }
    });
};

const deleteModel = (modelId) => {
    if (confirm('Are you sure you want to delete this model?')) {
        router.delete(route('vehicle-models.destroy', modelId), {
            preserveScroll: true,
            onSuccess: () => {
                const updatedBrand = props.brands.find(b => b.id === selectedBrand.value.id);
                if (updatedBrand) selectedBrand.value = updatedBrand;
            }
        });
    }
};
</script>

<template>
    <Head title="Vehicle Database" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">Vehicle Database</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Brands List -->
                    <div class="md:col-span-1 bg-white dark:bg-zinc-800 shadow-sm sm:rounded-2xl overflow-hidden border border-gray-100 dark:border-zinc-700">
                        <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                            <h3 class="font-bold text-gray-800 dark:text-gray-200">Brands (Make)</h3>
                        </div>
                        
                        <div class="p-4 bg-white dark:bg-zinc-800 border-b border-gray-100 dark:border-zinc-700">
                            <form @submit.prevent="submitBrand" class="flex gap-2">
                                <input v-model="brandForm.name" type="text" placeholder="New Brand..." required class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white text-sm">
                                <button type="submit" :disabled="brandForm.processing" class="px-3 py-2 bg-orange-500 text-white rounded-lg text-sm font-semibold hover:bg-orange-600 transition">+</button>
                            </form>
                        </div>

                        <div class="overflow-y-auto max-h-[500px]">
                            <div v-for="brand in brands" :key="brand.id" 
                                @click="selectBrand(brand)"
                                :class="['p-4 border-b border-gray-50 dark:border-zinc-700/50 cursor-pointer flex justify-between items-center transition', 
                                         selectedBrand?.id === brand.id ? 'bg-orange-50 dark:bg-orange-900/20 border-l-4 border-l-orange-500' : 'hover:bg-gray-50 dark:hover:bg-zinc-700/30']">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ brand.name }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        <span v-if="brand.garage_id === null" class="bg-gray-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-300">Global</span>
                                        <span v-else class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded">Custom</span>
                                        · {{ brand.models?.length || 0 }} Models
                                    </div>
                                </div>
                                <div>
                                    <button @click.stop="deleteBrand(brand.id)" class="text-red-400 hover:text-red-600 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Models List -->
                    <div class="md:col-span-2 bg-white dark:bg-zinc-800 shadow-sm sm:rounded-2xl overflow-hidden border border-gray-100 dark:border-zinc-700">
                        <template v-if="selectedBrand">
                            <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50 flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-gray-800 dark:text-gray-200">{{ selectedBrand.name }} Models</h3>
                                    <p class="text-xs text-gray-500">Manage models for this brand</p>
                                </div>
                            </div>

                            <div class="p-4 bg-white dark:bg-zinc-800 border-b border-gray-100 dark:border-zinc-700">
                                <form @submit.prevent="submitModel" class="flex flex-col gap-2 max-w-xl">
                                    <div class="flex gap-2">
                                        <input v-model="modelForm.name" type="text" placeholder="e.g. Pulsar 150, Pulsar 220F" required class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white text-sm">
                                        <button type="submit" :disabled="modelForm.processing" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-semibold hover:opacity-80 transition whitespace-nowrap">Add Model(s)</button>
                                    </div>
                                    <p class="text-xs text-gray-500">Tip: You can add multiple models at once by separating them with commas.</p>
                                </form>
                            </div>

                            <div class="p-4">
                                <div v-if="!selectedBrand.models || selectedBrand.models.length === 0" class="text-center py-8 text-gray-500">
                                    No models found for this brand.
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="model in selectedBrand.models" :key="model.id" class="flex justify-between items-center p-3 rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900">
                                        <div>
                                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ model.name }}</div>
                                            <div class="text-[10px] uppercase tracking-wider font-semibold mt-1">
                                                <span v-if="model.garage_id === null" class="text-gray-400">Global</span>
                                                <span v-else class="text-green-500">Custom</span>
                                            </div>
                                        </div>
                                        <div>
                                            <button @click="deleteModel(model.id)" class="text-red-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex flex-col items-center justify-center h-full min-h-[400px] text-gray-400">
                                <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                <p class="text-lg font-medium">Select a brand</p>
                                <p class="text-sm">Choose a brand from the list to manage its models</p>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
